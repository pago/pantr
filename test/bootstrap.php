<?php
require_once 'PHPUnit/Framework.php';
$loader = function($dir) {
	return function($classname) use ($dir) {
		$file = __DIR__ . '/../'.$dir.'/' . str_replace('\\', DIRECTORY_SEPARATOR, $classname) . '.php';

		if(file_exists($file)) {
			require_once $file;
			return true;
		}
		return false;
	};
};

spl_autoload_register($loader('src'));
spl_autoload_register($loader('lib'));

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__.'/../lib/');