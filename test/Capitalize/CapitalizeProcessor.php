<?php
namespace SeanMorris\Multiota\Test\Capitalize;
class CapitalizeProcessor extends \SeanMorris\Multiota\Processor
{
	public function process($input)
	{
		return strtoupper($input);
	}
}
