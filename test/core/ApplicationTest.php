<?php
require_once dirname(__FILE__).'/../bootstrap.php';

require_once __DIR__.'/../mocks/MockTaskRepository.php';

use pake\Pake;
use pake\Task;
use pake\core\Status;
use pake\core\TaskExecutor;
use pake\core\TaskExecutorFactory;
use pake\core\TaskRepository;
use pake\core\ExecutionStrategy;
use pake\core\ExecutionStrategyFactory;
use pake\core\Application;

use pgs\cli\Request;
use pgs\cli\RequestContainer;

class ApplicationTest extends PHPUnit_Framework_TestCase {
	private $taskRepository, $app;
	public function setUp() {
		$this->taskRepository = new MockTaskRepository();
		$a = $this->taskRepository->addDummyTask('a')->dependsOn('b');
		$b = $this->taskRepository->addDummyTask('b');
		
		$this->app = $this->getApplication($this->taskRepository);
	}
	
	/**
	 * it should execute the selected task
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_execute_the_selected_task() {
		$this->app->run('a');
		$this->taskRepository->assertAllTasksExecuted();
	} // execute the selected task
	
	/**
	 * it should use default task if none specified
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_use_default_task_if_none_specified() {
		$this->app->setDefaultTask('b');
		$this->app->run();
		$this->assertTrue($this->taskRepository['b']->wasExecuted());
		$this->assertFalse($this->taskRepository['a']->wasExecuted());
	} // use default task if none specified
	
	/**
	 * it should use category when no specific task was found
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_use_category_when_no_specific_task_was_found() {
		$foo = $this->taskRepository->addDummyTask('foo');
		
		$this->app->run('foo:bar');
		$this->assertTrue($foo->wasExecuted());
	} // use category when no specific task was found
	
	
	private function getApplication(TaskRepository $taskRepository) {
		$executionStrategyFactory = new ExecutionStrategyFactory($taskRepository);
		
		$taskExecutor = new TaskExecutor(
			$taskRepository,
			$executionStrategyFactory,
			new RequestContainer(new Request(array())));
			
		$taskExecutorFactory = new TaskExecutorFactory($taskRepository, $executionStrategyFactory);
		
		$pakefile = $this->getMock('pake\core\Pakefile', array('load'), array(''));
		$pakefile->expects($this->once())
			->method('load');
		$pakefileFactory = $this->getMock('pake\core\PakefileFactory');
		$pakefileFactory->expects($this->once())
			->method('getPakefile')
			->will($this->returnValue($pakefile));
		
		return new Application($taskRepository, $pakefileFactory, $taskExecutorFactory);
	}
}