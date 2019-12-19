<?php
namespace SeanMorris\Multiota\Test\Capitalize;
class CapitalizeProcessor extends \SeanMorris\Multiota\Mapper
{
	public function process($input)
	{
		sleep(rand(0,10)/10);
		return strtoupper($input);
	}
}
