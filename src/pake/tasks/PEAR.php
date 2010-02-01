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
		Pake::mkdirs($dir);
		Pake::writeAction('pear:init', $dir);
		$argv = array('', 'generate-project', $dir);
		$runner = new \Pearanha_Runner_ProjectRunner();
		$runner->run($argv);
	}
	
	public static function registerTasks($pearrc='lib/.pearrc') {
		Pake::task('pear', 'Execute a local PEAR command')
			->usage("pear [--pearrc] <command> [args]\n"
				. 'pake pear:<command> [args]')
			->option('pearrc')
				->desc('Define the .pearrc file to use. Defaults to lib/.pearrc and .pearrc')
			->run(function($req) use ($pearrc) {
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
				// drop pearrc option
				if(isset($req['pearrc'])) {
					$pearrc = $req['pearrc'];
					array_shift($args);
				}
				
				// try to find a .pearrc in current directory
				// this is typical if there is no pakefile
				if(!isset($req['pearrc']) && !file_exists($pearrc) && file_exists('.pearrc')) {
					$pearrc = '.pearrc';
				}
				if(!file_exists($pearrc)) {
					throw new \Exception('Could not find .pearrc');
				}
				$pear = new PEAR($pearrc);
				try {
					$pear($args);
				} catch(\Exception $e) {
					Pake::writeln($e->getMessage(), Pake::WARNING);
				}
			});
		
		Pake::task('pear:init', 'Creates a local pear repository')
			->usage('pear:init [directory]')
			->desc('Creates a local pear repository in [directory|PARAMETER]'
				. ' or [lib|PARAMETER] if no directory was specified.')
			->needsRequest()
			->run(function($req) {
				$dir = isset($req[0]) ? $req[0] : 'lib';
				PEAR::init($dir);
			});
	}
}