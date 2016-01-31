<?php
namespace SeanMorris\Multiota;
class Job
{
	protected
		$dataSource
		, $processor
		, $maxChildren
		, $maxRecordsPerChild
		, $chunkSize
		, $childTimeout
	;

	public function start()
	{
		$pool = new Pool(
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