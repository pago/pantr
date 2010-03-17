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
namespace pantr\file;

use pantr\pantr;
use pantr\ext\File;
use pantr\ext\PHPUnit;
use pantr\ext\Pirum;
use pantr\ext\Pearfarm\PackageSpec;

pantr::setDefault('build:package');
pantr::property('pantr.version', '0.7.3');
pantr::property('pear.channel', 'pear.pagosoft.com');
pantr::loadProperties();

// global namespace
pantr::task('clean', 'Remove unused files')
	->run(function() {
		pantr::silent('clean', 'Cleaning up', function() {
			// get rid of .DS_Store files
			pantr::rm(pantr::finder()->name('.DS_Store')->in('src'));
			pantr::rm('build', true);
			pantr::rm('dist', true);
		});
	});

// tests
PHPUnit::task('test:unit', 'Run all tests');
	
// config
pantr::task('config:set-version', 'Changes the pantr version for the release')
	->option('version')
		->desc('Set the version to the specified value')
	->run(function($req) {
		if(isset($req['version'])) {
			pantr::property('pantr.version', $req['version']);
			pantr::writeAction('set-version', $req['version']);
		}
	});

pantr::task('config:deploy-local', 'Configures pantr to create a local pear package')
	->option('local')
		->shorthand('l')
	->run(function($req) {
		if(isset($req['l'])) {
			pantr::writeAction('switch', 'to local pear channel');
			pantr::property('pear.channel', 'pear.localhost');
			pantr::property('pear.dir', __DIR__.'/../lpear');
		}
	});

pantr::task('config:sync-pear', 'install/remove channels and packages')
	->run(function() {
		pantr::dependencies()->in('lib')
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
pantr::task('build:init', 'Create build directory')
	->dependsOn('clean')
	->run(function() {
		$lib = pantr::finder()
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
		pantr::mkdirs('build/pantr');
		pantr::mirror($lib, 'lib', 'build/pantr', pantr::SILENT);
		pantr::mirror(pantr::finder(), 'src', 'build/pantr', pantr::SILENT);
		
		// and executables
		pantr::mkdirs('build/bin');
		pantr::copy('bin/pantr', 'build/bin/pantr');
		pantr::copy('bin/pantr.bat', 'build/bin/pantr.bat');
	});
	
pantr::task('build:package', 'description')
	->dependsOn('test:unit', 'build:init')
	->before(pantr::getTask('config:deploy-local'))
	->run(function() {
		$spec = PackageSpec::in('build')
			->setName('pantr')
			->setChannel(pantr::property('pear.channel'))
			->setSummary('pantr is a simple php build tool.')
			->setDescription('pantr is a simple php build tool.')
			->setNotes('n/a')
			->setVersion(pantr::property('pantr.version'))
			->setStability('beta')
			->setLicense(PackageSpec::LICENSE_MIT)
			->addMaintainer('lead', 'Patrick Gotthardt', 'pago', 'patrick@pagosoft.com')
			->setDependsOnPHPVersion('5.3.0')
			->addFiles(pantr::fileset()
					->ignore_version_control()
					->relative()
					->in('build'))
			->addFiles(array('bin/pantr', 'bin/pantr.bat'))
			->addExecutable('bin/pantr')
			->addExecutable('bin/pantr.bat', null, PackageSpec::PLATFORM_WIN)
			->createPackage('dist');
	});

// compilation
File::task('compile:services', 'build/pantr/pantr/core/services.yml', ':dirname/:filename.php')
	->run(function($src, $target) {
		// compile dependency injection layer
		$sc = new sfServiceContainerBuilder();

		$loader = new sfServiceContainerLoaderFileYaml($sc);
		$loader->load($src);

		$dumper = new sfServiceContainerDumperPhp($sc);
		$code = $dumper->dump(array('class' => 'pantrContainer'));
		file_put_contents($target, $code);
	});
pantr::getTask('build:init')->after(pantr::getTask('compile:services'));

pantr::task('compile:version', 'Updates the version number in the pantr.php file')
	->run(function() {
		// update version number
		pantr::replace_in('build/pantr/pantr/pantr.php', function($f, $c) {
			pantr::writeAction('rewrite', $f);
			return str_replace(
				"const VERSION='DEV';",
				"const VERSION='".pantr::property('pantr.version')."';",
				$c
			);
		});
	})
	->before(pantr::getTask('config:set-version'));
pantr::getTask('build:init')->after(pantr::getTask('compile:version'));


// publish
pantr::task('deploy', 'Publish the pear package on pear channel')
	->dependsOn('build:package')
	->run(function() {
		Pirum::onChannel(pantr::property('pear.dir'))
			->addLatestVersion('dist');
	});