<?php
namespace com\pagosoft\pake;

class Pake {
	private static $tasks = array();
	public static function task($name, $desc) {
		$task = new Task($name, $desc);
		self::$tasks[$name] = $task;
		return $task;
	}
	
	public static function getTask($name) {
		return self::$tasks[$name];
	}
	
	private static $defaultTask = null;
	public static function setDefault($task) {
		self::$defaultTask = $task;
	}
	
	public static function runDefault() {
		self::run(self::$defaultTask);
	}

	private static $executedTasks = array();
	public static function run($taskName) {
		// only run tasks that have not been executed before
		if(!self::hasBeenExecuted($taskName)) {
			$task = self::$tasks[$taskName];
			$task();
			self::$executedTasks[$taskName] = true;
		}
	}

	public static function hasBeenExecuted($taskName) {
		return isset(self::$executedTasks[$taskName]);
	}
}