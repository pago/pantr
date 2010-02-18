<?php
require_once __DIR__.'/../bootstrap.php';

use pake\Executor;
use pake\Task;
use pake\CyclicResolutionPakefileFactory;
use pake\Dependency\ExecutionStrategy;

class ExecutionStrategyTest extends PHPUnit_Framework_TestCase {
	/**
	 * it should add all dependencies
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_add_all_dependencies() {
		$ex = new Executor(array(), new CyclicResolutionPakefileFactory());
		$strat = new ExecutionStrategy($ex);
		
		$this->addTask($ex, 'a')->dependsOn('c');
		$this->addTask($ex, 'b');
		$this->addTask($ex, 'c');
		$this->addTask($ex, 'd')->dependsOn('a', 'b', 'c');
		
		$path = $strat->getStrategy($ex->getTask('d'));
		print_r($path);
	} // it should add all dependencies
	
	private function addTask(Executor $ex, $name) {
		$task = new Task($name, '');
		$ex->registerTask($task);
		return $task;
	}
}