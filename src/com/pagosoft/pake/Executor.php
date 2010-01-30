<?php
namespace com\pagosoft\pake;

class Executor {
	private $args;
	private $tasks = array();
	private $defaultTask = null;
	private $executedTasks = array();
	private $factory;
	
	public function __construct($args, PakefileFactory $factory) {
		$this->args = $args;
		$this->factory = $factory;
	}
	
	public function registerTask(Task $task) {
		$this->tasks[$task->getName()] = $task;
	}
	
	public function setDefault($task) {
		$this->defaultTask = $task;
	}
	
	public function loadPakefile() {
		$name = 'pakefile';
		$pakefile = $this->factory->getPakefile($name);
		$pakefile->load();
	}
	
	private function getTaskName() {
		if(isset($this->args[0])) {
			return $this->args[0];
		}
		return $this->defaultTask;
	}

	public function __invoke($taskName=null) {
		$taskName = $taskName ?: $this->getTaskName();
		// only run tasks that have not been executed before
		if(!$this->hasBeenExecuted($taskName)) {
			$task = $this->tasks[$taskName];
			$task($this);
			$this->executedTasks[$taskName] = true;
		}
	}

	private function hasBeenExecuted($taskName) {
		return isset($this->executedTasks[$taskName]);
	}
}