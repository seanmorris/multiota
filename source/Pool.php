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
		, $progress;

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

	public function progress($progress)
	{
		$total = $this->dataSource->total();

		if($total !== NULL)
		{
			fwrite(STDERR, "Pool mapped " . $progress . "/" . $total . " records.\n");
		}
		else
		{
			fwrite(STDERR, "Pool mapped " . $progress . " records.\n");
		}
	}

	public function start()
	{
		fwrite(STDERR, sprintf('Starting pool with room for %d children.', $this->children) . PHP_EOL);

		$started         = 0;
		$sent            = 0;
		$progress        = 0;
		$mappers         = [];
		$reducers        = [];
		$reducersStarted = 0;
		$localReducer    = NULL;

		if($this->reducer)
		{
			$localReducer = new $this->reducer;
		}

		while(1)
		{
			while(!$this->dataSource->done() && count($mappers) < $this->children)
			{
				$newProcess = new ChildProcess($this->mapperCommand($started));
				$mappers[] = $newProcess;
				$started++;
			}

			while($mappers && count($reducers) < $this->children)
			{
				$newProcess = new ChildProcess($this->reducerCommand($started));
				$reducers[] = $newProcess;
				$started++;
			}

			foreach($mappers as $childId => $child)
			{
				if(!isset($fed[$childId]))
				{
					$fed[$childId] = 0;
				}

				while($error = $child->readError())
				{
					$this->error("\t" . $error);
				}

				while($record = $child->read())
				{
					$record = unserialize(base64_decode($record));

					if($record instanceof ReduceRecord && $this->reducer)
					{
						$reducerId = hexdec(substr(md5($record->key()), -4)) % count($reducers);

						$r = array_values($reducers);

						if(!isset($reducersFed[$reducerId]))
						{
							$reducersFed[$reducerId] = 0;
						}

						$reducer = $r[$reducerId];
						$reducer->write(base64_encode(serialize($record)) . PHP_EOL);
					}
					elseif(is_scalar($record))
					{
						fwrite(STDOUT, $record . PHP_EOL);
					}
					else
					{
						fwrite(STDOUT, base64_encode(serialize($record)) . PHP_EOL);
					}
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

				if($child->isDead())
				{
					unset($child, $mappers[$childId], $fed[$childId]);
				}
			}

			foreach($reducers as $reducerId => $reducer)
			{
				while($error = $reducer->readError())
				{
					$this->error("\t" . $error);
				}

				while($record = $reducer->read())
				{
					fwrite(STDERR, 'Received a record from a reducer.' . PHP_EOL);

					$record = unserialize(base64_decode($record));
					$localReducer->process($record);
				}

				if($reducer->isDead())
				{
					unset($reducer, $reducers[$reducerId]);
				}

			}

			$newProgress = $sent - array_sum($fed);

			if($progress !== $newProgress)
			{
				$progress = $newProgress;

				$this->progress($progress);
			}

			/*
			fwrite(STDERR, sprintf(
				"Data source done: %d, Mappers: %d, Reducers: %d\n"
				, $this->dataSource->done()
				, count($mappers)
				, count($reducers)
			));
			*/

			if($this->dataSource->done() && !$mappers && !$reducers)
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
