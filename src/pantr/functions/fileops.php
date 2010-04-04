<?php
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
namespace pantr\file;

use pantr\Finder;

/** 
 *  Invokes the specified function on the content of all specified
 *  files. The result will be written to the same file the input
 *  came from. This is supposed to be used when you're rewriting a
 *  file after having copied it in some build directory.
 *  
 *  @param FinderResult|string|array $files
 *  @param callable $fn
 */
function replace_in($files, $fn) {
	$files = \pantr\transformToIterable($files);
	
	foreach($files as $file) {
		$content = file_get_contents($file);
		$content = $fn($file, $content);
		file_put_contents($file, $content);
	}
}

/**
 * Mirrors a list of files to another directory.
 *
 * @param FinderResult|string $arg
 */
function mirror($files, $target_dir, $isSilent = false, $options = array()) {
	if(is_string($files)) {
		$files = \pantr\transformToIterable($files);
	}
	$origin_dir = $files->getSourceDirectory();
	$files = $files->getRelativeFilePaths();
	
	// switch silent <-> options
	if(is_array($isSilent)) {
		$options = $isSilent;
		$isSilent = false;
	}
	
	if($isSilent) {
		writeAction('mirror', $origin_dir.' -> '.$target_dir);
		pantr::beginSilent();
	}
	
	foreach($files as $file) {
		if(is_dir($origin_dir.DIRECTORY_SEPARATOR.$file)) {
			mkdirs($target_dir.DIRECTORY_SEPARATOR.$file);
		} else if(is_file($origin_dir.DIRECTORY_SEPARATOR.$file)) {
			copy($origin_dir.DIRECTORY_SEPARATOR.$file,
				$target_dir.DIRECTORY_SEPARATOR.$file, $options);
		} else if(is_link($origin_dir.DIRECTORY_SEPARATOR.$file)) {
			symlink($origin_dir.DIRECTORY_SEPARATOR.$file, $target_dir.DIRECTORY_SEPARATOR.$file);
		} else {
			throw new \Exception(sprintf('Unable to determine "%s" type', $file));
		}
	}
	
	if($isSilent) {
		pantr::endSilent();
	}
}

/**
 * Copies a single file from $src to $target.
 * Optionally replaces variables as specified in the
 * associative array $vars where each key is surrounded with '##' and '##'
 * or, if $vars is callable, it will place the result of invoking
 * this callable on the content of the source file into the target file.
 */
function copy($src, $target, $vars=null) {
	// read content and modify it
	$package = file_get_contents($src);
	if(!is_null($vars)) {
		if(is_callable($vars)) {
			$package = $vars($package);
		} else {
			foreach($vars as $k => $v) {
				$package = str_replace('##'.$k.'##', $v, $package);
			}
		}
	}
	
	// make path from pattern
	$target = fileNameTransform($src, $target);
	
	if(!is_dir(dirname($target))) {
		mkdirs(dirname($target));
	}
	writeAction('copy', $src . ' to ' . $target);
	file_put_contents($target, $package);
}

/**
 * Moves a single file.
 */
function move($src, $target) {
	pantr::writeAction('move', $src . ' to ' . $target);
	\copy($src, $target);
	\unlink($src);
}

/**
 * Removes the specified file or files.
 *
 * @param FinderResult|string $arg
 */
function rm($files, $recursive=null) {
	$files = \pantr\transformToIterable($files);
	$files = array_reverse($files);
	foreach($files as $target) {
		if(!file_exists($target)) {
			pantr::writeAction('rm', $target . ' does not exist', pantr::INFO);
			return;
		}
		
		if(is_dir($target) && !is_link($target)) {
			// remove all files
			rm(fileset()->in($target));

			// and now all empty directories
			rm(finder(self::TYPE_DIR)->in($target));
			pantr::writeAction('rm', $target);
			\rmdir($target);
		} else {
			pantr::writeAction('rm', $target);
			\unlink($target);
		}
	}
}

function touch($files) {
	$files = \pantr\transformToIterable($files);
	foreach($files as $file) {
		pantr::writeAction('touch', $file);
		\touch($file);
	}
}

function replace_tokens_to_dir($files, $target, $tokens) {
	if(is_string($files)) {
		$files = \pantr\transformToIterable($files);
	}
	$src = $files->getSourceDirectory();
	$files = $files->getRelativeFilePaths();
	
	foreach($files as $file) {
		pantr::writeAction('tokens', $target . DIRECTORY_SEPARATOR . $file);
		$content = file_get_contents($src . DIRECTORY_SEPARATOR . $file);
		foreach($tokens as $k => $v) {
			$content = str_replace('##'.$k.'##', $v, $content);
		}
		file_put_contents($target . DIRECTORY_SEPARATOR . $file, $content);
	}
}

function replace_tokens($files, $tokens) {
	$files = \pantr\transformToIterable($files);
	foreach($files as $file) {
		pantr::writeAction('tokens', $file);
		$content = file_get_contents($file);
		foreach($tokens as $k => $v) {
			$content = str_replace('##'.$k.'##', $v, $content);
		}
		file_put_contents($file, $content);
	}
}

function mkdirs($path, $mode = 0777) {
	if(is_dir($path)) {
		return true;
	}
	if(file_exists($path)) {
		throw new \Exception('Can not create directory at "'.$path.'" as place is already occupied by file');
	}

	pantr::writeAction('dir+', $path);
	return @\mkdir($path, $mode, true);
}

function rename($origin, $target) {
	if(is_readable($target)) {
		throw new \Exception(sprintf('Cannot rename because the target "%" already exist.', $target));
	}
	pantr::writeAction('rename', $origin.' > '.$target);
	\rename($origin, $target);
}

function symlink($origin_dir, $target_dir, $copy_on_windows = false) {
	if(!function_exists('symlink') && $copy_on_windows) {
		$finder = Finder::type('any')->ignore_version_control();
		pantr::mirror($finder->in($origin_dir), $target_dir);
		return;
	}

	$ok = false;
	if(is_link($target_dir)) {
		if(readlink($target_dir) != $origin_dir) {
			\unlink($target_dir);
		} else {
			$ok = true;
		}
	}

	if(!$ok) {
		pantr::writeAction('link+', $target_dir);
		\symlink($origin_dir, $target_dir);
	}
}

function chmod($files, $mode, $umask = 0000) {
	$current_umask = umask();
	umask($umask);

	$files = \pantr\transformToIterable($files);

	foreach ($files as $file) {
		pantr::writeAction(sprintf('chmod %o', $mode), $file);
		\chmod($file, $mode);
	}

	umask($current_umask);
}

function sh($cmd, $interactive = false) {
	$verbose = true;
	pantr::writeAction('exec ', $cmd);

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