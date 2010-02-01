<?php
namespace pake\tasks;

use pake\Pake;

class PEAR {
	private $runner;
	public function __construct($pearrc='lib/.pearrc') {
		$this->runner = new \Pearanha_Runner_PearRunner($pearrc);
	}
	
	public function __invoke($args) {
		// the empty arg is required because PearRunner drops the first
		// argument
		array_unshift($args, '');
		$this->runner->run($args);
	}
	
	public function __call($name, $args) {
		// fix name
		$name = str_replace('_', '-', $name);
		// fix args
		if(count($args) == 1 && is_string($args[0])) {
			$args = explode(' ', $args[0]);
		}
		array_unshift($name);
		$this($args);
	}
	
	public static function init($dir='lib') {
		$argv = array('', 'generate-project', $dir);
		$runner = new \Pearanha_Runner_ProjectRunner();
		$runner->run($argv);
		Pake::writeAction('pear:init', $dir);
	}
	
	public static function registerTasks($pearrc='lib/.pearrc') {
		Pake::task('pear', 'Execute a local PEAR command')
			->usage("pear <command> [args]\npake pear:<command> [args]")
			->run(function() use ($pearrc) {
				$isTaskName = function($taskName) {
					if(strpos($taskName, 'pear:') === 0) {
						$taskName = substr($taskName, 5);
						return true;
					} else if($taskName == 'pear') {
						return true;
					}
					return false;
				};
				$args = $_SERVER['argv'];
				array_shift($args);

				// drop everything before task name
				while(count($args) > 0 && !$isTaskName($args[0])) {
					array_shift($args);
				}
				// drop the task name itself
				if(count($args > 0)) {
					$taskName = $args[0];
					if(strpos($taskName, 'pear:') === 0) {
						// change pear:foo to foo
						$taskName = substr($taskName, 5);
						$args[0] = $taskName;
					} else {
						// remove 'pear' from stack
						$taskName = array_shift($args);
					}
				}
				
				$pear = new PEAR($pearrc);
				$pear($args);
			});
		
		Pake::task('pear:init', 'Creates a local pear repository')
			->usage('pear:init [--dir=lib]')
			->needsRequest()
			->option('dir')
				->desc('specifies the directory for the pear repository')
			->run(function($req) {
				$dir = isset($req['dir']) ? $req['dir'] : 'lib';
				PEAR::init($dir);
			});
	}
}