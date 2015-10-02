<?php
namespace SeanMorris\Multiota;
class Batch
{
	protected
		$processor = NULL
		, $children = 16
		, $maxRecords = 5000
		, $maxChunkSize = 4
		, $started = 0
		, $processes = []
		, $pipes = []
		, $done = FALSE
		, $fed = []
		, $return = FALSE;

	protected
		$records = 65536
	;

	public function __construct($processor)
	{
		$this->processor = $processor;
	}

	public function next()
	{
		static $i = NULL;

		if($i === NULL)
		{
			$i = $this->records;
		}

		if($i <= 0)
		{
			$this->done = TRUE;
		}

		return $i--;
	}

	public function done()
	{
		return $this->done;
	}

	public function start()
	{
		$pipeDescriptor = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);

		/*
		if(1||!$this->return)
		{
			$pipeDescriptor = array(
				0 => array('pipe', 'r'),
				1 => array('file', 'php://stdout', 'w'),
				2 => array('pipe', 'w'),
			);
		}
		*/

		while(1)
		{
			while(!$this->done() && count($this->processes) < $this->children)
			{
				$this->processes[] = proc_open(
					sprintf(
						'idilic fakeBatchProcess %s %d %d'
						, escapeshellarg($this->processor)
						, ++$this->started
						, $this->maxRecords
					)
					, $pipeDescriptor
					, $pipe
				);

				stream_set_blocking($pipe[1], FALSE);

				$this->pipes[] = $pipe;
			}

			foreach($this->processes as $childId => $child)
			{
				if(!isset($this->fed[$childId]))
				{
					$this->fed[$childId] = 0;
				}


				if($this->return)
				{
					while($output = trim(fgets($this->pipes[$childId][1])))
					{
						var_dump($output);
					}
				}

				if(!is_resource($child) || feof($this->pipes[$childId][1]))
				{
					$status = proc_get_status($child);

					if($status['running'] == FALSE)
					{
						proc_close($child);
						unset(
							$this->processes[$childId]
							, $this->pipes[$childId]
							, $this->fed[$childId]
						);
						continue;
					}

					continue;
				}

				$curChunk = 0;

				while(
					!$this->done()
					&& $this->fed[$childId] < $this->maxRecords
					&& $curChunk++ < $this->maxChunkSize
				){
					$this->fed[$childId]++;

					$feed = $this->next();

					fwrite($this->pipes[$childId][0], $feed . PHP_EOL);
				}
			}

			if($this->done() && !$this->processes)
			{
				break;
			}
		}

		$done = TRUE;
	}
}
