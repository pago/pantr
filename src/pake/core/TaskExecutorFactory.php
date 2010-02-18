<?php
namespace pake\core;

use pgs\cli\RequestContainer;

class TaskExecutorFactory {
	private $taskRepository, $executionStrategyFactory, $args;
	public function __construct(TaskRepository $taskRepository,
			ExecutionStrategyFactory $executionStrategyFactory) {
		$this->taskRepository = $taskRepository;
		$this->executionStrategyFactory = $executionStrategyFactory;
	}
	
	public function get(RequestContainer $args) {
		return new TaskExecutor($this->taskRepository,
			$this->executionStrategyFactory, $args);
	}
}