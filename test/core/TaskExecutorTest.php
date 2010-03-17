<?php
require_once dirname(__FILE__).'/../bootstrap.php';

require_once 'PHPUnit/Extensions/OutputTestCase.php';
require_once __DIR__.'/../mocks/MockTaskRepository.php';

use pantr\pantr;
use pantr\Task;
use pantr\core\TaskExecutor;
use pantr\core\Status;
use pantr\core\TaskRepository;
use pantr\core\ExecutionStrategy;

use pgs\cli\Request;
use pgs\cli\RequestContainer;

class TaskExecutorTest extends PHPUnit_Extensions_OutputTestCase {
	public function setUp() {
		pantr::out()->disableColorizedOutput(true);
	}

	public function tearDown() {
		pantr::out()->disableColorizedOutput(false);
	}

	
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
		$this->expectOutputString('Task "b" failed. Aborting.'."\n");
		$status = $taskExecutor->execute($a);
		
		$this->assertEquals(Status::FAILURE, $status);
		$this->assertTrue($b->wasExecuted());
		$this->assertFalse($a->wasExecuted());
	} // it should abort when dependency fails
	
	
	private function getTaskExecutor(TaskRepository $taskRepository, Task $task) {
		$executionStrategy = new ExecutionStrategy($taskRepository, $task);
		$executionStrategyFactory = $this->getMock(
			'pantr\core\ExecutionStrategyFactory', array('get'),
			array($taskRepository));
		$executionStrategyFactory->expects($this->once())
			->method('get')
			->will($this->returnValue($executionStrategy));
		
		$taskExecutor = new TaskExecutor($taskRepository, $executionStrategyFactory,
			new RequestContainer(new Request(array())));
		return $taskExecutor;
	}
}