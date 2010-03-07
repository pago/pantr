<?php
/* 
 * Copyright (c) 2010 Patrick Gotthardt
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace pake;

use pake\core\TaskRepository;
use pake\core\Application;
use pake\core\HomePathProvider;

use Pagosoft\Console\Console;
use Pagosoft\Console\Output;
use Pagosoft\Console\Input;

use pgs\util\Finder;
use pake\ext\Phar;

/**
 * The Pake class is the public API that pakefiles should use to create tasks,
 * execute certain standard actions (move a file, delete some file, etc.).
 *
 * It also provides a way to register a task, set the default task and
 * register an alias for a task.
 *
 * Any unknown static method call that the pgs\cli\Output class would know how to handle
 * will be delegated towards it. Thus you will also use the Pake class to
 * format your output.
 *
 * There should not be an instance of the Pake class, since all of its methods are static.
 */
class Pake {
	const VERSION='DEV';
	
	//-----------------------------------------------------------------------
	// Task execution part
	//-----------------------------------------------------------------------
	
	private static $taskRepository;
	private static $application;
	private static $console, $log;
	private static $homePathProvider;
	
	/**
	 * Specify the executor that should be used to invoke and
	 * and register tasks.
	 */
	public static function setTaskRepository(TaskRepository $taskRepository) {
		self::$taskRepository = $taskRepository;
	}
	
	public static function setApplication(Application $application) {
		self::$application = $application;
	}
	
	/**
	 * Specify the HomePathProvider that provides the path to the global
	 * pake directory.
	 */
	public static function setHomePathProvider(HomePathProvider $p) {
		self::$homePathProvider = $p;
	}
	
	/**
	 * Returns all registered tasks.
	 * @see pake\Executor#getTasks()
	 */
	public static function getDefinedTasks() {
		return self::$taskRepository->getTasks();
	}
	
	/**
	 * Returns the specified task or null, if it does not exist.
	 */
	public static function getTask($name) {
		return self::$taskRepository[$name];
	}
	
	/**
	 * Register a new task and return it for further specification.
	 *
	 * This is the main entrance point for pakefiles.
	 * <code>Pake::task('foo', 'some description')
	 * ->run(function() { Pake::writeln('Hello World!'); });
	 */
	public static function task($name, $desc) {
		$task = new Task($name, $desc);
		self::$taskRepository->registerTask($task);
		return $task;
	}
	
	/**
	 * Register an alias for a specific task.
	 * <code>Pake::alias('generate-pear-server-info', 'gpsi');</code>
	 */
	public static function alias($taskName, $alias) {
		self::$taskRepository->alias($taskName, $alias);
	}
	
	/**
	 * Specify the default task that should be executed if no
	 * task was provided by the user.
	 */
	public static function setDefault($taskName) {
		self::$application->setDefaultTask($taskName);
	}

	/**
	 * Executes the specified task and all of its dependencies.
	 */
	public static function run() {
		$args = func_get_args();
		if(count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		}
		self::$application->run($args);
	}
	
	//-----------------------------------------------------------------------
	// formatted printing, dealing with the console
	//-----------------------------------------------------------------------
	
	// output design
	const PARAMETER='PARAMETER';
	const COMMENT='COMMENT';
	const INFO='INFO';
	const WARNING='WARNING';
	const ERROR='ERROR';
	const SECTION='SECTION';
	
	/**
	 * Outputs the given text as it is (without indenting it)
	 * @param string $t
	 * @return Output
	 */
	public function write($t, $style = null) {
		return self::console()->out()->write($t, $style);
	}

	/**
	 * @return Output
	 */
	public function writeln($t='', $style = null) {
		return self::console()->out()->writeln($t, $style);
	}

	/**
	 * @return Output
	 */
	public function writeblock($t) {
		return self::console()->out()->writeblock($t);
	}
	
	/**
	 * @return Output
	 */
	public function writeOption($short, $long, $description=null) {
		// is there a shorthand?
		if(is_null($description)) {
			$description = $long;
			$long = $short;
			return self::writeln(sprintf("   --%-20s %s", $long, $description));
		}
		return self::writeln(sprintf("-%s --%-20s %s", $short, $long, $description));
	}

