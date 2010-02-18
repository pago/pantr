<?php
namespace pake\Dependency;

use pake\Task;
use pake\Executor;

class ExecutionStrategy {
	private $executor;
	public function __construct(Executor $ex) {
		$this->executor = $ex;
	}
	
	public function getStrategy(Task $task) {
		$order = array($task->getName());
		$this->addDependencies($task, $order);
		return $order;
	}
	
	private function addDependencies(Task $task, $order) {
		$dependencies = $task->getDependencies();
		foreach($dependencies as $dep) {
			$order[] = $dep;
			$job = $this->executor->getTask($dep);
			$this->addDependencies($job, $order);
		}
	}
}