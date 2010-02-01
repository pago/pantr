<?php
namespace pake;

use pgs\cli\Output;
use pgs\util\Finder;

class Pake {
	const VERSION='0.4.0';
	
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
	
	// Utility-methods
	public static function finder($type='any') {
		return Finder::type($type);
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
	
	public static function writeAction($action, $desc, $style='PARAMETER') {
		return Pake::writeln(sprintf('[%20s|%s]    %s', $action, $style, $desc));
	}
	
	public static function phar($pharName, $paths='src') {
		return function() use ($pharName, $paths) {
			Phar::create($pharName, $paths);
		};
	}
	
	public static function create_pear_package($packageXml='package.xml') {
		if (!class_exists('PEAR_Packager')) {
	        @include('PEAR/Packager.php');
	
	        if (!class_exists('PEAR_Packager')) {
	            // falling back to cli-call
				Pake::sh('pear package', $return);
	            return;
	        }
	    }
		
		Pake::writeAction('pear-package', $packageXml);
	    $packager = new \PEAR_Packager();
	    $packager->debug = 0; // silence output
	    $archive = $packager->package($packageXml, true);
		return $archive;
	}
	
	public static function mirror($arg, $origin_dir, $target_dir, $options = array()) {
		$files = Pake::_getFinderFromArg($arg, $origin_dir, true);
	
		foreach($files as $file) {
			if(is_dir($origin_dir.DIRECTORY_SEPARATOR.$file)) {
				Pake::mkdirs($target_dir.DIRECTORY_SEPARATOR.$file);
			} else if(is_file($origin_dir.DIRECTORY_SEPARATOR.$file)) {
				Pake::copy($origin_dir.DIRECTORY_SEPARATOR.$file,
					$target_dir.DIRECTORY_SEPARATOR.$file, $options);
			} else if(is_link($origin_dir.DIRECTORY_SEPARATOR.$file)) {
				pake_symlink($origin_dir.DIRECTORY_SEPARATOR.$file, $target_dir.DIRECTORY_SEPARATOR.$file);
			} else {
				throw new \Exception(sprintf('Unable to determine "%s" type', $file));
			}
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
	
	public static function rm($arg, $target_dir='') {
		$files = array_reverse(Pake::_getFinderFromArg($arg, $target_dir));
		foreach($files as $target) {
			if(!file_exists($target)) {
				Pake::writeAction('rm', $target . ' does not exist', Pake::INFO);
				return;
			}
			Pake::writeAction('rm', $target);
			if(is_dir($target) && !is_link($target)) {
				Pake::rm(Pake::finder()->in($target));
				rmdir($target);
			} else {
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