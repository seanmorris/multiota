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

	protected function finish()
	{
		$this->emit($this->existingData);

		$this->existingData && \SeanMorris\Ids\Log::debug(
			'Reducer process emitting data...'
			, $this->existingData
		);
	}

	public function process($input)
	{
		if(is_string($input))
		{
			$input = unserialize(base64_decode($input));
		}
		
		$input && $this->accumulate($input);
	}
}
