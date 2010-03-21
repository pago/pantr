<?php
require_once __DIR__.'/../../mocks/MockTaskRepository.php';

use pantr\pantr;
use pantr\Task;
use pantr\core\Status;
use pantr\core\TaskExecutor;
use pantr\core\TaskExecutorFactory;
use pantr\core\TaskRepository;
use pantr\core\ExecutionStrategy;
use pantr\core\ExecutionStrategyFactory;
use pantr\core\Application;

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
	
	/**
	 * it should run abbreviated tasks
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_locate_abbreviated_tasks() {
		// GIVEN a task repository with a "foobar" task
		$foobar = $this->taskRepository->addDummyTask('foobar');
		
		// WHEN executing task "foo"
		$this->app->run('foo');
		
		// THEN "foobar" was executed
		$this->assertTrue($foobar->wasExecuted());
	} // locate abbreviated tasks
	
	/**
	 * it should throw an exception if two abbreviated tasks match
	 * @author Patrick Gotthardt
	 * @test
	 * @expectedException \pantr\core\NoTaskFoundException
	 */
	public function it_should_throw_an_exception_if_two_abbreviated_tasks_match() {
		// GIVEN a task repository with two tasks
		$this->taskRepository->addDummyTask('bar');
		$this->taskRepository->addDummyTask('baz');
		
		// WHEN using an ambigious task name
		$this->app->run('ba');
		
		// THEN throw an exception
	} // throw an exception if two abbreviated tasks match
	
	/**
	 * it should use a fully qualified task name if it exists before checking for abbreviations
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_use_a_fully_qualified_task_name_if_it_exists_before_checking_for_abbreviations() {
		// GIVEN a task repository with two tasks
		$foo = $this->taskRepository->addDummyTask('foo');
		$foobar = $this->taskRepository->addDummyTask('foobar');
		
		// WHEN running task "foo"
		$this->app->run('foo');
		
		// THEN task "foo" should have been executed
		$this->assertTrue($foo->wasExecuted());
	} // use a fully qualified task name if it exists before checking for abbreviations
	
	/**
	 * it should accept the dash sign as word seperator
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_accept_the_dash_sign_as_word_seperator() {
		// GIVEN a task repository with two tasks
		$t1 = $this->taskRepository->addDummyTask('foo-bar');
		$t2 = $this->taskRepository->addDummyTask('foo:bar');
		
		// WHEN running task "foo"
		$this->app->run('f-b');
		$this->app->run('f:b');
		
		// THEN task "foo" should have been executed
		$this->assertTrue($t1->wasExecuted());
		$this->assertTrue($t2->wasExecuted());
	} // accept the dash sign as word seperator
	
	private function getApplication(TaskRepository $taskRepository) {
		$executionStrategyFactory = new ExecutionStrategyFactory($taskRepository);
		
		$taskExecutor = new TaskExecutor(
			$taskRepository,
			$executionStrategyFactory,
			new RequestContainer(new Request(array())));
			
		$taskExecutorFactory = new TaskExecutorFactory($taskRepository, $executionStrategyFactory);
		
		$pantrfile = $this->getMock('pantr\core\TaskFile', array('load', 'getPath'), array(''));
		$pantrfile->expects($this->once())
			->method('load');
		$pantrfile->expects($this->once())
			->method('getPath')
			->will($this->returnValue(getcwd()));
		$pantrfileFactory = $this->getMock('pantr\core\TaskFileFactory');
		$pantrfileFactory->expects($this->once())
			->method('getTaskFile')
			->will($this->returnValue($pantrfile));
		
		return new Application($taskRepository, $pantrfileFactory, $taskExecutorFactory);
	}
}