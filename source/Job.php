<?php
namespace SeanMorris\Multiota;
class Job
{
	protected
		$processor            = 'SeanMorris\Multiota\Processor'
		, $pool               = 'SeanMorris\Multiota\Pool'
		, $dataSource         = 'SeanMorris\Multiota\DataSource'
		, $maxChildren        = 10
		, $maxRecordsPerChild = 100
		, $chunkSize          = 10
		, $childTimeout       = 2
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
					'maxChildren'          => 16
					, 'maxRecordsPerChild' => 128
					, 'chunkSize'          => 32
					, 'childTimeout'       => 2
					, 'servers'            => []
				];

				$this->maxChildren        = $arg['maxChildren'];
				$this->maxRecordsPerChild = $arg['maxRecordsPerChild'];
				$this->chunkSize          = $arg['chunkSize'];
				$this->childTimeout       = $arg['childTimeout'];
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
