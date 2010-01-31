<?php
// pake class loader
spl_autoload_register(function($classname) {
	$file = str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';
	
	if(file_exists($file)) {
		require_once $file;
		return true;
	}
	return false;
});

if(!class_exists('com\pagosoft\pake\Pake')) {
	exit("pake has not been installed properly!\n");
}

require __DIR__ . '/lib/pagosoft_parser.phar';
require __DIR__ . '/lib/pagosoft_util.phar';
require __DIR__ . '/lib/pagosoft_cli.phar';

use com\pagosoft\pake\Executor,
	com\pagosoft\pake\Pake,
	com\pagosoft\pake\CyclicResolutionPakefileFactory;

// drop script name from args
$args = $_SERVER['argv'];
array_shift($args);

// create executor and load pakefile
$ex = new Executor($args, new CyclicResolutionPakefileFactory());
Pake::setExecutor($ex);
$ex->printPakeInfo();
$ex->loadPakefile();

// invoke pake
$ex();