<?php
namespace SeanMorris\Multiota\Test\Lettercount;
class Reducer extends \SeanMorris\Multiota\Reducer
{
	protected function accumulate($data)
	{
		// fwrite(STDERR, "\tReducer accumulated key..." . print_r($data->key(), 1) . PHP_EOL);
		// fwrite(STDERR, "\tReducer accumulated value..." . print_r($data->value(), 1) . PHP_EOL);

		// if(!isset($this->existingData[$data->key()]))
		// {
		// 	$this->existingData[$data->key()] = 0;
		// }

		// $this->existingData[$data->key()] = $data->value() + $this->existingData[$data->key()];

		// ksort($this->existingData);

		foreach($data->value() as $k => $v)
		{
			if(!isset($this->existingData[ $k ]))
			{
				$this->existingData[ $k ] = 0;
			}

			$this->existingData[ $k ] += $v;
		}

		ksort($this->existingData);
	}

}
