<?php
namespace SeanMorris\Multiota\Test\Count;
class CountJob extends \SeanMorris\Multiota\Job
{
	protected
		$dataSource = 'SeanMorris\Multiota\Test\Count\CountSource'
		, $processor = 'SeanMorris\Multiota\Test\Count\CountProcessor'
		, $maxChildren = 16
		, $maxRecordsPerChild = 1024
		, $chunkSize = 64
		, $childTimeout = 1;
}
