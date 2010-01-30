<?php
namespace com\pagosoft\pake;

class Pake {
	const VERSION='0.1';
	
	private static $executor;
	public static function setExecutor(Executor $ex) {
		self::$executor = $ex;
	}
	
	public static function task($name, $desc) {
		$task = new Task($name, $desc);
		self::$executor->registerTask($task);
		return $task;
	}
	
	public static function setDefault($taskName) {
		self::$executor->setDefault($taskName);
	}

	public static function run($taskName) {
		self::$executor->run($taskName);
	}
}