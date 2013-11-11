<?php

/*

  Generic ancestor to run a multi-process worker script.
  Derive a child class that overrides two functions:

  class MyWorkers
  extends Workers {

  // fetch and return array of up to $batch items to process
  protected function getBatch($batch) {
  return array(1, 2, 3, 4, 5);
  }

  // process one item from such an array
  protected function handleItem($item) {
  $this->l("handling [$item]");
  }
  }

  Initialize it overriding zero or more of these defaults:

  $m = new MyWorkers(array(
  'workers' => 100,                    // number of worker processes to spawn
  'batch' => 1000,                    // number of items to fetch in one getBatch() call
  'chunk' => 100,                        // number of items to dispatch to one worker process
  'backoff' => 1,                        // number of seconds to wait when throttling speed
  'throttle' => 3000,                // max items to dispatch without waiting $backoff seconds
  'limit' => 1000000                // items to dispatch before exiting (if 0, run forever)
  ));

  Run it:

  $m->run();

 */

abstract class Workers {

	protected $workers = 100;		// number of worker processes to spawn
	protected $batch = 1000;		// number of items to fetch in one getBatch() call
	protected $chunk = 100;			// number of items to dispatch to one worker process
	protected $backoff = 1;			// number of seconds to wait when throttling speed
	protected $throttle = 3000;	// max items to dispatch without waiting $backoff seconds
	protected $limit = 1000000;	// items to dispatch before exiting (if 0, run forever)
	protected $quit = false;		// if true, exit gracefully
	protected $buildup = 0;			// number of items dispatched without waiting
	protected $total = 0;				// total number of items dispatched

	public function __construct($args = array()) {
		$this->l("initializing " . get_class($this));
		$this->config($args);

		declare(ticks = 1);
		pcntl_signal(SIGINT, array(&$this, "catchSignal"));
		pcntl_signal(SIGTERM, array(&$this, "catchSignal"));
	}

	public function config($args) {
		foreach ($args as $k => $v) {
			$this->l("configuring $k = $v");
			$this->{$k} = $v;
		}
	}

	public function catchSignal($signal) {
		$this->l("caught signal $signal, exiting gracefully");
		$this->quit = true;
		pcntl_alarm(10);
	}

	protected function l($msg) {
		$t = date('Y-m-d H:i:s');
		$pid = posix_getpid();
		echo "[$pid] [$t] " . rtrim($msg) . "\n";
	}

	protected function wait($secs, $why) {
		$this->l("$why: waiting {$secs} seconds");
		sleep($secs);
		$this->buildup = 0;
	}

	protected function doThrottle($count) {
		$this->l("dispatched $count items");

		$this->total += $count;
		$this->buildup += $count;

		if ($this->quit) {
			$this->l("quit flag is set: exiting");
			return true;
		}

		if ($this->limit > 0 && $this->total >= $this->limit) {
			$this->l("total $this->total items: exiting");
			return true;
		}

		if ($count < $this->batch) {
			$this->wait($this->backoff, "only $count items");
			return false;
		}

		if ($this->buildup >= $this->throttle) {
			$this->wait($this->backoff, "buildup $this->buildup items");
			return false;
		}
	}

	public function run() {
		$this->l("running " . get_class($this));

		while (true) {

			$batch = $this->getBatch($this->batch);

			$chunks = array_chunk($batch, $this->chunk, true);
			foreach ($chunks as $chunk) {
				$this->startWorker($chunk);
			}

			$count = count($batch);
			if ($this->doThrottle($count)) {
				break;
			}
		}
	}

	abstract protected function getBatch($batch);

	protected function startWorker($items) {
		if (count($items) < 1) {
			return;
		}

		if ($this->workers < 1) {
			$this->l("waiting for worker [$this->total]");
			$pid = pcntl_wait($status);
			$this->workers++;
			$this->l("worker [$pid] had exit status [$status]");
		}

		$this->l("starting worker for " . count($items) . " items");

		$pid = pcntl_fork();
		if ($pid < 0) {
			die("fork failed: $pid");
		}

		if ($pid == 0) {
			$this->l("worker handling " . count($items) . " items");
			$this->handleItems($items);
			$this->l("worker handled " . count($items) . " items");
			exit(0);
		}

		$this->l("forked worker [$pid]");
		$this->workers--;
	}

	protected function handleItems($items) {
		foreach ($items as $item) {
			$this->handleItem($item);
		}
	}

	abstract protected function handleItem($item);
}

