<?php
namespace SeanMorris\Multiota\Test\LetterCount;
class LetterCountJob extends \SeanMorris\Multiota\Job
{
	protected
		$mapper    = 'SeanMorris\Multiota\Test\LetterCount\Mapper'
		, $reducer = 'SeanMorris\Multiota\Test\LetterCount\Reducer'
		, $maxChildren        = 8
		, $chunkSize          = 100
		, $childTimeout       = 1;
}
