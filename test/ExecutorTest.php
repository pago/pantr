<?php
require_once 'bootstrap.php';

use pake\Executor,
	pake\Task,
	pake\CyclicResolutionPakefileFactory;

class ExecutorTest extends PHPUnit_Framework_TestCase {
	public function testInvoke() {
		// GIVEN
		$ex = new Executor(array(), new CyclicResolutionPakefileFactory());
		$ex->setDefault('test');
		
		$task = new Task('test', '');
		$invoked = false;
		$task->run(function() use (&$invoked) {
			$invoked = true;
		});
		$ex->registerTask($task);
		
		// WHEN
		$ex();
		
		// THEN
		$this->assertTrue($invoked);
	}
}