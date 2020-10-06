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
		// fwrite(
		// 	STDERR
		// 	, "Reducer complete... "
		// 		. print_r($this->existingData, 1)
		// 		. PHP_EOL
		// );
		
		$this->emit($this->existingData);
	}

	public function process($input)
	{
		if(is_string($input))
		{
			$input = unserialize(base64_decode($input));
		}
		
		$this->accumulate($input);

		// fwrite(STDERR,
		// 	"Reducer accumulated data..." . print_r($this->existingData, 1) . PHP_EOL
		// 	// , $input
		// 	// , $this->existingData
		// );
	}
}
