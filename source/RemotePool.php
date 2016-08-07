<?php
namespace SeanMorris\Multiota;
class RemotePool extends Pool
{
	protected
		$servers = []
	;

	public function __construct(...$args){
		parent::__construct(...$args);

		if(isset($this->sourceArgs['servers']))
		{
			$this->servers = $this->sourceArgs['servers'];
		}
		else
		{
			throw new \Exception('No servers defined for remote job.');
		}

		fwrite(STDERR, "Connecting to\n\t" . implode("\n\t", $this->servers) . PHP_EOL);
	}

	protected function childCommand($started)
	{
		$serverCount = count($this->servers);
		$server = $this->servers[$started % $serverCount];
		$command = sprintf(
			'ssh %s \'idilic batchProcess %s %d %d %f\''
			, escapeshellarg($server)
			, escapeshellarg(addslashes(addslashes($this->processor)))
			, $started
			, $this->maxRecords
			, $this->childTimeout
		);

		return $command;
	}
}
