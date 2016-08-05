<?php
namespace SeanMorris\Multiota\Test\Count;
class CountJob extends \SeanMorris\Multiota\Job
{
	protected
		$dataSource = 'SeanMorris\Multiota\Test\Count\CountSource'
		, $processor = 'SeanMorris\Multiota\Test\Count\CountProcessor'
		, $maxChildren = 4
		, $maxRecordsPerChild = 100
		, $chunkSize = 10
		, $childTimeout = 2;
}
