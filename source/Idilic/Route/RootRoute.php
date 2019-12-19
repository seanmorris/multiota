<?php
namespace SeanMorris\Multiota\Idilic\Route;
/**
 * Application core library.
 * 	Passing a package with no command will return the root directory of that package.
 * Switches:
 * 	-d= or --domain=
 * 		Set the domain for the current command.
 */
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function multiotaJob($router)
	{
		$args = $router->path()->consumeNodes();

		$job = array_shift($args);

		$job = new $job;
		$job->start();

	}

	public function multiotaChild($router)
	{
		$args = $router->path()->consumeNodes();

		$processor = array_shift($args);
		$child     = array_shift($args);
		$max       = array_shift($args);
		$timeout   = array_shift($args);

		$processor = new $processor($child, $max, $timeout);
		$processor->spin();
	}

	/** Multiota specific. */

	public function batch($router)
	{
		$job = new \SeanMorris\Multiota\Test\Count\CountJob;
		$job->start();
		/*
		$pool = new \SeanMorris\Multiota\Pool(
			'\SeanMorris\Multiota\DataSource'
			, '\SeanMorris\Multiota\Processor'
		);

		$pool->start();
		*/
		exit;
	}

	/** Multiota specific. */

	public function batchProcess($router)
	{
		$processor = $router->path()->consumeNode();
		$child = $router->path()->consumeNode();
		$max = $router->path()->consumeNode();
		$timeout = $router->path()->consumeNode();

		$processor = new $processor($child, $max, $timeout);

		$processor->spin();
	}

	/** Multiota specific. */

	public function countJob()
	{
		$job = new \SeanMorris\Multiota\Test\Count\CountJob(
			'SeanMorris\Multiota\RemotePool'
			, [
				'servers' => ['localhost']
			]
		);
		$job->start();
	}

	/** Multiota specific. */

	public function capitalizeJob()
	{
		$job = new \SeanMorris\Multiota\Job(
			'SeanMorris\Multiota\Test\Capitalize\CapitalizeProcessor'
			, 'SeanMorris\Multiota\RemotePool'
			, [
				'servers' => ['thewhtrbt.com', 'buzzingbeesalon.com']
			]
		);
		$job->start();
	}

	/** Multiota specific. */

	public function letterCountMap()
	{
		$job = new \SeanMorris\Multiota\Job(
			'SeanMorris\Multiota\Test\LetterCount\Mapper'
			//, 'SeanMorris\Multiota\RemotePool'
			, [
				'servers' => ['seantop', 'localhost']
			]
		);
		$job->start();
	}

	/** Multiota specific. */

	public function letterCountReduce()
	{
		$job = new \SeanMorris\Multiota\Job(
			'SeanMorris\Multiota\Test\LetterCount\Reducer'
			//, 'SeanMorris\Multiota\RemotePool'
			, [
				'servers' => ['seantop', 'localhost']
			]
		);
		$job->start();
	}
}