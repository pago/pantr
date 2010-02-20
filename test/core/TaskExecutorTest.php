<?php
require_once dirname(__FILE__).'/../bootstrap.php';

require_once 'PHPUnit/Extensions/OutputTestCase.php';
require_once __DIR__.'/../mocks/MockTaskRepository.php';

use pake\Pake;
use pake\Task;
use pake\core\TaskExecutor;
use pake\core\Status;
use pake\core\TaskRepository;
use pake\core\ExecutionStrategy;

use pgs\cli\Request;
use pgs\cli\RequestContainer;

class TaskExecutorTest extends PHPUnit_Extensions_OutputTestCase {
	/**
	 * it should invoke all tasks
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_invoke_all_tasks() {
		$taskRepository = new MockTaskRepository();
		$a = $taskRepository->addDummyTask('a')->dependsOn('b');
		$b = $taskRepository->addDummyTask('b');
		
		$taskExecutor = $this->getTaskExecutor($taskRepository, $a);
		$status = $taskExecutor->execute($a);
		$this->assertEquals(Status::SUCCESS, $status);
		$taskRepository->assertAllTasksExecuted();
	} // it should invoke all tasks
	
	/**
	 * it should abort when dependency fails
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_abort_when_dependency_fails() {
		$taskRepository = new MockTaskRepository();
		$a = $taskRepository->addDummyTask('a')->dependsOn('b');
		$b = $taskRepository->addDummyTask('b', Status::FAILURE);
		
		$taskExecutor = $this->getTaskExecutor($taskRepository, $a);
		$this->expectOutputString(Pake::colorize(
			'Task "b" failed. Aborting.'."\n", Pake::ERROR));
		$status = $taskExecutor->execute($a);
		
		$this->assertEquals(Status::FAILURE, $status);
		$this->assertTrue($b->wasExecuted());
		$this->assertFalse($a->wasExecuted());
	} // it should abort when dependency fails
	
	
	private function getTaskExecutor(TaskRepository $taskRepository, Task $task) {
		$executionStrategy = new ExecutionStrategy($taskRepository, $task);
		$executionStrategyFactory = $this->getMock(
			'pake\core\ExecutionStrategyFactory', array('get'),
			array($taskRepository));
		$executionStrategyFactory->expects($this->once())
			->method('get')
			->will($this->returnValue($executionStrategy));
		
		$taskExecutor = new TaskExecutor($taskRepository, $executionStrategyFactory,
			new RequestContainer(new Request(array())));
		return $taskExecutor;
	}
}