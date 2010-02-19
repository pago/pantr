<?php
require_once dirname(__FILE__).'/../bootstrap.php';

use pake\Task;
use pake\core\TaskExecutor;
use pake\core\Status;
use pake\core\TaskRepository;
use pake\core\ExecutionStrategy;

use pgs\cli\Request;
use pgs\cli\RequestContainer;

class TaskExecutorTest extends PHPUnit_Framework_TestCase {
	/**
	 * it should invoke all tasks
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_invoke_all_tasks() {
		$a = $this->getTask('a')->dependsOn('b');
		$b = $this->getTask('b');
			
		$taskRepository = new TaskRepository();
		$taskRepository->registerTask($a);
		$taskRepository->registerTask($b);
		
		$executionStrategy = new ExecutionStrategy($taskRepository, $a);
		$executionStrategyFactory = $this->getMock(
			'pake\core\ExecutionStrategyFactory', array('get'),
			array($taskRepository));
		$executionStrategyFactory->expects($this->once())
			->method('get')
			->will($this->returnValue($executionStrategy));
		
		$taskExecutor = new TaskExecutor($taskRepository, $executionStrategyFactory,
			new RequestContainer(new Request(array())));
		$status = $taskExecutor->execute($a);
		$this->assertEquals(Status::SUCCESS, $status);
	} // it should invoke all tasks
	
	private function getTask($name) {
		$task = new Task($name, '');
		return $task->run(function() {
			return Status::SUCCESS;
		});
	}
}