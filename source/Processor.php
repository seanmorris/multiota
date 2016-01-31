<?php
namespace SeanMorris\Multiota;
class Processor
{
	protected
		$child, $max, $timeout;

	public function __construct($child, $max, $timeout = 0.5)
	{
		$this->child = $child;
		$this->max = $max;
		$this->timeout = $timeout;
	}

	public function process($input)
	{
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

				if($loops > 100)
				{
					if((time() - $start) > $this->timeout)
					{
						$log = sprintf(
							'Child process #%d timed out, processed %d records.'
							, $child
							, $processed
						);

						\SeanMorris\Ids\Log::debug($log);

						return;
					}

					$loops = 0;
				}

				continue;
			}

			$this->process($input);

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