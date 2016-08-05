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
	}

	protected function childCommand($started)
	{
		$serverCount = count($this->servers);

		if(!$serverCount)
		{
			throw new \Exception('No servers defined for remote job.');
		}

		$server = $this->servers[$started % $serverCount];

		fwrite(STDERR, 'Connecting to ' . $server . PHP_EOL);

		$command = sprintf(
			'ssh %s \'idilic batchProcess %s %d %d %f\''
			, escapeshellarg($server)
			, escapeshellarg(addslashes(addslashes($this->processor)))
			, $started
			, $this->maxRecords
			, $this->childTimeout
		);

		fwrite(STDERR, 'Running ' . $command . PHP_EOL);

		return $command;
	}
}
