<?php
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
		
		// load local pakefile
		$pakefile = $this->pakefileFactory->getPakefile($req['file'] ?: 'pakefile');
		if(!is_null($pakefile)) {
			$pakefile->load();
		} else {
			\pake\Pake::writeln('No pakefile found.', \pake\Pake::WARNING);
		}
		
		// prepare task
		$taskName = $req[0];
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
		} else if(strpos($taskName, ':') !== 0) {
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