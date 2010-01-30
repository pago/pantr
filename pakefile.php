<?php
use com\pagosoft\pake\Pake;
use com\pagosoft\pake\Phar;

Pake::task('test', 'A simple test task')->
run(function() {
	echo "Hello World!\n";
});

Pake::task('clean', 'Remove unused files')
	->run(function() {
		if(file_exists('dist/pake.phar')) {
			unlink('dist/pake.phar');
		}
	});

Pake::task('init', 'Create dist environment')
	->run(function() {
		if(!file_exists('dist')) {
			mkdir('dist');
		}
	});
	
Pake::task('dist', 'Create distribution package')
	->dependsOn('clean', 'init', 'phar')
	->run(function() {
		copy('pake.phar', 'dist/pake.phar');
		unlink('pake.phar');
		copy('bin/pake', 'dist/pake');
		copy('bin/pake.bat', 'dist/pake.bat');
		chmod('bin/pake', 0777);
	});
	
Pake::task('pear-package', 'Create a pear package from distribution package')
	->dependsOn('dist')
	->run(function() {
		$package = file_get_contents('package.xml');
		$keys = array('##PAKE_VERSION##', '##CURRENT_DATE##');
		$values = array(Pake::VERSION, date('Y-m-d'));
		$package = str_replace($keys, $values, $package);
		file_put_contents('dist/package.xml', $package);
		
		chdir('dist');
		if (!class_exists('PEAR_Packager')) {
            @include('PEAR/Packager.php');

            if (!class_exists('PEAR_Packager')) {
                // falling back to cli-call
				$cmd = 'pear package';
				passthru($cmd.' 2>&1', $return);
                return;
            }
        }

        $packager = new PEAR_Packager();
        $packager->debug = 0; // silence output
        $archive = $packager->package('package.xml', true);
		chdir('..');
	});

Pake::task('phar', 'Create a phar archive')
	->run(function() {
		Phar::create('pake.phar');
	});