<?php
namespace SeanMorris\Multiota;
class Job
{
	protected
		$processor            = 'SeanMorris\Multiota\Processor'
		, $pool               = 'SeanMorris\Multiota\Pool'
		, $dataSource         = 'SeanMorris\Multiota\DataSource'
		, $maxChildren        = 8
		, $maxRecordsPerChild = 128
		, $chunkSize          = 16
		, $childTimeout       = 2
		, $unserialize        = FALSE
		, $servers            = []
	;

	public function __construct(...$args)
	{
		foreach($args as $arg)
		{
			if(is_a($arg, 'SeanMorris\Multiota\Processor', TRUE))
			{
				$this->processor = $arg;
			}

			if(is_a($arg, 'SeanMorris\Multiota\Pool', TRUE))
			{
				$this->pool = $arg;
			}

			if(is_a($arg, 'SeanMorris\Multiota\DataSource', TRUE))
			{
				$this->dataSource = $arg;
			}

			if(is_array($arg))
			{
				$this->sourceArgs = $arg;

				$arg = $arg + [
					'maxChildren'          => $this->maxChildren
					, 'maxRecordsPerChild' => $this->maxRecordsPerChild
					, 'chunkSize'          => $this->chunkSize
					, 'childTimeout'       => $this->childTimeout
					, 'unserialize'        => $this->unserialize
					, 'servers'            => $this->servers
				];

				$this->maxChildren        = $arg['maxChildren'];
				$this->maxRecordsPerChild = $arg['maxRecordsPerChild'];
				$this->chunkSize          = $arg['chunkSize'];
				$this->childTimeout       = $arg['childTimeout'];
				$this->unserialize        = $arg['unserialize'];
				$this->servers            = $arg['servers'];
			}
		}
	}

	public function start()
	{
		$pool = new $this->pool(
			$this->dataSource
			, $this->processor
			, [
				'children'       => $this->maxChildren
				, 'maxRecords'   => $this->maxRecordsPerChild
				, 'maxChunkSize' => $this->chunkSize
				, 'childTimeout' => $this->childTimeout
				, 'servers'      => $this->servers
			]
		);

		$pool->start();
	}
}
