<?php
require_once 'PHPUnit/Framework.php';

spl_autoload_register(function($classname) {
	$file = __DIR__ . '/../src/' . str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';

	if(file_exists($file)) {
		require_once $file;
		return true;
	}
	return false;
});