<?php
use com\pagosoft\pake\Pake;
use com\pagosoft\pake\Phar;
use com\pagosoft\pake\Finder;

Pake::enhance('finder', function($type='any') {
	return Finder::type($type);
});

Pake::enhance('_getFinderFromArg', function($arg, $target_dir = '', $relative = false) {
	$files = array();

    if (is_array($arg)) {
        $files = $arg;
    } elseif (is_string($arg)) {
        $files[] = $arg;
    } elseif ($arg instanceof Finder) {
        $files = $arg->in($target_dir);
    } else {
        throw new Exception('Wrong argument type (must be a list, a string or a pakeFinder object).');
    }

    if ($relative and $target_dir) {
        $files = preg_replace('/^'.preg_quote(realpath($target_dir), '/').'/', '', $files);

        // remove leading /
        $files = array_map(create_function('$f', 'return 0 === strpos($f, DIRECTORY_SEPARATOR) ? substr($f, 1) : $f;'), $files);
    }

    return $files;
});

Pake::enhance('writeAction', function($action, $desc, $style='PARAMETER') {
	return Pake::writeln(sprintf('[%20s|%s]    %s', $action, $style, $desc));
});

Pake::enhance('phar', function($pharName) {
	return function() use ($pharName) {
		Phar::create($pharName);
	};
});

Pake::enhance('create_pear_package', function($packageXml='package.xml') {
	if (!class_exists('PEAR_Packager')) {
        @include('PEAR/Packager.php');

        if (!class_exists('PEAR_Packager')) {
            // falling back to cli-call
			$cmd = 'pear package';
			passthru($cmd.' 2>&1', $return);
            return;
        }
    }

    $packager = new PEAR_Packager();
    $packager->debug = 0; // silence output
    $archive = $packager->package($packageXml, true);
	return $archive;
});

Pake::enhance('mirror', function($arg, $origin_dir, $target_dir, $options = array()) {
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
			throw new Exception(sprintf('Unable to determine "%s" type', $file));
		}
	}
});

Pake::enhance('copy', function($src, $target, $vars=null) {
	Pake::writeAction('copy', $src . ' to ' . $target);
	$package = file_get_contents($src);
	if(!is_null($vars)) {
		foreach($vars as $k => $v) {
			$package = str_replace('##'.$k.'##', $v, $package);
		}
	}
	if(!is_dir(dirname($target))) {
		Pake::mkdirs(dirname($target));
	}
	file_put_contents($target, $package);
});

Pake::enhance('move', function($src, $target) {
	Pake::writeAction('move', $src . ' to ' . $target);
	copy($src, $target);
	unlink($src);
});

Pake::enhance('rm', function($arg, $target_dir='') {
	$files = array_reverse(Pake::_getFinderFromArg($arg, $target_dir));
	foreach($files as $target) {
		if(!file_exists($target)) {
			Pake::writeAction('rm', $target . ' does not exist', Pake::INFO);
			return;
		}
		Pake::writeAction('rm', $target);
		if(is_dir($target) && !is_link($target)) {
			rmdir($target);
		} else {
			unlink($target);
		}
	}
});

Pake::enhance('touch', function($arg, $target_dir='') {
	$files = Pake::_getFinderFromArg($arg, $target_dir);
	foreach($files as $file) {
		Pake::writeAction('touch', $file);
		touch($file);
	}
});

Pake::enhance('replace_tokens_to_dir', function($arg, $src, $target, $tokens) {
	$files = Pake::_getFinderFromArg($arg, $src, true);
	foreach($files as $file) {
		Pake::writeAction('tokens', $target . DIRECTORY_SEPARATOR . $file);
		$content = file_get_contents($src . DIRECTORY_SEPARATOR . $file);
		foreach($tokens as $k => $v) {
			$content = str_replace('##'.$k.'##', $v, $content);
		}
		file_put_contents($target . DIRECTORY_SEPARATOR . $file, $content);
	}
});

Pake::enhance('replace_tokens', function($arg, $src, $tokens) {
	Pake::replace_tokens_to_dir($arg, $src, $src, $tokens);
});

Pake::enhance('mkdirs', function($path, $mode = 0777) {
	if(is_dir($path)) {
		return true;
	}
	if(file_exists($path)) {
		throw new Exception('Can not create directory at "'.$path.'" as place is already occupied by file');
	}

	Pake::writeAction('dir+', $path);
	return @mkdir($path, $mode, true);
});

Pake::enhance('rename', function($origin, $target) {
	if(is_readable($target)) {
		throw new Exception(sprintf('Cannot rename because the target "%" already exist.', $target));
	}
	Pake::writeAction('rename', $origin.' > '.$target);
	rename($origin, $target);
});

Pake::enhance('symlink', function($origin_dir, $target_dir, $copy_on_windows = false) {
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
});

Pake::enhance('chmod', function($arg, $target_dir, $mode, $umask = 0000) {
	$current_umask = umask();
	umask($umask);

	$files = Pake::_getFinderFromArg($arg, $target_dir, true);

	foreach ($files as $file) {
		Pake::writeAction(sprintf('chmod %o', $mode), $target_dir.DIRECTORY_SEPARATOR.$file);
		chmod($target_dir.DIRECTORY_SEPARATOR.$file, $mode);
	}

	umask($current_umask);
});

Pake::enhance('sh', function($cmd, $interactive = false) {
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
});

Pake::task('pake:help', 'Display this help message')->
run(function() {
	Pake::writeln('Usage:', Pake::SECTION)
		->writeln('pake [options] <task>');
		Pake::getArgs()->printOptions();
		Pake::nl()
		->writeln('Available tasks:', Pake::SECTION);
	foreach(Pake::getDefinedTasks() as $key => $task) {
		//Pake::writeAction($task->getName(), $task->getDescription());
		if($task->getName() != $key) {
			Pake::writeAction($key, 'Alias for ['.$task->getName().'|INFO]');
		} else {
			Pake::writeAction($key, $task->getDescription());
		}
	}
});
Pake::alias('pake:help', 'help');