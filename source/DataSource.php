<?php
namespace SeanMorris\Multiota;
class DataSource
{
	protected
		$records = NULL
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
		$line = fgets(STDIN);

		if(!feof(STDIN) && trim($line))
		{
			return $line;
		}

		$this->done = TRUE;

		return FALSE;
	}
}
