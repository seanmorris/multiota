<?php
namespace SeanMorris\Multiota;
class DataSource
{
	protected
		$handle
		, $unserialize
	;

	public function __construct($handle = STDIN, $unserialize = FALSE)
	{
		$this->handle      = STDIN;
		$this->unserialize = $unserialize;
	}

	public function total()
	{
		return NULL;
	}

	public function done()
	{
		return feof($this->handle);
	}

	public function fetch()
	{
		$line = $this->read();

		if(!$this->done())
		{
			return $line;
		}

		return FALSE;
	}

	protected function read()
	{
		if($this->unserialize)
		{
			return unserialize(base64_decode(trim(fgets($this->handle))));
		}

		return trim(fgets($this->handle));
	}
}
