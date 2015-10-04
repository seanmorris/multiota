<?php
namespace SeanMorris\Multiota;
class Processor
{
	protected
		$child, $max, $timeout;

	public function __construct($child, $max, $timeout = 1)
	{
		$this->child = $child;
		$this->max = $max;
		$this->timeout = $timeout;
	}

	public function process($input)
	{
		print $input;
		print PHP_EOL;
	}

	public function spin()
	{
		$child = $this->child;
		$max = $this->max;

		$stdin = fopen('php://stdin', 'r');
		stream_set_blocking($stdin, FALSE);

		$start = time();
		$loops = 0;
		$processed = 0;

		while($processed < $max)
		{
			$input = trim(fgets($stdin));

			if(!strlen($input))
			{
				$loops++;
				if($loops > 10000 && (time() - $start) > $this->timeout)
				{
					$log = sprintf(
						'Child process #%d timed out, processed %d records.'
						, $child
						, $processed
					);

					\SeanMorris\Ids\Log::debug($log);

					return;
				}

				continue;
			}

			$this->process($input);

			$loops = 0;

			$processed++;
		}

		$log = sprintf(
			'Child process #%d processed %d records.'
			, $child
			, $processed
		);

		\SeanMorris\Ids\Log::debug($log);
	}
}