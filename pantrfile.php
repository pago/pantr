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
use Pagosoft\PSpec\Spec;
use pantr\ext\Pirum;
use pantr\ext\Pearfarm\PackageSpec;

setDefault('build');

$version = trim(`git describe`);
property('pantr.version', $version);
property('pear.channel', 'pear.pagosoft.com');

// global namespace
task('clean', 'Remove unused files', function() {
	silent('clean', 'Cleaning up', function() {
		// get rid of .DS_Store files
		rm(find('.DS_Store')->in('src'));
		rm('build', true);
		rm('dist', true);
	});
});

/** @hidden */
task('test:init', function() {
	require_once 'test/bootstrap.php';
});

/**
 * Run PHPUnit tests.
 * @option v:verbose display details
 * @dependsOn test:init
 */
task('test:unit', function($req) {
	$tests = fileset('*Test.php')->in('test/unit');
	return PHPUnit::forAllTests($tests)->run(isset($req['verbose']), 'test:unit');
});

/**
 * Runs PSpec tests
 *
 * @dependsOn test:init
 */
task('test:spec', function($req) {
	$result = Spec::run('test/spec', array(
		'verbose' => isset($req['verbose']),
		'taskname' => 'test:spec'
	));
});

/**
 * Runs PSpec "integration" tests - might take a while
 *
 * @dependsOn test:init
 */
task('test:integration', function($req) {
	return Spec::run('test/integration', array(
		'verbose' => isset($req['verbose']),
		'taskname' => 'test:integration'
	));
});

/**
 * Test all
 * @dependsOn test:unit, test:spec
 */
task('test');

// config

/**
 * Configures pantr to create a local pear package
 *
 * @needsRequest
 * @hidden
 */
task('config:deploy-local',function($req) {
	if(isset($req['l'])) {
		writeAction('switch', 'to local pear channel');
		property('pear.channel', 'pear.localhost');
		property('pear.dir', __DIR__.'/../lpear');
	}
});

/**
 * Install/remove channels and packages
 */
task('deps', function() {
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
/**
 * Create build directory
 *
 * @dependsOn clean
 */
task('build:init', function() {
	$lib = find()
		// we don't want to distribute vfsStream
		->prune('vfsStream')->discard('vfsStream')
		->prune('pear')->discard('pear') // or pear
		->prune('data')->discard('data')
		// or the IOC-documentation and tests
		->prune('doc')->discard('doc')
		->prune('test')->discard('test')
		// and no hidden files!
		->prune('.*')->discard('.*')
		->not_name('.*')
		->ignore_version_control()
		->in('lib');

	// copy source files
	mkdirs('build/pantr');
	mirror($lib, 'build/pantr', pantr::SILENT);
	mirror(find()->in('src'), 'build/pantr', pantr::SILENT);
	
	// and executables
	mkdirs('build/bin');
	copy('bin/pantr', 'build/bin/pantr');
	copy('bin/pantr.bat', 'build/bin/pantr.bat');
});

/**
 * Creates a PEAR package
 *
 * @dependsOn test, build:init
 * @before config:deploy-local
 */
task('build:package', function() {
	$spec = PackageSpec::in('build')
		->setName('pantr')
		->setChannel(property('pear.channel'))
		->setSummary('pantr is a simple php build tool.')
		->setDescription('pantr is a simple php build tool.')
		->setNotes('n/a')
		->setVersion(property('pantr.version'))
		->setStability('beta')
		->setLicense(PackageSpec::LICENSE_MIT)
		->addMaintainer('lead', 'Patrick Gotthardt', 'pago', 'patrick@pagosoft.com')
		->setDependsOnPHPVersion('5.3.0')
		->addFiles(fileset()
				->ignore_version_control()
				->relative()
				->in('build'))
		->addFiles(array('bin/pantr', 'bin/pantr.bat'))
		->addExecutable('bin/pantr')
		->addExecutable('bin/pantr.bat', null, PackageSpec::PLATFORM_WIN)
		->createPackage('dist');
});

// compilation
File::task('build:services', 'build/pantr/pantr/core/services.yml', ':dirname/:filename.php')
	->dependsOn('build:init')
	->run(function($src, $target) {
		// compile dependency injection layer
		$sc = new \sfServiceContainerBuilder();

		$loader = new \sfServiceContainerLoaderFileYaml($sc);
		$loader->load($src);

		$dumper = new \sfServiceContainerDumperPhp($sc);
		$code = $dumper->dump(array('class' => 'pantrContainer'));
		file_put_contents($target, $code);
	});

/**
 * Updates the version number in the pantr.php file
 * 
 * @hidden
 * @dependsOn build:init
 */
task('build:version', function() {
	// update version number
	replace_in('build/pantr/pantr/pantr.php', function($f, $c) {
		writeAction('rewrite', $f);
		return str_replace(
			"const VERSION='DEV';",
			"const VERSION='".property('pantr.version')."';",
			$c
		);
	});
});

/**
 * Builds the project
 * 
 * @dependsOn build:init, build:services, build:version, build:package
 */
task('build');

// publish
/**
 * Publish the pear package on pear channel
 *
 * @dependsOn build:package
 * @option l deploy to local PEAR server
 * @option version The release version
 */
task('deploy', function($args) {
	Pirum::onChannel(property('pear.dir'))
		->addLatestVersion('dist');
});