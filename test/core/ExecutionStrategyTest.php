<?php
require_once __DIR__.'/../bootstrap.php';

use pake\Task;
use pake\core\TaskRepository;
use pake\core\ExecutionStrategy;

class ExecutionStrategyTest extends PHPUnit_Framework_TestCase {
	/**
	 * it should sort tasks
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_sort_tasks()
	{
		$repo = new TaskRepository();
		$repo->registerTask($this->task('a')->dependsOn('c'));
		$repo->registerTask($this->task('b')->dependsOn('e', 'f'));
		$repo->registerTask($this->task('c'));
		$repo->registerTask($this->task('d')->dependsOn('a', 'b', 'c'));
		$repo->registerTask($this->task('e'));
		$repo->registerTask($this->task('f')->dependsOn('c'));

		$strat = new ExecutionStrategy($repo, $repo->getTask('d'));
		// c -> a -> e -> f -> b -> d
		$path = array('c', 'a', 'e', 'f', 'b', 'd');
		$i = 0;
		foreach($strat as $task) {
			$this->assertEquals($path[$i++], $task);
		}
		$this->assertEquals(count($path), $i);
	} // it should sort tasks
	
	
	private function task($name) {
		return new Task($name, '');
	}
}