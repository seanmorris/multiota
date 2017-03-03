<?php
namespace SeanMorris\Multiota;
class Reducer extends Mapper
{
	protected $existingData = [];

	protected function accumulate($data)
	{
		$this->existingData[$data->key()] = $data->value();
	}

	public function get()
	{
		return $this->existingData;
	}

	protected function finish()
	{
		$this->emit($this->existingData);
	}

	public function process($input)
	{
		fwrite(STDERR, "\tReducer accumulated input..." . print_r($input,1) . PHP_EOL);
		
		if(is_string($input))
		{
			$input = unserialize(base64_decode($input));
		}
		
		$input && $this->accumulate($input);
	}
}
