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
namespace pake\core;

use pake\Task;
use pgs\cli\Request;
use pgs\cli\RequestContainer;

class Application {
	private $defaultTaskName;
	private $tasks, $pakefileFactory, $taskExecutorFactory;
	
	public function __construct(
			TaskRepository $tasks,
			PakefileFactory $pakefileFactory,
			TaskExecutorFactory $taskExecutorFactory) {
		$this->tasks = $tasks;
		$this->pakefileFactory = $pakefileFactory;
		$this->taskExecutorFactory = $taskExecutorFactory;
	}
	
	public function setDefaultTask($defaultTaskName) {
		$this->defaultTaskName = $defaultTaskName;
	}
	
	private static function getRequestContainer(array $args) {
		$req = new RequestContainer(new Request($args));
		$req->registerParameter('file', 'The name of the pakefile', 'f');
		$req->registerParameter('ignore-failure',
			'If specified, the task will be executed even if some or all of its dependencies fail');
		return $req;
	}
	
	public static function printOptions() {
		$req = self::getRequestContainer(array());
		$req->printOptions();
	}
	
	public function run() {
		// pure args
		$args = func_get_args();
		if(count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		}
		
		// this request
		$req = self::getRequestContainer($args);
		$taskName = $req[0];
		
		// load local pakefile
		$pakefile = $this->pakefileFactory->getPakefile($req['file'] ?: 'pakefile');
		if(!is_null($pakefile)) {
			$pakefile->load();
		} else if($taskName[0] != ':') { // not a global task
			\pake\Pake::writeln('No pakefile found.', \pake\Pake::WARNING);
		}
		
		// prepare task
		$task = $this->getTask($taskName);
		if(is_null($task)) {
			throw new NoTaskFoundException($taskName);
		}
		$taskArgs = $this->getTaskArgs($taskName, $args);
		
		// and now - finally - execute it
		$executor = $this->taskExecutorFactory->get($taskArgs);
		$executor->execute($task, $req['ignore-failure'] ?: true);
	}
	
	private function getTaskArgs($taskName, $args) {
		// drop everything before task name
		while(count($args) > 0 && $taskName != $args[0]) {
			array_shift($args);
		}
		// drop the task name itself
		array_shift($args);
		return new RequestContainer(new Request($args));
	}
	
	private function getTask($taskName) {
		if(isset($this->tasks[$taskName])) {
			return $this->tasks[$taskName];
		} else if(strpos($taskName, ':') !== false) {
			// has category?
			list($category, $taskName) = explode(':', $taskName);
			if(isset($this->tasks[$category])) {
				return $this->tasks[$category];
			}
		}
		
		// choose default
		if(!is_null($this->defaultTaskName) && isset($this->tasks[$this->defaultTaskName])) {
			return $this->tasks[$this->defaultTaskName];
		}
		
		// or at least help
		if(isset($this->tasks['help'])) {
			return $this->tasks['help'];
		}
		
		// no default task, no help task -> PANIC
		Pake::writeln('Could not find any task to execute. Aborting.', Pake::ERROR);
		return null;
	}
}