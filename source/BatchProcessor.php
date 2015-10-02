<?php
namespace SeanMorris\Multiota;
class BatchProcess
{
	protected
		$child, $max;

	public function __construct($child, $max)
	{
		$this->child = $child;
		$this->max = $max;
	}
	public function process()
	{
		$child = $this->child;
		$max = $this->max;

		//printf('Child #%d started!\n', $child);

		$stdin = fopen('php://stdin', 'r');
		stream_set_blocking($stdin, FALSE);

		$start = time();

		$loops = 0;

		$processed = 0;

		$timeout = 1;

		while($processed < $max)
		{
			$input = trim(fgets($stdin));

			if(!strlen($input))
			{
				$loops++;
				if($loops > 10000 && (time() - $start) > $timeout)
				{
					$log = sprintf(
						'Child process #%d timed out, processed %d records.\n'
						, $child
						, $processed
					);

					\SeanMorris\Ids\Log::debug($log);

					return;
				}

				continue;
			}

			print $input . PHP_EOL;

			$loops = 0;

			$processed++;
		}

		$log = sprintf(
			'Child process #%d processed %d records.\n'
			, $child
			, $processed
		);

		\SeanMorris\Ids\Log::debug($log);
	}
}