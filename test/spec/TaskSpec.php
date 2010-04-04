<?php
namespace PSpec;

use pantr\Task;

describe('Task', function($self) {
	$self->addMethod('invoke', function($task, $args=array()) {
		$req = new \pgs\cli\RequestContainer(new \pgs\cli\Request($args));
		$task($req);
	});
	
	// this is obviously useless but it acts as a proof-of-concept
	beforeEach(function($self) {
		$self->task = new Task('a');
	});
	
	it('should throw an exception if argument passed to before is invalid', function($self) {
		$task = $self->task;
		$invokedWith = function($with) use (&$task) {
			return function() use ($with, &$task) {
				$task->before($with);
			};
		};
		the('task before method', $invokedWith('foo'))->shouldProduce('InvalidArgumentException');
		the('task before method', $invokedWith(null))->shouldProduce('InvalidArgumentException');
	
		$other = new Task('b');
		$other->dependsOn('c');
		the('task before method', $invokedWith($other))->shouldProduce('InvalidArgumentException');
	});
	
	it('should pass the request and itself as arguments to the invoked action', function($self) {
		$task = $self->task;
		$task['run'] = false;
		$task->run(function($req, $task) {
			the($req)->shouldHaveKey('f');
			theValueOf($req['f'])->shouldBe(true);
			$task['run'] = true;
		});
		$self->invoke($task, array('-f'));
		theValueOf($task['run'])->shouldBe(true);
	});
	
	it('should accept a callable as argument to before', function($self) {
		// WHEN adding a callable to the before list
		$self->task->before(function($args, $self) {
		});
		// THEN nothing should happen
	});
	
	it('should invoke the before functions before it is invoked', function($self) {
		$task = $self->task;
		$task->before(function($req, $task) {
			$task['foo'] = true;
		});
		$task->run(function($req, $task) {
			the($task)->shouldHaveKey('foo');
			theValueOf($task['foo'])->shouldBe(true);
		});
		$self->invoke($task);
	});
	
	it('should call the action even if it takes no arguments', function($self) {
		$task = $self->task;
		$run = false;
		$task->run(function() use (&$run) {
			$run = true;
		});
		$self->invoke($task);
		theValueOf($run)->shouldBeTrue();
	});
	
	it('should not invoke the action if a before callable fails', function($self) {
		$task = $self->task;
		$task->before(function($req, $task) {
			return false;
		});
		$task->run(function($req, $task) {
			failBecause('the task should not have been run');
		});
		$self->invoke($task);
	});
});