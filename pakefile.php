<?php
/* 
 * Copyright (c) 2010 Patrick Gotthardt
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

use pake\Pake;
use pake\ext\File;
use pake\ext\PHPUnit;
use pake\ext\Pirum;
use pake\ext\Pearfarm\PackageSpec;

Pake::setDefault('build:package');
Pake::property('pake.version', '0.7.2');
Pake::loadProperties();

// global namespace
Pake::task('clean', 'Remove unused files')
	->run(function() {
		Pake::silent('clean', 'Cleaning up', function() {
			// get rid of .DS_Store files
			Pake::rm(Pake::finder()->name('.DS_Store')->in('src'));
			Pake::rm('build', true);
			Pake::rm('dist', true);
		});
	});

// tests
PHPUnit::task('test:unit', 'Run all tests');
	
// config
Pake::task('config:set-version', 'Changes the pake version for the release')
	->option('version')
		->desc('Set the version to the specified value')
	->run(function($req) {
		if(isset($req['version'])) {
			Pake::property('pake.version', $req['version']);
			Pake::writeAction('set-version', $req['version']);
		}
	});

Pake::task('config:sync-pear', 'install/remove channels and packages')
	->run(function() {
		Pake::dependencies()->in('lib')
			->fromChannel('pear.pagosoft.com')
				->usePackage('util')
				->usePackage('cli')
				->usePackage('parser')
			->fromChannel('pear.php-tools.net')
				->usePackage('vfsstream', 'alpha')
			->fromChannel('pear.symfony-project.com')
				->usePackage('yaml')
			->sync();
	});

// build process
Pake::task('build:init', 'Create build directory')
	->dependsOn('clean')
	->run(function() {
		$lib = Pake::finder()
			// we don't want to distribute vfsStream
			->discard('vfsStream')->prune('vfsStream')
			->prune('pear')->discard('pear') // or pear
			->prune('data')->discard('data')
			// or the IOC-documentation and tests
			->prune('doc')->discard('doc')
			->prune('test')->discard('test')
			// and no hidden files!
			->prune('.*')->discard('.*')
			->not_name('.*')
			->ignore_version_control();

		// copy source files
		Pake::mkdirs('build/pake');
		Pake::mirror($lib, 'lib', 'build/pake', Pake::SILENT);
		Pake::mirror(Pake::finder(), 'src', 'build/pake', Pake::SILENT);
		
		// and executables
		Pake::mkdirs('build/bin');
		Pake::copy('bin/pake', 'build/bin/pake');
		Pake::copy('bin/pake.bat', 'build/bin/pake.bat');
	});
	
Pake::task('build:package', 'description')
	->dependsOn('test:unit', 'build:init')
	->run(function() {
		$spec = PackageSpec::in('build')
			->setName('pake')
			->setChannel('pear.pagosoft.com')
			->setSummary('Pake is a simple php build tool.')
			->setDescription('Pake is a simple php build tool.')
			->setNotes('n/a')
			->setVersion(Pake::property('pake.version'))
			->setStability('beta')
			->setLicense(PackageSpec::LICENSE_MIT)
			->addMaintainer('lead', 'Patrick Gotthardt', 'pago', 'patrick@pagosoft.com')
			->setDependsOnPHPVersion('5.3.0')
			->addFiles(Pake::fileset()
					->ignore_version_control()
					->relative()
					->in('build'))
			->addFiles(array('bin/pake', 'bin/pake.bat'))
			->addExecutable('bin/pake')
			->addExecutable('bin/pake.bat', null, PackageSpec::PLATFORM_WIN)
			->writePackageFile();
		Pake::mkdirs('dist');
		Pake::create_pear_package('build/package.xml', 'dist');
	});

// compilation
File::task('compile:services', 'build/pake/pake/core/services.yml', ':dirname/:filename.php')
	->run(function($src, $target) {
		// compile dependency injection layer
		$sc = new sfServiceContainerBuilder();

		$loader = new sfServiceContainerLoaderFileYaml($sc);
		$loader->load($src);

		$dumper = new sfServiceContainerDumperPhp($sc);
		$code = $dumper->dump(array('class' => 'PakeContainer'));
		file_put_contents($target, $code);
	});
Pake::getTask('build:init')->after(Pake::getTask('compile:services'));

Pake::task('compile:version', 'Updates the version number in the Pake.php file')
	->dependsOn('config:set-version')
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
Pake::getTask('build:init')->after(Pake::getTask('compile:version'));


// publish
Pake::task('publish', 'Publish the pear package on pear channel')
	->dependsOn('build:package')
	->run(function() {
		Pirum::onChannel(Pake::property('pear.dir'))
			->addLatestVersion('dist');
	});