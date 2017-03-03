<?php
namespace SeanMorris\Multiota\Test\Lettercount;
class Reducer extends \SeanMorris\Multiota\Reducer
{
	protected function accumulate($data)
	{
		if(!isset($this->existingData[$data->key()]))
		{
			$this->existingData[$data->key()] = 0;
		}

		$this->existingData[$data->key()] = $data->value() + $this->existingData[$data->key()];

		ksort($this->existingData);
	}

}
