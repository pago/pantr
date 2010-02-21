<?php
use pake\Pake;
use pake\Phar;
use pake\ext\PHPUnit;

PHPUnit::task('unit-test', 'Run all tests',
	Pake::fileset()
		->name('*Test.php')
		->in('test'));
		
Pake::task('test', 'description')
	->dependsOn('clean')
	->run(function() {
		$lib = Pake::finder()
			->discard('vfsStream')->prune('vfsStream')
			->prune('pear')->discard('pear')
			->prune('data')->discard('data')
			->prune('.*')->discard('.*')
			->not_name('.*')
			->ignore_version_control();
		
		// copy source files
		Pake::mkdirs('build');
		Pake::mirror(Pake::finder(), 'src', 'build');
		Pake::mirror($lib, 'lib', 'build');
	});

Pake::task('clean', 'Remove unused files')
	->run(function() {
		// get rid of .DS_Store files
		Pake::rm(Pake::finder()->name('.DS_Store')->in('src'));
		Pake::rm('build', true);
	});

Pake::task('init', 'Create dist environment')
	->run(function() {
		Pake::mkdirs('dist');
	});

Pake::task('phar', 'Create a phar archive')
	->run(Pake::phar('pake.phar', array('src', 'lib')));
	
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