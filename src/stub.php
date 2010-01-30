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

require __DIR__ . '/com/pagosoft/pake/pake_functions.php';

use com\pagosoft\pake\Executor,
	com\pagosoft\pake\Pake,
	com\pagosoft\pake\CyclicResolutionPakefileFactory;

$args = $_SERVER['argv'];
array_shift($args);

$ex = new Executor($args, new CyclicResolutionPakefileFactory());
Pake::setExecutor($ex);
$ex->loadPakefile();
$ex();