<?php
namespace SeanMorris\Multiota;
class Mapper
{
	protected
		$child
		, $max
		, $timeout
		, $processed
		, $start
		, $done = FALSE
		, $shortClass;

	public function __construct($child = -1, $max = -1, $timeout = -1)
	{
		$reflectionClass       = new \ReflectionClass(get_called_class());
		$this->shortClass      = $reflectionClass->getShortName();
		$this->child           = $child;
		$this->max             = $max;
		$this->timeout         = $timeout;

		$log = sprintf(
			'%s process #%d starting, processing %d record max, %ds timeout'
			, $this->shortClass
			, $child
			, $this->max
			, $this->timeout
		);

		fwrite(STDERR, $log . PHP_EOL);
	}

	public function process($input)
	{
		return $input;
	}

	public function emit($record)
	{
		fwrite(STDOUT, base64_encode(serialize($record)) . PHP_EOL);
	}

	public function reduce($key, $record)
	{
		$this->emit(new ReduceRecord($key, $record));
	}

	public function spin()
	{
		$child = $this->child;
		$max = $this->max;

		$stdin = fopen('php://stdin', 'r');
		stream_set_blocking($stdin, FALSE);

		$this->start = time();
		$loops = 0;
		$this->processed = 0;

		$timedout = FALSE;

		while(($max <= 0 || $this->processed < $max) && !$this->done)
		{
			$loops++;

			if($input = trim(fgets($stdin)))
			{
				$input = unserialize(base64_decode($input));

				$log = sprintf(
					"%s process #%d got input.\n%s"
					, $this->shortClass
					, $child
					, print_r($input, 1)
				);

				$this->processed++;
				fwrite(STDERR, $log . PHP_EOL);

				$this->resetTimeout();
				$loops = 0;

				if(!is_a($input, 'SeanMorris\Multiota\ReduceRecord'))
				{
					//$input = new ReduceRecord(uniqid(), $input);
				}

				if(($output = $this->process($input)) !== NULL)
				{
					$this->emit($output);
				}
			}

			if($this->timeout > 0 && $loops > 1000)
			{
				if($this->timeout())
				{
					$log = sprintf(
						'%s process #%d timed out after %ds, processed %d records.'
						, $this->shortClass
						, $child
						, $this->timeout
						, $this->processed
					);

					fwrite(STDERR, $log . PHP_EOL);

					//\SeanMorris\Ids\Log::debug($log);

					$timedout = TRUE;

					break;
				}

				$loops = 0;
			}
		}

		$this->finish();

		$log = sprintf(
			'%s process #%d processed %d records. Exiting...'
			, $this->shortClass
			, $child
			, $this->processed
		);

		$timedout || fwrite(STDERR, $log . PHP_EOL);

		//$timedout || \SeanMorris\Ids\Log::debug($log);
	}

	protected function resetTimeout($timeout = NULL)
	{
		$this->start = time();

		if($timeout)
		{
			$this->timeout = $timeout;
		}
	}

	protected function timeout()
	{
		if($this->timeout <= 0)
		{
			return FALSE;
		}

		if(!$this->start)
		{
			$this->start = time();
		}

		return (time() - $this->start) > $this->timeout;
	}

	public function processed()
	{
		return $this->processed;
	}

	protected function finish()
	{

	}
}
