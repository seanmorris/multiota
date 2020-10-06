<?php
namespace SeanMorris\Multiota;
class Job
{
	protected
		$mapper               = 'SeanMorris\Multiota\Mapper'
		, $reducer            = NULL
		, $pool               = 'SeanMorris\Multiota\Pool'
		, $dataSource         = 'SeanMorris\Multiota\DataSource'
		, $maxChildren        = 4
		, $chunkSize          = 1
		, $childTimeout       = 8
		, $unserialize        = FALSE
		, $servers            = []
	;

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

			if(is_a($arg, 'SeanMorris\Multiota\Pool', TRUE))
			{
				$this->pool = $arg;
				continue;
			}

			if(is_a($arg, 'SeanMorris\Multiota\DataSource', TRUE))
			{
				$this->dataSource = $arg;
				continue;
			}

			if(is_array($arg))
			{
				$this->sourceArgs = $arg;

				$arg = $arg + [
					'maxChildren'          => $this->maxChildren
					, 'chunkSize'          => $this->chunkSize
					, 'childTimeout'       => $this->childTimeout
					, 'unserialize'        => $this->unserialize
					, 'servers'            => $this->servers
				];

				$this->maxChildren        = $arg['maxChildren'];
				$this->chunkSize          = $arg['chunkSize'];
				$this->childTimeout       = $arg['childTimeout'];
				$this->unserialize        = $arg['unserialize'];
				$this->servers            = $arg['servers'];

				continue;
			}
		}
	}

	public function start()
	{
		$pool = new $this->pool(
			$this->dataSource
			, $this->mapper
			, $this->reducer
			, [
				'children'       => $this->maxChildren
				, 'maxChunkSize' => $this->chunkSize
				, 'childTimeout' => $this->childTimeout
				, 'servers'      => $this->servers
			]
		);

		$pool->start();
	}
}
