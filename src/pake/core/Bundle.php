<?php
namespace pake\core;

abstract class Bundle {
	public abstract function registerClassLoader();
	public function registerLocalTasks() {}
	public function registerGlobalTasks() {}
}