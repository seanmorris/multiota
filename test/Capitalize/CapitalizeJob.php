<?php
namespace SeanMorris\Multiota\Test\Capitalize;
class CapitalizeJob extends \SeanMorris\Multiota\Job
{
	protected
		$mapper = 'SeanMorris\Multiota\Test\Capitalize\CapitalizeProcessor'
		, $maxRecordsPerChild = 1000000
		, $childTimeout       = 5
		, $maxChildren        = 16
		, $chunkSize          = 128;
}
