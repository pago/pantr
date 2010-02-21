<?php
use pake\Pake;
use pake\ext\Phar;
use pake\ext\PHPUnit;

Pake::property('pake.version', '0.6.1');

$testfiles = Pake::fileset()
	->name('*Test.php')
	->in('test');
PHPUnit::task('unit-test', 'Run all tests',
	$testfiles, $addVerbose=false);

Pake::task('clean', 'Remove unused files')
	->run(function() {
		Pake::beginSilent('clean', 'Cleaning up');
		
		// get rid of .DS_Store files
		Pake::rm(Pake::finder()->name('.DS_Store')->in('src'));
		Pake::rm('build', true);
		Pake::rm('dist', true);
		
		Pake::endSilent();
	});
	
Pake::task('init', 'Create build directory')
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
		Pake::mirror($lib, 'lib', 'build', Pake::SILENT);
		Pake::mirror(Pake::finder(), 'src', 'build', Pake::SILENT);
	});
	
Pake::task('set-version', 'Changes the pake version for the release')
	->option('version')
		->desc('Set the version to the specified value')
		->shorthand('v')
	->run(function($req) {
		if(isset($req['version'])) {
			Pake::property('pake.version', $req['version']);
			Pake::writeAction('set-version', $req['version']);
		}
	});

Pake::task('update-version', 'Updates the version number in the Pake.php file')
	->dependsOn('set-version', 'init')
	->run(function() {
		// update version number
		Pake::replace_in('build/pake/Pake.php', function($f, $c) {
			Pake::writeAction('rewrite', $f);
			return str_replace(
				"const VERSION='DEV';",
				"const VERSION='".Pake::property('pake.version')."';",
				$c
			);
		});
	});
	
Pake::task('dist', 'Create distribution package')
	->dependsOn('unit-test', 'update-version')
	->run(function() {
		Pake::mkdirs('dist');
		
		Phar::create('dist/pake.phar', 'build');
		
		Pake::copy('bin/pake', 'dist/pake');
		Pake::copy('bin/pake.bat', 'dist/pake.bat');
		Pake::chmod('pake', 'bin', 0777);
		
		Pake::copy('package.xml', 'dist/package.xml', array(
			'VERSION' => Pake::property('pake.version'),
			'CURRENT_DATE' => date('Y-m-d')
		));

		Pake::create_pear_package('dist/package.xml', 'dist');
	});