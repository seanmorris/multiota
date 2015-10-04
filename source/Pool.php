<?php
namespace SeanMorris\Multiota;
class Pool
{
	protected
		$processor = NULL
		, $children = 16
		, $maxRecords = 50
		, $maxChunkSize = 10;

	public function __construct($dataSource, $processor, $options = [])
	{
		$this->dataSource = new $dataSource;
		$this->processor = $processor;

		$this->children = isset($options['children'])
			? $options['children']
			: $this->children;

		$this->maxRecords = isset($options['maxRecords'])
			? $options['maxRecords']
			: $this->maxRecords;

		$this->maxChunkSize = isset($options['maxChunkSize'])
			? $options['maxChunkSize']
			: $this->maxChunkSize;
	}

	public function postprocess($record)
	{
		echo $record;
		echo PHP_EOL;
	}

	public function start()
	{
		$pipeDescriptor = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);

		$started = 0;
		$total = $this->dataSource->total();
		$sent = 0;
		$processes = [];

		while(1)
		{
			while(!$this->dataSource->done() && count($processes) < $this->children)
			{
				$processes[] = proc_open(
					sprintf(
						'idilic batchProcess %s %d %d'
						, escapeshellarg($this->processor)
						, ++$started
						, $this->maxRecords
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

			/*

			print $sent + array_sum($fed);
			print "/";
			print $total;
			print " Remaining\n";

			/*print ;
			print "\n";*/

			if($this->dataSource->done() && !$processes)
			{
				break;
			}
		}

		$done = TRUE;
	}
}
