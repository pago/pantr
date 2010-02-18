<?php
namespace pake\core;

use pake\Task;

class TaskRepository implements \ArrayAccess {
	private $tasks;
	
	public function __construct() {
		$this->tasks = array();
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
	 * Find real task name (alias-aware)
	 */
	private function getTaskName($taskName) {
		// check for alias
		$task = $this->tasks[$taskName];
		if($taskName != $task->getName()) {
			$taskName = $task->getName();
		}
		// return
		return $taskName;
	}
	
	public function getTasks() {
		return $this->tasks;
	}
	
	public function getTask($name) {
		$taskName = $this->getTaskName($name);
		return $this->tasks[$taskName];
	}
	
	// ArrayAccess implementation
	public function get($name) {
		$taskName = $this->getTaskName($name);
		return $this->tasks[$taskName];
	}

	public function offsetExists($name) {
		$taskName = $this->getTaskName($name);
		return isset($this->tasks[$taskName]);
	}

	public function offsetGet($offset) {
		return $this->get($offset);
	}

	public function offsetSet($name, $task) {
		$this->registerTask($task);
		if($name != $task->getName()) {
			$this->alias($task->getName(), $name);
		}
	}

	public function offsetUnset($offset) {
		throw new \Exception('Cannot unregister task.');
	}
}