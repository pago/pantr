<?php
namespace com\pagosoft\pake;

use com\pagosoft\cli\Request;

class Executor implements \ArrayAccess {
	private $request;
	private $tasks = array();
	private $defaultTask = null;
	private $executedTasks = array();
	private $factory;
	
	public function __construct($args, PakefileFactory $factory) {
		$this->request = new Request($args);
		$this->factory = $factory;
		
		$this->registerParameter('file', 'The name of the pakefile', 'f');
	}
	
	public function printPakeInfo() {
		// display pake info
		$copySpan = '2010';
		$year = date('Y');
		if($year != '2010') {
			$copySpan .= '-'.$year;
		}
		Pake::writeln(
			'Pake ' . Pake::VERSION . ' (c) '.$copySpan.' Patrick Gotthardt',
			Pake::INFO)->nl();
	}
	
	public function registerTask(Task $task) {
		$this->tasks[$task->getName()] = $task;
	}
	
	public function alias($taskName, $alias) {
		$task = $this->tasks[$taskName];
		$this->tasks[$alias] = $task;
	}
	
	public function setDefault($task) {
		$this->defaultTask = $task;
	}
	
	public function loadPakefile() {
		$this->loadStandardPakefile();
		if(isset($this['file'])) {
			$name = $this['file'];
		} else {
			$name = 'pakefile';
		}
		$pakefile = $this->factory->getPakefile($name);
		$pakefile->load();
	}
	
	private function loadStandardPakefile() {
		$pakefile = new Pakefile(__DIR__ . '/std_tasks.php');
		$pakefile->load();
	}
	
	private function getTaskName() {
		if(isset($this[0])) {
			return $this[0];
		}
		return $this->defaultTask;
	}
	
	public function getTasks() {
		return $this->tasks;
	}

	public function __invoke($taskName=null) {
		$taskName = $taskName ?: $this->getTaskName();
		// check out real task name (in case $taskName is an alias)
		$task = $this->tasks[$taskName];
		if($taskName != $task->getName()) {
			$taskName = $task->getName();
		}
		// only run tasks that have not been executed before
		if(!$this->hasBeenExecuted($taskName)) {	
			$task($this);
			$this->executedTasks[$taskName] = true;
		}
	}

	private function hasBeenExecuted($taskName) {
		return isset($this->executedTasks[$taskName]);
	}
	
	private $requestAliasTable = array();
	private $descriptionTable = array();
	public function registerParameter($long, $description='', $short=null) {
		if(!is_null($short)) {
			$this->requestAliasTable[$long] = $short;
			$this->requestAliasTable[$short] = $long;
		}
		$this->descriptionTable[$long] = $description;
	}
	
	public function printOptions() {
		if(count($this->descriptionTable) > 0) {
			Pake::nl()->writeln('Options:', 'BOLD');
			foreach($this->descriptionTable as $long => $desc) {
				// check if there is a short version
				if(isset($this->requestAliasTable[$long])) {
					$short = $this->requestAliasTable[$long];
					Pake::writeOption($short, $long, $desc);
				} else {
					Pake::writeOption($long, $desc);
				}
			}
		}
	}
	
	// ArrayAccess implementation
	public function get($key) {
		if(isset($this->request[$key])) {
			return $this->request[$key];
		} elseif(isset($this->requestAliasTable[$key])) {
			// translate key
			$key = $this->requestAliasTable[$key];
			if(isset($this->request[$key])) {
				return $this->request[$key];
			}
		}
		return null;
	}

	public function offsetExists($offset) {
		if(isset($this->request[$offset])) {
			return true;
		} elseif(isset($this->requestAliasTable[$offset])) {
			// translate offset
			$offset = $this->requestAliasTable[$offset];
			if(isset($this->request[$offset])) {
				return true;
			}
		}
		return false;
	}

	public function offsetGet($offset) {
		return $this->get($offset);
	}

	public function offsetSet($offset, $value) {
		throw new Exception('Controller is not to be written to');
	}

	public function offsetUnset($offset) {
		throw new Exception('Controller is not to be written to');
	}
}