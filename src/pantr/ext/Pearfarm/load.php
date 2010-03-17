<?php
// try to load pearfarm
foreach(explode(PATH_SEPARATOR, get_include_path()) as $path) {
	$dir = $path . DIRECTORY_SEPARATOR . 'pearfarm/src/Pearfarm';
	if(file_exists($dir)) {
		$classes = array('ITask', 'PackageSpec', 'Task'.DIRECTORY_SEPARATOR.'Push');
		foreach($classes as $class) {
			require_once $dir . DIRECTORY_SEPARATOR . $class . '.php';
		}
	}
}
if(!class_exists('Pearfarm_PackageSpec')) {
	throw new \Exception('Pearfarm is not installed!');
}