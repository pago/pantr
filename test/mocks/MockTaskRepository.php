<?php
use pake\core\TaskRepository;
use pake\core\Status;

require_once __DIR__.'/MockTask.php';

class MockTaskRepository extends TaskRepository {
	public $tasksExecuted = 0;
	
	public function assertAllTasksExecuted() {
		foreach($this as $task) {
			PHPUnit_Framework_Assert::assertTrue($task->wasExecuted());
		}
	}
	
	public function assertTaskExecuted($name, $times=null) {
		$times = $times ?: 1;
		if($name instanceof MockTask) {
			$task = $name;
			$name = $task->getName();
		} else {
			$task = $this[$name];
		}
		PHPUnit_Framework_Assert::assertEquals($times, $task->executionCount(),
			'Task '.$name.' should have been executed '.$times.' times');
	}
	
	public function addDummyTask($name, $result=null) {
		$task = MockTask::create($name)
			->willReturn($result ?: Status::SUCCESS);
		$this->registerTask($task);
		return $task;
	}
}