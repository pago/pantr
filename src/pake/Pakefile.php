<?php
namespace pake;

class Pakefile {
	private $name;
	public function __construct($name) {
		$this->name = $name;
	}
	
	public function load() {
		require $this->name;
	}
}