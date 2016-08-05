<?php
namespace SeanMorris\Multiota\Test\Count;
class CountSource extends \SeanMorris\Multiota\DataSource
{
	protected $records = 2**8;

	public function fetch()
	{
		static $i = NULL, $max;
		if(!$max)
		{
			$max = $this->records;
		}

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

		return $max-$i;
	}
}
