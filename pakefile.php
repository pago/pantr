<?php
use com\pagosoft\pake\Pake;
use com\pagosoft\pake\Phar;

Pake::setDefault('test');

Pake::task('foo', 'Testing foo')
	->dependsOn('bar')
	->run(function() {
		echo "foo\n";
	});

Pake::task('bar', 'Testing bar')
	->run(function() {
		echo "bar\n";
	});

Pake::task('test', 'Test')
	->dependsOn('foo', 'bar')
	->run(function() {
		echo "Hello World!\n";
	});

Pake::task('clean', 'Remove unused files')
	->run(function() {
		if(file_exists('dist/pake.phar')) {
			unlink('dist/pake.phar');
		}
	});
	
Pake::task('dist', 'Create distribution package')
	->dependsOn('clean')
	->run(function() {
		if(!file_exists('dist')) {
			mkdir('dist');
		}
		Pake::run('phar');
		copy('pake.phar', 'dist/pake.phar');
		unlink('pake.phar');
		copy('bin/pake', 'dist/pake');
		chmod('bin/pake', 0777);
	});

Pake::task('phar', 'Create a phar archive')
	->run(function() {
		Phar::create('pake.phar');
	});