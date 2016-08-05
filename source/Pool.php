<?php
namespace SeanMorris\Multiota;
class Pool
{
	protected
		$dataSource
		, $processor
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
			if(is_a($arg, 'SeanMorris\Multiota\Processor', TRUE))
			{
				$this->processor = $arg;
			}

			if(is_a($arg, 'SeanMorris\Multiota\DataSource', TRUE))
			{
				$this->dataSource = new $arg;
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
			}
		}
	}

	public function error($error)
	{
		fwrite(STDERR, $error . PHP_EOL);
	}

	public function postprocess($record, $child)
	{
		fwrite(STDOUT, $record . PHP_EOL);
	}

	public function progress($progress)
	{
		$total = $this->dataSource->total();

		if($total !== NULL)
		{
			fwrite(STDERR, "Pool processed " . $progress . "/" . $total . " records.\n");
		}
		else
		{
			fwrite(STDERR, "Pool processed " . $progress . " records.\n");
		}
	}

	public function start()
	{
		fwrite(STDERR, sprintf('Starting pool with room for %d children.', $this->children) . PHP_EOL);
		$pipeDescriptor = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);

		$started = 0;
		$sent = 0;
		$processes = [];
		$progress = 0;

		while(1)
		{
			while(!$this->dataSource->done() && count($processes) < $this->children)
			{
				$processes[] = proc_open(
					$this->childCommand($started)
					, $pipeDescriptor
					, $pipe
				);

				$started++;

				stream_set_blocking($pipe[0], FALSE);
				stream_set_blocking($pipe[1], FALSE);
				stream_set_blocking($pipe[2], FALSE);

				$pipes[] = $pipe;

				$this->onChildOpen($pipe);
			}

			foreach($processes as $childId => $child)
			{
				if(!isset($fed[$childId]))
				{
					$fed[$childId] = 0;
				}

				while($output = fgets($pipes[$childId][1]))
				{
					$output = trim($output);

					if($output === '')
					{
						break;
					}

					$this->postprocess($output, $child);
				}

				while($error = fgets($pipes[$childId][2]))
				{
					$error = trim($error);

					$this->error("\t" . $error);
				}

				if(!is_resource($child) || feof($pipes[$childId][1]))
				{
					$status = proc_get_status($child);

					$this->onChildKill($pipes[$childId]);

					if($status['running'] == FALSE)
					{
						proc_close($child);
						unset(
							$processes[$childId]
							, $pipes[$childId]
							, $fed[$childId]
						);
						continue;
					}

					continue;
				}

				$curChunk = 0;

				while(
					!$this->dataSource->done()
					&& $fed[$childId] < $this->maxRecords
					&& $curChunk < $this->maxChunkSize
				){
					$record = $this->dataSource->fetch();

					if($record !== FALSE)
					{
						fwrite($pipes[$childId][0], $record . PHP_EOL);
					}

					$fed[$childId]++;
					$curChunk++;
					$sent++;
				}
			}

			$newProgress = $sent - array_sum($fed);

			if($progress !== $newProgress)
			{
				$progress = $newProgress;

				$this->progress($progress);
			}

			if($this->dataSource->done() && !$processes)
			{
				break;
			}
		}

		$done = TRUE;

		fwrite(STDERR, 'Pool\'s closed.' . PHP_EOL);
	}

	protected function childCommand($started)
	{
		return sprintf(
			'idilic batchProcess %s %d %d %f'
			, escapeshellarg($this->processor)
			, $started
			, $this->maxRecords
			, $this->childTimeout
		);
	}

	protected function onChildOpen($pipes)
	{}
	protected function onChildKill($pipes)
	{}
}
