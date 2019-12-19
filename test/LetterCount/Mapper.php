<?php
namespace SeanMorris\Multiota\Test\Lettercount;
class Mapper extends \SeanMorris\Multiota\Mapper
{
	public function process($record)
	{
		fwrite(STDERR, "\tProcessing...\n\t" . print_r($record, 1) . PHP_EOL . PHP_EOL);

		$record = str_split($record->value());
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
		
		fwrite(STDERR, "\tEmitting..." . PHP_EOL);

		$this->reduce($output);
	}
}
