<?php
use pake\Pake;
use pake\ext\Phar;
use pake\ext\PHPUnit;
use pake\ext\Pirum;

Pake::property('pake.version', '0.7.0');
Pake::loadProperties();

$testfiles = Pake::fileset()
	->name('*Test.php')
	->in('test');
PHPUnit::task('unit-test', 'Run all tests',
	$testfiles);

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
		Pake::mkdirs('build/pake');
		Pake::mirror($lib, 'lib', 'build/pake', Pake::SILENT);
		Pake::mirror(Pake::finder(), 'src', 'build/pake', Pake::SILENT);
	});
	
Pake::task('set-version', 'Changes the pake version for the release')
	->option('version')
		->desc('Set the version to the specified value')
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
		Pake::replace_in('build/pake/pake/Pake.php', function($f, $c) {
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
		
		Pake::mkdirs('build/bin');
		Pake::copy('bin/pake', 'build/bin/pake');
		Pake::copy('bin/pake.bat', 'build/bin/pake.bat');
		
		$class_files = Pake::fileset()->ignore_version_control()
			->relative()
			->in('build/pake');
	    $xml_classes = '';
	    foreach ($class_files as $file) {
	        $dir_name  = dirname($file);
	        $file_name = basename($file);
	        $xml_classes .= '<file role="php" name="'.$file.'"/>'."\n";
	    }
		
		Pake::copy('package.xml', 'build/package.xml', array(
			'VERSION' => Pake::property('pake.version'),
			'CURRENT_DATE' => date('Y-m-d'),
			'CLASS_FILES' => $xml_classes,
			'STABILITY' => 'beta'
		));

		Pake::create_pear_package('build/package.xml', 'dist');
	});

Pake::task('publish', 'Publish the pear package on pear channel')
	->dependsOn('dist')
	->run(function() {
		Pirum::onChannel(Pake::property('pear.dir'))
			->addLatestVersion('dist');
	});
	
Pake::task('sync-pear', 'install/remove channels and packages')
	->run(function() {
		Pake::dependencies()->in('lib')
			->fromChannel('pear.pagosoft.com')
				->package('util')
				->package('cli')
				->package('parser')
			->fromChannel('pear.php-tools.net')
				->package('vfsstream', 'alpha')
			->fromChannel('pear.symfony-project.com')
				->package('yaml')
			->sync();
	});