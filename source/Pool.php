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
		, $childTimeout
		, $total
		, $progress;

	public function __construct($dataSource, $processor = NULL
		, $children = 16, $maxRecords = 500
		, $maxChunkSize = 10, $childTimeout = 0.01
	){
		$this->dataSource = new $dataSource;
		$this->processor = $processor;
		$this->children = $children;
		$this->maxRecords = $maxRecords;
		$this->maxChunkSize = $maxChunkSize;
		$this->childTimeout = $childTimeout;
	}

	public function postprocess($record)
	{
		return;
		echo $record;
		echo PHP_EOL;
	}

	public function progress($progress)
	{
		print $progress;
		print "/";
		print $this->dataSource->total();
		print " Processed.\n";
	}

	public function start()
	{
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
					sprintf(
						'idilic batchProcess %s %d %d %f'
						, escapeshellarg($this->processor)
						, ++$started
						, $this->maxRecords
						, $this->childTimeout
					)
					, $pipeDescriptor
					, $pipe
				);

				stream_set_blocking($pipe[1], FALSE);

				$pipes[] = $pipe;
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

					$this->postprocess($output);
				}

				if(!is_resource($child) || feof($pipes[$childId][1]))
				{
					$status = proc_get_status($child);

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
					
					fwrite($pipes[$childId][0], $record . PHP_EOL);

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
	}
}
