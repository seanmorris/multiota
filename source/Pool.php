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
			/*
			if(is_a($arg, 'SeanMorris\Multiota\Reducer', TRUE))
			{
				$this->reducer = $arg;
				continue;
			}
			*/

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

		$started          = 0;
		$sent             = 0;
		$mapped           = 0;
		$mappers          = [];
		$localReducer     = NULL;
		$maxMapperKey     = 0;
		$maxReducerKey    = 0;
		$reducersFedTotal = 0;

		if(is_a($this->mapper, 'SeanMorris\Multiota\Reducer', TRUE))
		{
			$localReducer = new $this->mapper;
		}

		while(1)
		{
			while(!$this->dataSource->done() && count($mappers) < $this->children)
			{
				$newProcess = new ChildProcess($this->mapperCommand($started));
				$mappers[] = $newProcess;
				$maxMapperKey = max(array_keys($mappers));
				$started++;
			}

			foreach($mappers as $childId => $child)
			{
				if(!isset($fed[$childId]))
				{
					$fed[$childId] = 0;
				}

				$curChunk = 0;

				while(
					!$this->dataSource->done()
					&& $fed[$childId] < $this->maxRecords
					&& $curChunk < $this->maxChunkSize
				){
					$record = $this->dataSource->fetch();

					if(!$this->dataSource->done() || $record)
					{
						$child->write(base64_encode(serialize($record)) . PHP_EOL);

						$fed[$childId]++;
						$curChunk++;
						$sent++;
					}
				}

				while($error = $child->readError())
				{
					$this->error("\t" . $error);
				}

				$recordsFed = 0;

				while($record = $child->read())
				{
					$record = unserialize(base64_decode($record));

					if(is_scalar($record))
					{
						fwrite(STDOUT, $record . PHP_EOL);
					}
					elseif($localReducer && is_array($record))
					{
						foreach($record as $k => $v)
						{
							$localReducer->process(new ReduceRecord($k, $v));
						}
					}
					else
					{
						fwrite(STDOUT, base64_encode(serialize($record)) . PHP_EOL);
					}
				}

				if($child->isDead())
				{
					unset($child, $mappers[$childId], $fed[$childId]);
				}
			}

			$newProgress = $sent - array_sum($fed);

			if($mapped !== $newProgress)
			{
				$mapped = $newProgress;

				$this->progress($mapped, $reducersFedTotal);
			}

			if($this->dataSource->done() && !$mappers)
			{
				break;
			}
		}

		if($localReducer)
		{
			$reducedData = $localReducer->get();

			foreach($reducedData as $k => $v)
			{
				printf("%s,%s\n", $k, $v);
			}
		}

		$done = TRUE;

		fwrite(STDERR, 'Pool\'s closed.' . PHP_EOL);
	}

	protected function mapperCommand($started)
	{
		return sprintf(
			'idilic batchProcess %s %d %d %0.3f'
			, escapeshellarg($this->mapper)
			, $started
			, $this->maxRecords
			, $this->childTimeout
		);
	}

	protected function reducerCommand($started)
	{
		return sprintf(
			'idilic batchProcess %s %d %d %0.3f'
			, escapeshellarg($this->reducer)
			, $started
			, 0
			, $this->childTimeout
		);
	}

	protected function onChildOpen($child, $childId)
	{}
	protected function onChildKill($child, $childId)
	{}
}
