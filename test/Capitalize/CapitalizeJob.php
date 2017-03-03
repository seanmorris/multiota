<?php
namespace SeanMorris\Multiota\Test\Capitalize;
class CapitalizeJob extends \SeanMorris\Multiota\Job
{
	protected
		$mapper = 'SeanMorris\Multiota\Test\Capitalize\CapitalizeProcessor'
		, $maxChildren = 8
		, $maxRecordsPerChild = 128
		, $chunkSize = 32
		, $childTimeout = 1;
}
