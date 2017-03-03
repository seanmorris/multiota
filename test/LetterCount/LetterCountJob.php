<?php
namespace SeanMorris\Multiota\Test\LetterCount;
class LetterCountJob extends \SeanMorris\Multiota\Job
{
	protected
		$mapper = 'SeanMorris\Multiota\Test\LetterCount\Mapper'
		, $reducer = 'SeanMorris\Multiota\Test\LetterCount\Reducer'
		, $maxChildren = 2 
		, $maxRecordsPerChild = 128
		, $chunkSize = 32
		, $childTimeout = 1;
}
