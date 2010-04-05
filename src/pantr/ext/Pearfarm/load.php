<?php
use pantr\pantr;

// try to load pearfarm
$tryLoad = function() {
	foreach(explode(PATH_SEPARATOR, get_include_path()) as $path) {
		$dir = $path . DIRECTORY_SEPARATOR . 'pearfarm/src/Pearfarm';
		if(file_exists($dir)) {
			$classes = array('ITask', 'PackageSpec', 'Task'.DIRECTORY_SEPARATOR.'Push');
			foreach($classes as $class) {
				require_once $dir . DIRECTORY_SEPARATOR . $class . '.php';
			}
		}
	}
};

$tryLoad();
if(!class_exists('Pearfarm_PackageSpec')) {
	// try to install it now
	pantr::silent(function() {
		$repo = pantr::getRepository();
		if(!$repo->hasChannel('pearfarm')) {
			$repo->discoverChannel('pearfarm.pearfarm.org');
		}
		$repo->install('pearfarm/pearfarm');

		$tryLoad();
	});
	
	if(!class_exists('Pearfarm_PackageSpec')) {
		throw new \Exception('Pearfarm is not installed!');
	} else {
		pantr::writeAction('install', 'Pearfarm');
	}
}