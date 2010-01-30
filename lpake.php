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

// pake class loader
spl_autoload_register(function($classname) {
	$file = __DIR__ . '/src/' . str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';

	if(file_exists($file)) {
		require_once $file;
		return true;
	}
	return false;
});

if(!class_exists('com\pagosoft\pake\Pake')) {
	exit("pake has not been installed properly!\n");
}

use com\pagosoft\pake\Pake;

$args = $_SERVER['argv'];
array_shift($args);

$phakefile = getcwd() . DIRECTORY_SEPARATOR . 'pakefile.php';
if(file_exists($phakefile)) {
	require_once $phakefile;
	if(count($args) > 0) {
		Pake::run($args[0]);
	} else {
		Pake::runDefault();
	}
} else {
	exit('Could not find pakefile at ' . $phakefile . "\n");
}