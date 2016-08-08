<?php
namespace SeanMorris\Multiota\Test\Capitalize;
class CapitalizeProcessor extends \SeanMorris\Multiota\Mapper
{
	public function process($input)
	{
		return strtoupper($input);
	}
}
