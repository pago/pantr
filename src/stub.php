<?php
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

use pake\Executor,
	pake\Pake,
	pake\CyclicResolutionPakefileFactory;

require_once 'SymfonyComponents/YAML/sfYaml.php';

// drop script name from args
$args = $_SERVER['argv'];
array_shift($args);

// create executor and load pakefile
$ex = new Executor($args, new CyclicResolutionPakefileFactory());
Pake::setExecutor($ex);
Pake::setHomePathProvider(new ServerHomePathProvider());
$ex->printPakeInfo();
$ex->loadPakefile();

// invoke pake
$ex();