<?php
namespace SeanMorris\Multiota;
class ReduceRecord
{
	protected $key, $value;

	public function __construct($key, $value)
	{
		$this->key   = $key;
		$this->value = $value;
	}

	public function key()
	{
		return $this->key;
	}

	public function value()
	{
		return $this->value;
	}
}
