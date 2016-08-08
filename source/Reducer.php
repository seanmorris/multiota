<?php
namespace SeanMorris\Multiota;
class Reducer extends Mapper
{
	protected $existingData = [];

	protected function accumulate($data)
	{
		$this->existingData[] = $data;
	}

	public function get()
	{
		return $this->existingData;
	}

	public function finish()
	{
		foreach($this->existingData as $key => $value)
		{
			$this->reduce($key, $value);
		}

		\SeanMorris\Ids\Log::debug($this->existingData);
	}

	public function process($input)
	{
		$input && $this->accumulate($input);
	}
}
