<?php
/* 
 * Copyright (c) 2009 Patrick Gotthardt
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
namespace pantr;

/*
 * This file defines functions in the \pantr namespace and includes the files
 * in the functions directory.
 */

/**
 * Transforms a file path according to the specified pattern.
 * Example:
 * $file = fileNameTransform('config.yml', ':filename.php');
 * would yield "config.php".
 * The available placeholders correspond to the information
 * retrieved by the pathinfo-function:
 * <dl>
 *  <dt>:filename</dt>
 *  <dd>the name of the file without its extension</dd>
 *  <dt>:basename</dt>
 *  <dd>the name of the file with extension</dd>
 *  <dt>:extension</dt>
 *  <dd>the extension of the file name</dd>
 *  <dt>:dirname</dt>
 *  <dd>the path to the file</dd>
 * </dl>
 *
 * If all you want is to change the file extension you may use the shorthand
 * "*.php" (to change the extension to "php" and keep all other parts)
 *
 * @param string $src
 * @param string $pattern
 * @return string
 */
function fileNameTransform($src, $pattern) {
	$info = pathinfo($src);
	if(strlen($pattern) > 2 && $pattern[0] == '*' && $pattern[1] == '.') {
		$pattern = ':dirname/:filename'. substr($pattern, 1);
	}
	$searches = array_map(function($p) { return ':'.$p; }, array_keys($info));
	return str_replace($searches, array_values($info), $pattern);
}

function transformToIterable($finder) {
	if(is_string($finder)) {
		if(file_exists($finder)) {
			return new FinderResult(array($finder), '.');
		}
		return Finder::type(Finder::TYPE_ANY)->name($finder)->in('.');
	} else if($finder instanceof Finder) {
		return $finder->in('.');
	} else if(is_array($finder)) {
		return $finder;
	} else if($finder instanceof \IteratorAggregate) {
		return $finder;
	}
	throw new \InvalidArgumentException('Argument must be one of string, array or finder');
}

function getSourceDirectory($finder) {
	if($finder instanceof FinderResult) {
		return $finder->getSourceDirectory();
	} else if(is_array($finder)) {
		return findCommonPrefix($finder);
	}
	throw new \InvalidArgumentException('Argument must be either an array or a FinderResult');
}

function findCommonPrefix($strings) {
	return array_reduce($strings, function($a, $b) {
		// first invocation yields $b
		if($a === null) return $b;
		
		// make sure we use the shortest string as the prefix lookup
		$lenA = strlen($a);
		$lenB = strlen($b);
		if($lenA < $lenB) {
			// switch strings
			$t = $b;
			$b = $a;
			$a = $t;
			
			// switch lengths
			$t = $lenB;
			$lenB = $lenA;
			$lenA = $t;
		}

		/*
		 * on subsequent calls we want reduce $a so that it is a prefix of $b
		 * i.e. $a = 'foo/bar/baz', $b = 'foo/bar/boo'
		 * should yield 'foo/bar/'
		 * 
		 * for efficiency reasons this should be implemented without substr...
		 */
		for($i = $lenA; $i >= 0; $i--) {
			if(substr($a, 0, $i) == substr($b, 0, $i)) {
				return substr($a, 0, $i);
			}
		}

		// there is no common prefix => ''
		return '';
	}, null);
}

// Include other functions for pantr\file namespace
require_once __DIR__.'/functions/utilities.php';
require_once __DIR__.'/functions/fileops.php';