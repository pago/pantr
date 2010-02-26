<?php
use pake\Pake;

// pake class loader
spl_autoload_register(function($classname) {
	$file = __DIR__.DIRECTORY_SEPARATOR.str_replace(
		array('\\', '_'),
		array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR),
		$classname
	) . '.php';
	
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
if(file_exists(__DIR__.'/SymfonyComponents')) {
	require_once __DIR__.'/SymfonyComponents/YAML/sfYaml.php';
	require_once __DIR__.'/SymfonyComponents/DependencyInjection/lib/sfServiceContainerAutoloader.php';
} else {
	// most likely running a local pake copy
	require_once __DIR__.'/../lib/SymfonyComponents/YAML/sfYaml.php';
	require_once __DIR__.'/../lib/SymfonyComponents/DependencyInjection/lib/sfServiceContainerAutoloader.php';
}

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

// include bundles
$bundleManager = $sc->bundleManager;
$bundleManager->registerIncludePath();
$bundleManager->loadBundles();

// display pake info and run it
Pake::writeInfo();
Pake::run($args);