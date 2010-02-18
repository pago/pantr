<?php
use pake\Pake;

// pake class loader
spl_autoload_register(function($classname) {
	$file = str_replace(
		array('\\', '_'),
		// phar packaging requires the use of '/' over DIRECTORY_SEPARATOR
		// as the latter won't work on windows (see #1)
		array('/', '/'), $classname) . '.php';
	
	if(file_exists($file)) {
		require_once $file;
		return true;
	}
	return false;
});

if(!class_exists('pake\Pake')) {
	exit("pake has not been installed properly!\n");
}

// load requirements
require_once 'SymfonyComponents/YAML/sfYaml.php';
require_once 'SymfonyComponents/DependencyInjection/lib/sfServiceContainerAutoloader.php';

// load dependency injection container
sfServiceContainerAutoloader::register();
$sc = new sfServiceContainerBuilder();
$loader = new sfServiceContainerLoaderFileYaml($sc);
$loader->load(__DIR__.'/pake/core/services.yml');

// drop script name from args
$args = $_SERVER['argv'];
array_shift($args);

// setup pake
Pake::setTaskRepository($sc->taskRepository);
Pake::setApplication($sc->application);
Pake::setHomePathProvider($sc->homePathProvider);

// load standard tasks
include_once __DIR__.'/pake/std_tasks.php';

// display pake info and run it
Pake::writeInfo();
Pake::run($args);