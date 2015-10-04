<?php
namespace SeanMorris\Multiota;
class DataSource
{
	protected
		$records = 2**8
		, $done = FALSE
	;

	public function total()
	{
		return $this->records;
	}

	public function done()
	{
		return $this->done;
	}

	public function fetch()
	{
		static $i = NULL;

		if($this->done)
		{
			return;
		}

		if($i === NULL)
		{
			$i = $this->records;
		}

		$i--;

		if($i == 0)
		{
			$this->done = TRUE;
		}

		return $i;
	/*
	public function fetch()
	{
		$this->done = TRUE;
	*/
	}
}