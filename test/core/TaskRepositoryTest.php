<?php
require_once __DIR__.'/../bootstrap.php';

use pake\Task;
use pake\core\TaskRepository;

class TaskRepositoryTest extends PHPUnit_Framework_TestCase {
	/**
	 * it should save tasks
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_save_tasks() {
		$repo = new TaskRepository();
		$names = array('a', 'b', 'c', 'd', 'e', 'f');
		foreach($names as $name) {
			$repo->registerTask($this->task($name));
		}
		
		foreach($names as $name) {
			$task = $repo->getTask($name);
			$this->assertNotNull($task);
			$this->assertEquals($name, $task->getName());
		}
	} // it should save tasks
	
	/**
	 * it should handle alias
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_handle_alias()
	{
		$repo = new TaskRepository();
		$repo->registerTask($this->task('a'));
		$repo->alias('a', 'b');
		$this->assertEquals('a', $repo->getTask('b')->getName());
	} // it should handle alias
	
	/**
	 * it should locate abbreviated tasks
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_locate_abbreviated_tasks() {
		$repo = new TaskRepository();
		$repo->registerTask($this->task('foobar'));
		$this->assertEquals('foobar', $repo['foo']->getName());
	} // locate abbreviated tasks
	
	/**
	 * it should throw an exception if two abbreviated tasks match
	 * @author Patrick Gotthardt
	 * @test
	 * @expectedException \pake\core\NoTaskFoundException
	 */
	public function it_should_throw_an_exception_if_two_abbreviated_tasks_match() {
		// GIVEN a task repository with two tasks
		$repo = new TaskRepository();
		$repo->registerTask($this->task('bar'));
		$repo->registerTask($this->task('baz'));
		
		// WHEN using an ambigious task name
		$task = $repo['ba'];
		
		// THEN throw an exception
	} // throw an exception if two abbreviated tasks match
	
	/**
	 * it should use a fully qualified task name if it exists before checking for abbreviations
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_use_a_fully_qualified_task_name_if_it_exists_before_checking_for_abbreviations() {
		// GIVEN a task repository with two tasks
		$repo = new TaskRepository();
		$repo->registerTask($this->task('foo'));
		$repo->registerTask($this->task('foobar'));
		
		// WHEN requesting the fully qualified task
		$task = $repo['foo'];
		
		// THEN it should be the correct task
		$this->assertEquals('foo', $task->getName());
	} // use a fully qualified task name if it exists before checking for abbreviations
	
	/**
	 * it should accept the dash sign as word seperator
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_accept_the_dash_sign_as_word_seperator() {
		$repo = new TaskRepository();
		$repo->registerTask($this->task('foo-bar'));
		$this->assertEquals('foo-bar', $repo['f-b']->getName());
	} // accept the dash sign as word seperator
	
	
	private function task($name) {
		return new Task($name, '');
	}
}