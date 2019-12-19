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

	public function finish()
	{
		fwrite(STDERR, "\tReducer finished... " . print_r($this->existingData, 1) . PHP_EOL);
		$this->emit($this->existingData);
	}

	public function process($input)
	{
		if(is_string($input))
		{
			$input = unserialize(base64_decode($input));
		}
		
		fwrite(STDERR, "\tReducer accumulated input... " . print_r($input,1) . PHP_EOL);

		$input && $this->accumulate($input);
	}
}
