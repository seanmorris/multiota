<?php
namespace SeanMorris\Multiota;
class Processor
{
	protected
		$child, $max, $timeout, $processed, $start, $done = FALSE;

	public function __construct($child, $max, $timeout = 0.5)
	{
		$this->child = $child;
		$this->max = $max;
		$this->timeout = $timeout;

		$log = sprintf(
			'Child process #%d starting, processing %d record max, %ds timeout.'
			, $child
			, $this->max
			, $this->timeout
		);

		fwrite(STDERR, $log . PHP_EOL);

		\SeanMorris\Ids\Log::debug($log);
	}

	public function process($input)
	{
		return $input;
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

		while($this->processed < $max && !$this->done)
		{
			$input = trim(fgets($stdin));

			$loops++;

			if(strlen($input))
			{
				$log = sprintf(
					'Child process #%d got input. (%s)'
					, $child
					, $input
				);

				//\SeanMorris\Ids\Log::debug($log);
				$this->resetTimeout();
				$loops = 0;
			}

			if($this->timeout > 0 && $loops > 100)
			{
				if($this->timeout())
				{
					$this->process(FALSE);

					$log = sprintf(
						'Child process #%d timed out after %ds, processed %d records.'
						, $child
						, $this->timeout
						, $this->processed
					);

					fwrite(STDERR, $log . PHP_EOL);

					\SeanMorris\Ids\Log::debug($log);

					break;
				}

				$loops = 0;
			}

			if(!strlen($input))
			{
				$this->process(NULL);
				continue;
			}

			fwrite(STDOUT, $this->process($input) . PHP_EOL);

			$this->processed++;
		}

		$this->process(FALSE);

		$log = sprintf(
			'Child process #%d processed %d records. Exiting...'
			, $child
			, $this->processed
		);

		fwrite(STDERR, $log . PHP_EOL);

		\SeanMorris\Ids\Log::debug($log);
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
		if(!$this->start)
		{
			$this->start = time();
		}

		return (time() - $this->start) > $this->timeout;
	}
}