	/**
	 * @return Output
	 */
	public function writeHelp($name, $usage, $longDesc) {
		return self::writeln('NAME', 'BOLD')
			->indent()
			->writeln($name)
			->dedent()->nl()
			->writeln('USAGE', 'BOLD')
			->indent()
			->writeln($usage)
			->dedent()->nl()
			->writeln('DESCRIPTION', 'BOLD')
			->indent()
			->writeblock($longDesc)
			->dedent();
	}
	
	/** 
	 * Output a formatted message to the user explaining
	 * the current task. $action is the performed task (i.e. "move", "build", "create")
	 * and $desc is the more detailed description ("to some other directory",
	 * "the PEAR channel", "a PEAR package")
	 *
	 * It will also log the action with a Zend_Log::NOTICE priority.
	 *
	 * @return Output
	 */
	public static function writeAction($action, $desc, $style='PARAMETER') {
		self::log()->notice($action . ': ' . $desc);
		return self::writeln(sprintf('[%20s|%s]    %s', $action, $style, $desc));
	}
	
	public static function writeActionPrefix($action, $style='PARAMETER') {
		return self::write(sprintf('[%20s|%s]    ', $action, $style));
	}
	
	/**
	 * Prints the version number for pake.
	 */
	public function writeInfo() {
		// display pake info
		$copySpan = '2010';
		$year = date('Y');
		if($year != '2010') {
			$copySpan .= '-'.$year;
		}
		self::writeln(
			'Pake ' . self::VERSION . ' (c) '.$copySpan.' Patrick Gotthardt',
			self::INFO
		)->nl();
	}
	
	/** 
	 * Provides access to the console
	 */
	public static function console() {
		if(is_null(self::$console)) {
			self::$console = new Console(new Input(), new Output());
		}
		return self::$console;;
	}
	
	public static function out() {
		return self::console()->out();
	}
	
	public static function in() {
		return self::console()->in();
	}
	
	//-----------------------------------------------------------------------
	// Logging
	//-----------------------------------------------------------------------
	public static function setLog(\Zend_Log $log) {
		self::$log = $log;
	}
	
	/**
     * Log a message at a priority.
	 * If called with no arguments at all, this method will return
	 * the current Zend_Log instance.
     *
     * @param  string   $message   Message to log
     * @param  integer  $priority  Priority of message
     * @param  mixed    $extras    Extra information to log in event
     * @return void
     * @throws Zend_Log_Exception
     */
    public static function log($message=null, $priority=null, $extras = null) {
		if(is_null(self::$log)) {
			// we initialize the log with the Null Writer in case the user
			// does not specify any other writer
			self::$log = new \Zend_Log(\Zend_Log_Writer_Null::factory(array()));
		}
		if(is_null($message) && is_null($priority) && is_null($extras)) {
			return self::$log;
		}
		self::$log->log($message, $priority, $extras);
	}
	
	//-----------------------------------------------------------------------
	// Misc
	//-----------------------------------------------------------------------
	
	private static $enhancements = array();
	/**
	 * Adds a new method to the Pake class. For example:
	 * <code>Pake::enhance('hello', function($who) { Pake::writeln('Hello '.$who); });
	 * Pake::hello('Patrick');</code>
	 */
	public static function enhance($name, $fn) {
		self::$enhancements[$name] = $fn;
	}
	
	public static function __callStatic($name, $arguments) {
		if(!isset(self::$enhancements[$name])) {
			throw new \Exception('Calling undefined function '.$name);
		}
		$fn = self::$enhancements[$name];
		return call_user_func_array($fn, $arguments);
	}
	
	//-----------------------------------------------------------------------
	// Utility methods
	//-----------------------------------------------------------------------
	
	const TYPE_FILES = 'file';
	const TYPE_DIR = 'dir';
	const TYPE_ANY = 'any';
	
	public static function fileset() {
		return Finder::type(self::TYPE_FILES);
	}
	
	public static function finder($type='any') {
		return Finder::type($type);
	}
	
	public static function getHomePath() {
		return self::$homePathProvider->get() ?: '';
	}
	
