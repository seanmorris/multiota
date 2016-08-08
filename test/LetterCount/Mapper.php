<?php
namespace SeanMorris\Multiota\Test\Lettercount;
class Mapper extends \SeanMorris\Multiota\Mapper
{
	public function process($record)
	{
		$record = str_split($record);
		$output = [];

		while($record)
		{
			$letter = strtoupper(array_shift($record));

			if(!preg_match('/[A-Z]/', $letter))
			{
				continue;
			}

			if(!isset($output[$letter]))
			{
				$output[$letter] = 0;
			}

			$output[$letter]++;
		}

		foreach($output as $letter => $count)
		{
			$this->reduce($letter, $count);
		}
	}
}
