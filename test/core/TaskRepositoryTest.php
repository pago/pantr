<?php
require_once __DIR__.'/../bootstrap.php';

use pantr\Task;
use pantr\core\TaskRepository;

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
	
	private function task($name) {
		return new Task($name, '');
	}
}