<?php
namespace pake\core;

use pgs\cli\RequestContainer;
use pake\Task;
use pake\Pake;

class TaskExecutor {
	private $taskRepository, $executionStrategyFactory, $args;
	public function __construct(TaskRepository $taskRepository,
			ExecutionStrategyFactory $executionStrategyFactory, RequestContainer $args) {
		$this->taskRepository = $taskRepository;
		$this->executionStrategyFactory = $executionStrategyFactory;
		$this->args = $args;
	}
	
	public function execute(Task $task, $abortOnFailure=true) {
		$path = $this->executionStrategyFactory->get($task);
		foreach($path as $subTaskName) {
			$subTask = $this->taskRepository->getTask($subTaskName);
			$status = $subTask($this->args);
			if($status == Status::FAILURE && $abortOnFailure) {
				Pake::writeln('Task "'.$subTaskName.'" failed. Aborting.', Pake::ERROR);
				return Status::FAILURE;
			}
		}
		return Status::SUCCESS;
	}
}