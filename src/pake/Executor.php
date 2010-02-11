<?php
namespace pake;

use pgs\cli\Request;

/**
 * The Executor is responsible for invoking tasks. It makes sure that a task
 * is executed exactly once and that all of its dependencies are invoked before
 * the task itself is run.
 */
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
		//$this->registerParameter('global-tasks', 'Show global pake tasks only', 'g');
	}
	
	/**
	 * Prints the version number for pake.
	 */
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
	
	/**
	 * Register a task for this executor.
	 */
	public function registerTask(Task $task) {
		$this->tasks[$task->getName()] = $task;
	}
	
	/**
	 * Registers a task under a different name.
	 * An aliased task can be used as a dependency and it can
	 * be invoked using the alias.
	 */
	public function alias($taskName, $alias) {
		$task = $this->tasks[$taskName];
		$this->tasks[$alias] = $task;
	}
	
	/**
	 * Specifies the task that should be run if the user has not selected
	 * a task.
	 */
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
		// do not load pakefile if a pake-command is executed
		if(0 !== strpos($this[0], 'pake:')) {
			try {
				$pakefile = $this->factory->getPakefile($name);
				$pakefile->load();
			} catch(\Exception $ex) {
				Pake::writeln($ex->getMessage(), Pake::WARNING)->nl();
			}
		}
	}
	
	private function loadStandardPakefile() {
		$pakefile = new Pakefile(__DIR__ . '/std_tasks.php');
		$pakefile->load();
	}
	
	private function getTaskName() {
		if(isset($this[0])) {
			$taskName = $this[0];
			// check out real task name (in case $taskName is an alias)
			if(!isset($this->tasks[$taskName])) {
				if(strpos($taskName, ':') !== 0) {
					list($category, $taskName) = explode(':', $taskName);
					if(isset($this->tasks[$category])) {
						$taskName = $category;
					} else {
						$taskName = 'help';
					}
				} else {
					$taskName = 'help';
				}
			}
			
			// check for alias
			$task = $this->tasks[$taskName];
			if($taskName != $task->getName()) {
				$taskName = $task->getName();
			}
			// return
			return $taskName;
		}
		return $this->defaultTask ?: 'help';
	}
	
	public function getTasks() {
		return $this->tasks;
	}

	public function __invoke($taskName=null) {
		$taskName = $taskName ?: $this->getTaskName();
		$task = $this->tasks[$taskName];
		
		// only run tasks that have not been executed before
		if(!$this->hasBeenExecuted($taskName)) {
			try {
				$task($this);
			} catch(\Exception $e) {
				Pake::writeln($e->getMessage(), Pake::ERROR);
				exit(1);
			}
			$this->executedTasks[$taskName] = true;
		}
	}

	private function hasBeenExecuted($taskName) {
		return isset($this->executedTasks[$taskName]);
	}
	
	public function getTaskArgs() {
		$args = $_SERVER['argv'];
		$taskName = $this->getTaskName();
		// drop everything before task name
		while(count($args) > 0 && $taskName != $args[0]) {
			array_shift($args);
		}
		// drop the task name itself
		array_shift($args);
		return new RequestContainer(new Request($args));
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