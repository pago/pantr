<?php
namespace pake;

class Option {
	private $long, $short=null, $desc='', $required=false;
	private $task;
	
	public function __construct(Task $task, $long) {
		$this->task = $task;
		$this->long = $long;
	}
	
	public function shorthand($short) {
		$this->short = $short;
		return $this;
	}
	
	public function desc($desc) {
		$this->desc = $desc;
		return $this;
	}
	
	public function required() {
		$this->required = true;
		return $this;
	}
	
	public function printHelp() {
		if(!is_null($this->short)) {
			Pake::writeOption($this->short, $this->long, $this->desc);
		} else {
			Pake::writeOption($this->long, $this->desc);
		}
	}
	
	public function registerOn(RequestContainer $req) {
		$req->registerParameter($this->long, $this->desc, $this->short, $this->required);
	}
	
	public function option($long) {
		$this->task->registerOption($this);
		return $this->task->option($long);
	}
	
	public function run($fn) {
		$this->task->registerOption($this);
		return $this->task->run($fn);
	}
}