	private static $properties = array();
	public static function property($key, $value=null) {
		// retrieval
		if(is_null($value)) {
			$path = explode(':', $key);
			if(count($path) == 1) {
				return self::$properties[$key];
			}
			$top = self::$properties[$path[0]];
			array_shift($path);
			foreach($path as $k) {
				if(isset($top[$k])) {
					$top = $top[$k];
				} else {
					// key does not exist
					return null;
				}
			}
			return $top;
		}
		// storing
		if(strpos($key, ':') !== false) {
			throw new \InvalidArgumentException(
				'Property key cannot contain reserved character ":" but is "'.$key.'"');
		}
		self::$properties[$key] = $value;
	}
	
	/** Loads the specified yaml file from PAKE_HOME (if it exists)
	 *  and a local file (if it exists).
	 *  This mechanism can be used to build config cascades.
	 *
	 * If $onlyLocal is true the cascade mechanism is deactivated.
	 */
	public static function loadProperties($name='pake.yaml', $onlyLocal=false) {
		$home = self::getHomePath();
		$file = $home . DIRECTORY_SEPARATOR . $name;
		$props = array();
		if(file_exists($file) && !$onlyLocal) {
			$props = \sfYaml::load($file);
		}
		if(file_exists($name)) {
			$props = array_merge_recursive($props, \sfYaml::load($name));
		}
		foreach($props as $k => $v) {
			self::$properties[$k] = $v;
		}
	}
	
	public static function dependencies() {
		return new \pake\ext\PEARSync();
	}
	
	public static function _getFinderFromArg($arg, $target_dir = '', $relative = false) {
		$files = array();
	
	    if (is_array($arg)) {
	        $files = $arg;
	    } elseif (is_string($arg)) {
	        $files[] = $arg;
	    } elseif ($arg instanceof Finder) {
	        $files = $arg->in($target_dir);
	    } else {
	        throw new \Exception('Wrong argument type (must be a list, a string or a pakeFinder object).');
	    }
	
	    if ($relative and $target_dir) {
	        $files = preg_replace('/^'.preg_quote(realpath($target_dir), '/').'/', '', $files);
	
	        // remove leading /
	        $files = array_map(create_function('$f', 'return 0 === strpos($f, DIRECTORY_SEPARATOR) ? substr($f, 1) : $f;'), $files);
	    }
	
	    return $files;
	}
	
	public static function beginSilent($action=null, $msg=null, $style=null) {
		if(!is_null($action) && is_null($msg)) {
			Pake::writeln($action, $style);
		} else if(!is_null($action) && !is_null($msg)) {
			Pake::writeAction($action, $msg, $style ?: Pake::PARAMETER);
		}
		ob_start();
	}
	
	public static function endSilent() {
		ob_end_clean();
	}
	
	/** Instead of manually tracking the beginning and end of a silent
	 *  block you can use this method to execute a function silently.
	 *  Overloaded method calls:
	 *  <code>Pake::silent($action, $msg, $fn)</code>
	 *  <code>Pake::silent($msg, $fn)</code>
	 *  <code>Pake::silent($fn)</code>
	 *
	 *  If $action and $msg (or just $msg) are specified pake will emit
	 *  them as output before switching to silent mode.
	 *  It is recommended to do this in order to notify the user of
	 *  what is happening.
	 */
	public static function silent() {
		$args = func_get_args();
		switch(count($args)) {
			case 3:
				Pake::writeAction($args[0], $args[1]);
				$fn = $args[2];
				break;
			case 2:
				Pake::writeln($args[0]);
				$fn = $args[1];
				break;
			case 1: $fn = $args[0]; break;
			default: throw new \Exception('Invalid method invokation.');
		}
		Pake::beginSilent();
		$fn();
		Pake::endSilent();
	}
	
	/**
	 * @deprecated use pake\ext\Phar instead
	 */
	public static function phar($pharName, $paths='src') {
		return function() use ($pharName, $paths) {
			Phar::create($pharName, $paths);
		};
	}
	
