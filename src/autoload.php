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

// load ClassLoader framework
require_once implode(DIRECTORY_SEPARATOR,
	array(__DIR__, 'Pagosoft','ClassLoader', 'load.php'));

use Pagosoft\ClassLoader\DirectoryClassLoader;
use Pagosoft\ClassLoader\GlobalClassLoader;

// autoload this directory
$cl = new DirectoryClassLoader(__DIR__);
$cl->registerAutoload();

if(file_exists(__DIR__.'/../lib/pgs')) {
	// might be a local instance of pantr running so include lib directory
	$cl = new DirectoryClassLoader(__DIR__.'/../lib');
	$cl->registerAutoload();
	
	// load local symfony
	require_once __DIR__.'/../lib/SymfonyComponents/YAML/sfYaml.php';
	require_once __DIR__.'/../lib/SymfonyComponents/dependency-injection/lib/sfServiceContainerAutoloader.php';
}

if(!class_exists('sfYaml')) {
	require_once __DIR__.'/SymfonyComponents/YAML/sfYaml.php';
}
if(!class_exists('sfServiceContainerAutoloader')) {
	require_once __DIR__.'/SymfonyComponents/dependency-injection/lib/sfServiceContainerAutoloader.php';
}

// and everything else in the path (mostly for pantr bundle support)
$cl = new GlobalClassLoader();
$cl->registerAutoload();