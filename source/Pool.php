<?php
namespace SeanMorris\Multiota;
class Pool
{
	protected
		$dataSource
		, $mapper
		, $reducer
		, $children
		, $maxRecords
		, $maxChunkSize
		, $sourceArgs
		, $childTimeout
		, $total
		, $mapped;

	public function __construct(...$args)
	{
		foreach($args as $arg)
		{
			if(is_a($arg, 'SeanMorris\Multiota\Reducer', TRUE))
			{
				$this->reducer = $arg;
				continue;
			}

			if(is_a($arg, 'SeanMorris\Multiota\Mapper', TRUE))
			{
				$this->mapper = $arg;
				continue;
			}

			if(is_a($arg, 'SeanMorris\Multiota\DataSource', TRUE))
			{
				$this->dataSource = new $arg;
				continue;
			}

			if(is_array($arg))
			{
				$this->sourceArgs = $arg;

				$arg = $arg + [
					'children'       => 16
					, 'maxRecords'   => 128
					, 'maxChunkSize' => 32
					, 'childTimeout' => 2
				];

				$this->children     = $arg['children'];
				$this->maxRecords   = $arg['maxRecords'];
				$this->maxChunkSize = $arg['maxChunkSize'];
				$this->childTimeout = $arg['childTimeout'];
				continue;
			}
		}
	}

	public function error($error)
	{
		fwrite(STDERR, $error . PHP_EOL);
	}

	public function progress($mapped)
	{
		$total = $this->dataSource->total();

		if($total !== NULL)
		{
			fwrite(STDERR, sprintf(
				"Pool %s %d/%d records.\n"
				, is_a($this->mapper, 'SeanMorris\Multiota\Reducer', TRUE)
					? 'reduced'
					: 'mapped'
				, $mapped
				, $total
			));
		}
		else
		{
			fwrite(STDERR, sprintf(
				"Pool %s %d records.\n"
				, is_a($this->mapper, 'SeanMorris\Multiota\Reducer', TRUE)
					? 'reduced'
					: 'mapped'
				, $mapped
			));
		}
	}

	public function start()
	{
		fwrite(STDERR, sprintf('Starting pool with room for %d children.', $this->children) . PHP_EOL);

		$mapperId         = 0;
		$reducerId        = 0;
		$sentToMapper             = 0;
		$mapped           = 0;
		$mappers          = [];
		$reducers         = [];
		$localReducer     = NULL;
		$maxMapperKey     = 0;
		$maxReducerKey    = 0;

		if($this->reducer)
		{
			$reducerClass = $this->reducer;
			$this->localReducer = new $reducerClass;
		}

		while(1)
		{
			$reduceRecords = [];

			while(!$this->dataSource->done() && count($mappers) < $this->children)
			{
				$newProcess = new ChildProcess($this->mapperCommand($mapperId));
				$mappers[] = $newProcess;
				$maxMapperKey = max(array_keys($mappers));
				$mapperId++;
			}

			foreach($mappers as $childId => $child)
			{
				if(!isset($mappersFed[$childId]))
				{
					$mappersFed[$childId] = 0;
				}

				$mapperCurrentChunk = 0;

				while(
					!$this->dataSource->done()
					&& $mappersFed[$childId] < $this->maxRecords
					&& $mapperCurrentChunk < $this->maxChunkSize
				){
					$record = $this->dataSource->fetch();

					if(!$this->dataSource->done() || $record)
					{
						$child->write(base64_encode(serialize($record)) . PHP_EOL);

						$mappersFed[$childId]++;
						$mapperCurrentChunk++;
						$sentToMapper++;
					}
				}

				while($error = $child->readError())
				{
					$this->error("\t" . $error);
				}

				while($record = $child->read())
				{
					$record = unserialize(base64_decode($record));

					// fwrite(STDERR, 'Got '. print_r($record, 1) . PHP_EOL);

					if(is_scalar($record))
					{
						fwrite(STDOUT, $record . PHP_EOL);
					}
					elseif($record instanceof ReduceRecord)
					{
						$reduceRecords[] = $record;
					}
					else
					{
						fwrite(STDOUT, base64_encode(serialize($record)) . PHP_EOL);
					}
				}

				if($child->isDead())
				{
					unset($child, $mappers[$childId], $mappersFed[$childId]);
				}
			}

			while($reduceRecords)
			{
				while(count($reducers) < $this->children)
				{
					$newProcess = new ChildProcess($this->reducerCommand($reducerId));
					$reducers[] = $newProcess;
					$maxReducerKey = max(array_keys($reducers));
					$reducerId++;
				}

				foreach($reducers as $childId => $child)
				{
					$child->write(base64_encode(serialize($record)) . PHP_EOL);

					while($error = $child->readError())
					{
						$this->error("\t" . $error);
					}

					while($record = $child->read())
					{
						$record = unserialize(base64_decode($record));

						fwrite(STDERR, '###--'. serialize($record) . PHP_EOL);

						$this->localReducer->process(new ReduceRecord('w_' . uniqid(), $record));
					}

					if($child->isDead())
					{
						fwrite(STDERR, '!!'. PHP_EOL);
						unset($child, $reducers[$childId]);
						continue;
					}

					if(!$reduceRecords)
					{
						break;
					}

					if(!$record = array_shift($reduceRecords))
					{
						continue;
					}
				}
			}

			$newProgress = $sentToMapper - array_sum($mappersFed);

			if($mapped !== $newProgress)
			{
				$mapped = $newProgress;

				$this->progress($mapped);
			}

			if($this->dataSource->done() && !$mappers)
			{
				break;
			}
		}

		foreach($reducers as $childId => $child)
		{
			while($error = $child->readError())
			{
				$this->error("\t" . $error);
			}

			while($record = $child->read())
			{
				$record = unserialize(base64_decode($record));

				fwrite(STDERR, '###'. serialize($record) . PHP_EOL);

				$this->localReducer->process(new ReduceRecord('w_' . uniqid(), $record));
			}

			if($child->isDead())
			{
				fwrite(STDERR, '!!'. PHP_EOL);
				unset($child, $reducers[$childId]);
				continue;
			}
		}

		if(isset($this->localReducer))
		{
			$reducedData = $this->localReducer->get();

			var_dump($reducedData);
		}

		$done = TRUE;

		fwrite(STDERR, 'Pool\'s closed.' . PHP_EOL);
	}

	protected function mapperCommand($started)
	{
		return sprintf(
			'idilic multiotaChild %s %d %d %0.3f'
			, escapeshellarg($this->mapper)
			, $started
			, $this->maxRecords
			, $this->childTimeout
		);
	}

	protected function reducerCommand($started)
	{
		return sprintf(
			'idilic multiotaChild %s %d %d %0.3f'
			, escapeshellarg($this->reducer)
			, $started
			, $this->maxRecords
			, $this->childTimeout
		);
	}

	protected function onChildOpen($child, $childId)
	{}
	protected function onChildKill($child, $childId)
	{}
}
