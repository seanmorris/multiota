<?php
namespace SeanMorris\Multiota\Test\Count;
class CountProcessor extends \SeanMorris\Multiota\Mapper
{
	public function process($input)
	{
		//sleep((rand(0,5)*0.25));
		print $input;
		print PHP_EOL;
	}
}
