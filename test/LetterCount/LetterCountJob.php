<?php
namespace SeanMorris\Multiota\Test\LetterCount;
class LetterCountJob extends \SeanMorris\Multiota\Job
{
	protected
		$mapper    = 'SeanMorris\Multiota\Test\LetterCount\Mapper'
		, $reducer = 'SeanMorris\Multiota\Test\LetterCount\Reducer'
		, $maxChildren        = 5
		, $maxRecordsPerChild = 100
		, $chunkSize          = 1
		, $childTimeout       = 1;
}
