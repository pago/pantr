<?php
/* 
 * Copyright (c) 2010 Patrick Gotthardt
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace pake\ext;

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
		array_unshift($args, $name);
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
			->run(self::getPearTask($pearrc));
			
		self::registerPakeTasks();
		
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
	
	private static function registerPakeTasks() {
		Pake::task('::init-pake-home', 'Creates a local pear repository in PAKE_HOME')
			->run(function() {
				$home = Pake::getHomePath();
				if($home === '') {
					Pake::writeln('No home path specified. Please set `PAKE_HOME`.', Pake::ERROR);
					return Status::FAILURE;
				}
				if(!file_exists($home)) {
					Pake::mkdirs($home);
				}
				if(!file_exists($home . DIRECTORY_SEPARATOR . '.pearrc')) {
					Pake::writeAction('init', 'Initializing pake home (test)');
					PEAR::init($home);
				}
			});
		Pake::task(':bundle', 'Execute a pake-local PEAR command')
			->usage(":bundle <command> [args]")
			->dependsOn('::init-pake-home')
			->needsRequest()
			->run(self::getPearTask(
				Pake::getHomePath() . DIRECTORY_SEPARATOR . '.pearrc',
				':bundle'));
	}
	
	private static function getPearTask($pearrc, $prefix='pear') {
		return function($req) use ($pearrc, $prefix) {
			$isTaskName = function($taskName) use($prefix) {
				if(strpos($taskName, $prefix.':') === 0) {
					$taskName = substr($taskName, 5);
					return true;
				} else if($taskName == $prefix) {
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
			if(count($args) > 0) {
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
			
			// handle the (non-standard) "pear init" task
			if($args[0] == 'init') {
				$dir = isset($args[1]) ? $args[1] : 'lib';
				PEAR::init($dir);
				return;
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
		};
	}
}