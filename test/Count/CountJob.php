<?php
namespace SeanMorris\Multiota\Test\Count;
class CountJob extends \SeanMorris\Multiota\Job
{
	protected
		$dataSource = 'SeanMorris\Multiota\Test\Count\CountSource'
		, $processor = 'SeanMorris\Multiota\Test\Count\CountProcessor'
		, $maxChildren = 8
		, $maxRecordsPerChild = 128
		, $chunkSize = 32
		, $childTimeout = 1;
}
