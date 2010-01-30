<?php
namespace com\pagosoft\pake;

class Task {
	private $name, $desc;
	private $dependsOn = array();
	private $run;
	
	public function __construct($name, $desc) {
		$this->name = $name;
		$this->desc = $desc;
	}
	
	public function dependsOn() {
		$this->dependsOn = array_merge($this->dependsOn, func_get_args());
		return $this;
	}
	
	public function run($fn) {
		$this->run = $fn;
		return $this;
	}
	
	public function __invoke() {
		foreach($this->dependsOn as $taskName) {
			Pake::run($taskName);
		}
		$fn = $this->run;
		$fn();
	}
}