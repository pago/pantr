<?php
namespace pake\core;

use pake\Task;

class ExecutionStrategy implements \IteratorAggregate {
	private $repo, $marked=array();
	private $path;
	public function __construct(TaskRepository $repo, Task $task) {
		$this->repo = $repo;
		$this->path = $this->getDependencyList($task);
	}
	
	public function getIterator() {
		return new \ArrayIterator($this->path);
	}
	
	private function getDependencyList(Task $task) {
		if(isset($this->marked[$task->getName()])) {
			return array();
		}
		$this->marked[$task->getName()] = true;
		$path = array();
		$deps = $this->asTasks($task->getDependencies());
		foreach($deps as $dep) {
			$path = array_merge($path, $this->getDependencyList($dep));
		}
		$path[] = $task->getName();
		return $path;
	}
	
	private function asTasks(array $deps) {
		$repo = $this->repo;
		return array_map(function($taskName) use ($repo) {
			return $repo->getTask($taskName);
		}, $deps);
	}
}