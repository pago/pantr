<?php

use pantr\Task;
use pantr\core\Status;

use pgs\cli\RequestContainer;

class MockTask extends Task {
	private $executed = 0;
	private $returnValue = Status::SUCCESS;
	
	public function __construct($name) {
		parent::__construct($name);
	}
	
	public function __invoke(RequestContainer $args) {
        $this->executed++;
		return $this->returnValue;
    }

	public function willReturn($value) {
		$this->returnValue = $value;
		return $this;
	}
	
	public function wasExecuted() { return 0 < $this->executed; }
	public function executionCount() {
		return $this->executed;
	}
	
	public static function create($name) {
		return new MockTask($name);
	}
}
