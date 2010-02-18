<?php
namespace pake\core;

use pake\Task;

class ExecutionStrategyFactory {
	private $taskRepository;
	public function __construct(TaskRepository $taskRepository) {
		$this->taskRepository = $taskRepository;
	}
	
	public function get(Task $task) {
		return new ExecutionStrategy($this->taskRepository, $task);
	}
}