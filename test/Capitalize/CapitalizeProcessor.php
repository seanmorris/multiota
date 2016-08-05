<?php
namespace SeanMorris\Multiota\Test\Capitalize;
class CapitalizeProcessor extends \SeanMorris\Multiota\Processor
{
	public function process($input)
	{
		print strtoupper($input);
		print PHP_EOL;
	}
}
