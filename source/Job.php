<?php
namespace SeanMorris\Multiota;
class Job
{
	protected
		$pool
		, $dataSource
		, $processor
		, $maxChildren
		, $maxRecordsPerChild
		, $chunkSize
		, $childTimeout
	;

	public function start()
	{
		$pool = $this->pool ? $this->pool : 'SeanMorris\Multiota\Pool';
		$pool = new $pool(
			$this->dataSource
			, $this->processor
			, $this->maxChildren
			, $this->maxRecordsPerChild
			, $this->chunkSize
			, $this->childTimeout
		);

		$pool->start();
	}
}