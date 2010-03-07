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

class TaskRepository implements \ArrayAccess, \Countable, \IteratorAggregate {
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
		return $this[$name];
	}
	
	// ArrayAccess implementation
	public function get($name) {
		$taskName = $this->getTaskName($name);
		return $this->tasks[$taskName];
	}

	public function offsetExists($taskName) {
//		$taskName = $this->getTaskName($name);
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
	
	public function count() {
		return count($this->tasks);
	}
	
	public function getIterator() {
		return new \ArrayIterator($this->tasks);
	}
}