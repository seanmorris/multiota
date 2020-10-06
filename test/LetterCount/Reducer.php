<?php
namespace SeanMorris\Multiota\Test\Lettercount;
class Reducer extends \SeanMorris\Multiota\Reducer
{
	protected function accumulate($data)
	{
		$key = $data->key();
		
		if(!$value = $data->value())
		{
			return;
		}

		foreach($value as $k => $v)
		{
			// fwrite(STDERR, '!!!');
			
			if(!isset($this->existingData[ $k ]))
			{
				$this->existingData[ $k ] = 0;
			}

			$this->existingData[ $k ] += $v;
		}

		ksort($this->existingData);

		// fwrite(STDERR, get_called_class() . " accumulated data..." . print_r($this->existingData, 1) . PHP_EOL);
	}

}