	public static function create_pear_package($packageXml='package.xml', $dest=null) {
		if (!class_exists('PEAR_Packager')) {
	        @include('PEAR/Packager.php');
	
	        if (!class_exists('PEAR_Packager')) {
	            // falling back to cli-call
				Pake::sh('pear package', $return);
	            return;
	        }
	    }
		
	    $packager = new \PEAR_Packager();
	    $packager->debug = 0; // silence output
	    $archive = $packager->package($packageXml, true);
		Pake::writeAction('pear-package', $archive);
		
		if(!is_null($dest)) {
			Pake::beginSilent();
			Pake::move($archive, $dest.DIRECTORY_SEPARATOR.$archive);
			Pake::endSilent();
		}
		
		return $archive;
	}
	
	/** Invokes the specified function on the content of all specified
	 *  files. The result will be written to the same file the input
	 *  came from. This is supposed to be used when you're rewriting a
	 *  file after having copied it in some build directory.
	 *  
	 *  Overloads:
	 *  Pake::replaceIn(fileset|finder, $dir, $fn)
	 *  Pake::replaceIn(array<filepath>, $fn)
	 */
	public static function replace_in($arg, $origin_dir='', $fn=null) {
		if(!is_string($origin_dir) && is_null($fn)) {
			$fn = $origin_dir;
			$origin_dir = '';
		}
		$files = Pake::_getFinderFromArg($arg, $origin_dir);
		
		foreach($files as $file) {
			$content = file_get_contents($file);
			$content = $fn($file, $content);
			file_put_contents($file, $content);
		}
	}
	
	const SILENT = 1;
	
	// this part is based on http://wiki.github.com/indeyets/pake/
	/* 
	 * Copyright (c) 2010 Patrick Gotthardt
	 * Copyright © 2005 Fabien POTENCIER
	 * Copyright © 2009 Alexey ZAKHLESTIN
	 * 
	 * Permission is hereby granted, free of charge, to any person obtaining a copy
	 * of this software and associated documentation files (the "Software"), to deal
	 * in the Software without restriction, including without limitation the rights
	 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	 * copies of the Software, and to permit persons to whom the Software is
	 * furnished to do so, subject to the following conditions:
	 * 
	 * The above copyright notice and this permission notice shall be included in
	 * all copies or substantial portions of the Software.
	 * 
	 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	 * THE SOFTWARE.
	 */
	public static function mirror($arg, $origin_dir, $target_dir, $isSilent = false, $options = array()) {
		$files = Pake::_getFinderFromArg($arg, $origin_dir, true);
		
		// switch silent <-> options
		if(is_array($isSilent)) {
			$options = $isSilent;
			$isSilent = false;
		}
		
		if($isSilent) {
			Pake::writeAction('mirror', $origin_dir.' -> '.$target_dir);
			Pake::beginSilent();
		}
		
		foreach($files as $file) {
			if(is_dir($origin_dir.DIRECTORY_SEPARATOR.$file)) {
				Pake::mkdirs($target_dir.DIRECTORY_SEPARATOR.$file);
			} else if(is_file($origin_dir.DIRECTORY_SEPARATOR.$file)) {
				Pake::copy($origin_dir.DIRECTORY_SEPARATOR.$file,
					$target_dir.DIRECTORY_SEPARATOR.$file, $options);
			} else if(is_link($origin_dir.DIRECTORY_SEPARATOR.$file)) {
				Pake::symlink($origin_dir.DIRECTORY_SEPARATOR.$file, $target_dir.DIRECTORY_SEPARATOR.$file);
			} else {
				throw new \Exception(sprintf('Unable to determine "%s" type', $file));
			}
		}
		
		if($isSilent) {
			Pake::endSilent();
		}
	}
	
	public static function copy($src, $target, $vars=null) {
		$package = file_get_contents($src);
		if(!is_null($vars)) {
			foreach($vars as $k => $v) {
				$package = str_replace('##'.$k.'##', $v, $package);
			}
		}
		if(!is_dir(dirname($target))) {
			Pake::mkdirs(dirname($target));
		}
		Pake::writeAction('copy', $src . ' to ' . $target);
		file_put_contents($target, $package);
	}
	
	public static function move($src, $target) {
		Pake::writeAction('move', $src . ' to ' . $target);
		copy($src, $target);
		unlink($src);
	}
	
