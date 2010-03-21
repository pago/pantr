<?php
namespace PSpec;

use pantr\pantr;
use pantr\ext\PEAR\Repository;

describe('PEAR Repository', function($self) {
	// delete pear dir
	afterAll(function($self) {
		pantr::silent(function() use (&$self) {
			pantr::rm($self->peardir);
		});
	});
	
	// preparation
	$self->peardir = __DIR__.'/../../../tmp/pear';
	pantr::silent(function() use (&$self) {
		pantr::mkdirs($self->peardir);
	});
	$self->peardir = realpath($self->peardir);
	$self->repo = new Repository($self->peardir);
	$self->repo->create();
	
	it('should create a new repository if there is none', function($self) {
		// in case it has already been created we just delete it
		pantr::silent(function() use ($self) {
			pantr::rm($self->peardir);
			pantr::mkdirs($self->peardir);
		});
		theFile($self->peardir.'/.pearrc')->shouldNotExist();
		
		$repo = new Repository($self->peardir);
		theValueOf($repo->exists())->shouldBe(false);
		
		$repo->create();
		theValueOf($repo->exists())->shouldBeTrue();
		theFile($self->peardir.'/.pearrc')->shouldExist();
	});
	
	it('should discover a channel', function($self) {
		$discovery = function() use ($self) {
			$self->repo->discoverChannel('pear.pagosoft.com');
		};
		evaluating($discovery)->shouldProduceOutput(
			"Adding Channel \"pear.pagosoft.com\" succeeded\n"
			. "Discovery of channel \"pear.pagosoft.com\" succeeded\n");
	});
	
	it('should install a package', function($self) {
		ob_start();
		$self->repo->install('pgs/util');
		ob_end_clean();
		theFile($self->peardir.'/pgs')->shouldExist();
	});
	
	it('should uninstall a package', function($self) {
		ob_start();
		$self->repo->uninstall('pgs/util');
		ob_end_clean();
		theFile($self->peardir.'/pgs')->shouldNotExist();
	});
});