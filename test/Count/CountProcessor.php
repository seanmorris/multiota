<?php
namespace SeanMorris\Multiota\Test\Count;
class CountProcessor extends \SeanMorris\Multiota\Processor
{
	public function process($input)
	{
		//sleep((rand(0,5)*0.25));
		print $input;
		print PHP_EOL;
	}
}