	public static function rm($arg, $target_dir='', $recursive=null) {
		if(!is_string($target_dir) && is_null($recursive)) {
			$recursive = $target_dir;
			$target_dir = '';
		}
		$files = array_reverse(Pake::_getFinderFromArg($arg, $target_dir));
		foreach($files as $target) {
			if(!file_exists($target)) {
				Pake::writeAction('rm', $target . ' does not exist', Pake::INFO);
				return;
			}
			
			if(is_dir($target) && !is_link($target)) {
				// remove all files
				Pake::rm(Pake::fileset()->in($target));

				// and now all empty directories
				Pake::rm(Pake::finder(self::TYPE_DIR)->in($target));
				Pake::writeAction('rm', $target);
				rmdir($target);
			} else {
				Pake::writeAction('rm', $target);
				unlink($target);
			}
		}
	}
	
	public static function touch($arg, $target_dir='') {
		$files = Pake::_getFinderFromArg($arg, $target_dir);
		foreach($files as $file) {
			Pake::writeAction('touch', $file);
			touch($file);
		}
	}
	
	public static function replace_tokens_to_dir($arg, $src, $target, $tokens) {
		$files = Pake::_getFinderFromArg($arg, $src, true);
		foreach($files as $file) {
			Pake::writeAction('tokens', $target . DIRECTORY_SEPARATOR . $file);
			$content = file_get_contents($src . DIRECTORY_SEPARATOR . $file);
			foreach($tokens as $k => $v) {
				$content = str_replace('##'.$k.'##', $v, $content);
			}
			file_put_contents($target . DIRECTORY_SEPARATOR . $file, $content);
		}
	}
	
	public static function replace_tokens($arg, $src, $tokens) {
		Pake::replace_tokens_to_dir($arg, $src, $src, $tokens);
	}
	
	public static function mkdirs($path, $mode = 0777) {
		if(is_dir($path)) {
			return true;
		}
		if(file_exists($path)) {
			throw new \Exception('Can not create directory at "'.$path.'" as place is already occupied by file');
		}
	
		Pake::writeAction('dir+', $path);
		return @mkdir($path, $mode, true);
	}
	
	public static function rename($origin, $target) {
		if(is_readable($target)) {
			throw new \Exception(sprintf('Cannot rename because the target "%" already exist.', $target));
		}
		Pake::writeAction('rename', $origin.' > '.$target);
		rename($origin, $target);
	}
	
	public static function symlink($origin_dir, $target_dir, $copy_on_windows = false) {
		if(!function_exists('symlink') && $copy_on_windows) {
			$finder = pakeFinder::type('any')->ignore_version_control();
			Pake::mirror($finder, $origin_dir, $target_dir);
			return;
		}
	
		$ok = false;
		if(is_link($target_dir)) {
			if(readlink($target_dir) != $origin_dir) {
				unlink($target_dir);
			} else {
				$ok = true;
			}
		}
	
		if(!$ok) {
			Pake::writeAction('link+', $target_dir);
			symlink($origin_dir, $target_dir);
		}
	}
	
	public static function chmod($arg, $target_dir, $mode, $umask = 0000) {
		$current_umask = umask();
		umask($umask);
	
		$files = Pake::_getFinderFromArg($arg, $target_dir, true);
	
		foreach ($files as $file) {
			Pake::writeAction(sprintf('chmod %o', $mode), $target_dir.DIRECTORY_SEPARATOR.$file);
			chmod($target_dir.DIRECTORY_SEPARATOR.$file, $mode);
		}
	
		umask($current_umask);
	}
	
	public static function sh($cmd, $interactive = false) {
		$verbose = true;
		Pake::writeAction('exec ', $cmd);
	
		if(false === $interactive) {
			ob_start();
		}
	
		passthru($cmd.' 2>&1', $return);
	
		if(false === $interactive) {
			$content = ob_get_clean();
	
			if($return > 0) {
				throw new \Exception(sprintf('Problem executing command %s', $verbose ? "\n".$content : ''));
			}
		} else {
			if ($return > 0) {
				throw new \Exception('Problem executing command');
			}
		}
	
		if(false === $interactive) {
			return $content;
		}
	}
}