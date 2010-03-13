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
require_once __DIR__.DIRECTORY_SEPARATOR.'autoload.php';

use pake\Pake;
if(!class_exists('pake\Pake')) {
	exit("pake has not been installed properly!\n");
}

// load dependency injection container
sfServiceContainerAutoloader::register();
if(file_exists(__DIR__.'/pake/core/services.php')) {
	require_once __DIR__.'/pake/core/services.php';
	$sc = new PakeContainer();
} else {
	$sc = new sfServiceContainerBuilder();
	$loader = new sfServiceContainerLoaderFileYaml($sc);
	$loader->load(__DIR__.'/pake/core/services.yml');
}
// drop script name from args
$args = $_SERVER['argv'];
array_shift($args);

// setup pake
Pake::setTaskRepository($sc->taskRepository);
Pake::setApplication($sc->application);
Pake::setHomePathProvider($sc->homePathProvider);

// load standard tasks
include_once __DIR__.'/pake/std_tasks.php';

// include bundles
$bundleManager = $sc->bundleManager;
$bundleManager->registerIncludePath();
$bundleManager->loadBundles();
Pake::setBundleManager($bundleManager);

// display pake info and run it
Pake::writeInfo();
Pake::run($args);