<?php
use pake\Pake;
use pake\Phar;

define('PROJECT_NAME', '##PROJECT_NAME##');
define('PHAR_NAME', '##PROJECT_NAME##.phar');
define('VERSION', '1.0.0');
define('STABILITY', 'alpha');

Pake::task('clean', 'Remove unused files')
	->run(function() {
		Pake::rm('dist/'.PHAR_NAME);
	});

Pake::task('init', 'Create dist environment')
	->run(function() {
		Pake::mkdirs('dist');
	});
	
Pake::task('phar', 'Create a phar archive')
	->run(Pake::phar(PHAR_NAME, array('src', 'lib')));
	
Pake::task('dist', 'Create distribution package')
	->dependsOn('clean', 'init', 'phar')
	->run(function() {
		Pake::move(PHAR_NAME, 'dist/'.PHAR_NAME);
	});

Pake::task('pear-package', 'Create a pear package from distribution package')
	->dependsOn('dist')
	->run(function() {
		$class_files = Pake::finder('file')->ignore_version_control()->name('*.php')->relative()->in('src');
	    $xml_classes = '';
	    foreach ($class_files as $file) {
	        $dir_name  = dirname($file);
	        $file_name = basename($file);
	        $xml_classes .= '<file role="php" baseinstalldir="'.$dir_name.'" install-as="'.$file_name.'" name="'.PROJECT_NAME.'/'.$file.'"/>'."\n";
	    }
		Pake::copy('package.xml', 'dist/package.xml', array(
			'PROJECT_NAME' => PROJECT_NAME,
			'VERSION' => VERSION,
			'STABILITY' => STABILITY,
			'CURRENT_DATE' => date('Y-m-d'),
			'CLASS_FILES' => $xml_classes
		));
	
		chdir('dist');
		Pake::create_pear_package();
		chdir('..');
	});