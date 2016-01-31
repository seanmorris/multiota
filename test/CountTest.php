<?php
namespace SeanMorris\Multiota\Test;
class CountTest extends \UnitTestCase
{
	public function testCountJob()
	{
		$job = new \SeanMorris\Multiota\Test\Count\CountJob;
		$job->start();
	}
}
