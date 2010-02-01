<?php
use pake\Pake;
use pake\Phar;
use pake\tasks;

Pake::task('test', 'A simple test task')->
run(function() {
	$args = Pake::getArgs();
	if(isset($args[1])) {
		Pake::writeAction('task', 'Hello ['.$args[1].'|COMMENT]');
	} else {
		Pake::writeAction('task', 'Hello World!');
	}
});

Pake::task('pear:install', 'Install pear package')
	->run(function() {
		$req = Pake::getArgs();
		chdir('lib');
		Pake::sh('my_pearanha install '.$req[1]);
		chdir('..');
	});

Pake::task('clean', 'Remove unused files')
	->run(function() {
		Pake::rm('dist/pake.phar');
	});

Pake::task('init', 'Create dist environment')
	->run(function() {
		Pake::mkdirs('dist');
	});
	
Pake::task('phar', 'Create a phar archive')->
run(Pake::phar('pake.phar', array('src', 'lib')));
	
Pake::task('dist', 'Create distribution package')
	->dependsOn('clean', 'init', 'phar')
	->run(function() {
		Pake::move('pake.phar', 'dist/pake.phar');
		Pake::copy('bin/pake', 'dist/pake');
		Pake::copy('bin/pake.bat', 'dist/pake.bat');
		Pake::chmod('pake', 'bin', 0777);
	});

Pake::task('pear-package', 'Create a pear package from distribution package')->
dependsOn('dist')->
run(function() {
	Pake::copy('package.xml', 'dist/package.xml', array(
		'VERSION' => Pake::VERSION,
		'CURRENT_DATE' => date('Y-m-d')
	));
	
	chdir('dist');
	Pake::create_pear_package();
	chdir('..');
});