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