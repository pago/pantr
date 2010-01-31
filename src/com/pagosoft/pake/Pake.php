<?php
namespace com\pagosoft\pake;

use com\pagosoft\cli\Output;

class Pake {
	const VERSION='0.2';
	
	private static $executor;
	private static $out;
	public static function setExecutor(Executor $ex) {
		self::$executor = $ex;
	}
	
	public static function getDefinedTasks() {
		return self::$executor->getTasks();
	}
	
	public static function task($name, $desc) {
		$task = new Task($name, $desc);
		self::$executor->registerTask($task);
		return $task;
	}
	
	public static function alias($taskName, $alias) {
		self::$executor->alias($taskName, $alias);
	}
	
	public static function setDefault($taskName) {
		self::$executor->setDefault($taskName);
	}

	public static function run($taskName) {
		self::$executor->run($taskName);
	}
	
	public static function getArgs() {
		return self::$executor;
	}
		
	const PARAMETER='PARAMETER';
	const COMMENT='COMMENT';
	const INFO='INFO';
	const WARNING='WARNING';
	const ERROR='ERROR';
	const SECTION='SECTION';
	
	public static function getOut() {
		if(self::$out == null) {
			self::$out = new Output();
			self::$out->registerStyle('INFO', array('fg' => 'yellow'))
				->registerStyle('WARNING', array('fg' => 'red'))
				->registerStyle('ERROR', array('fg' => 'red', 'reverse' => true, 'bold' => true))
				->registerStyle('SECTION', array('bold' => true));
		}
		return self::$out;
	}
	
	private static $enhancements = array();
	public static function enhance($name, $fn) {
		self::$enhancements[$name] = $fn;
	}
	
	public static function __callStatic($name, $arguments) {
		if(!isset(self::$enhancements[$name])) {
			$out = self::getOut();
			if(method_exists($out, $name)) {
				return call_user_func_array(array($out, $name), $arguments);
			}
			throw new \Exception('Calling undefined function '.$name);
		}
		$fn = self::$enhancements[$name];
		return call_user_func_array($fn, $arguments);
	}
}