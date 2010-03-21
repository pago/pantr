<?php
namespace PSpec;

use pantr\pantr;
use pantr\ext\PEAR\Repository;
use pantr\ext\PEAR\Sync;

describe('PEAR Sync', function($self) {
	// delete pear dir
	afterAll(function($self) {
		pantr::silent(function() use (&$self) {
			pantr::rm($self->peardir);
		});
	});
	
	$self->peardir = __DIR__.'/../../../tmp/pear';
	pantr::silent(function() use (&$self) {
		pantr::mkdirs($self->peardir);
	});
	$self->peardir = realpath($self->peardir);
	$self->repo = new Repository($self->peardir);
	$self->repo->create();
	
	it('should discover new channels', function($self) {
		$sync = new Sync($self->repo);
		$channels = $self->repo->listChannels();
		the($channels)->shouldNotContain('pear.pagosoft.com');
		$sync->fromChannel('pear.pagosoft.com');
		pantr::silent(function() use (&$sync) {
			$sync->sync();
		});
		$channels = $self->repo->listChannels();
		the($channels)->shouldContain('pear.pagosoft.com');
	});
	
	it('should install new packages', function($self) {
		// definitly needs mocking
		skip();
		
		$sync = new Sync($self->repo);
		$sync->fromChannel('pear.pagosoft.com')
			->usePackage('cli');
		
		$pkgs = $self->repo->listAllPackages();
		theValueOf($pkgs)->shouldHaveKey('pear.pagosoft.com');
		theValueOf($pkgs['pear.pagosoft.com'])->shouldNotContain('cli');
		pantr::silent(function() use ($sync) {
			$sync->sync();
		});
		theFile($self->peardir.'/pgs')->shouldExist();
		$pkgs = $self->repo->listAllPackages();
		theValueOf($pkgs['pear.pagosoft.com'])->shouldContain('cli');
	});
	
	it('should remove packages', function($self) {
		skip();
		
		$sync = new Sync($self->repo);
		$sync->fromChannel('pear.pagosoft.com');
		
		$pkgs = $self->repo->listAllPackages();
		theValueOf($pkgs)->shouldHaveKey('pear.pagosoft.com');
//		theValueOf($pkgs['pear.pagosoft.com'])->shouldContain('util');
		pantr::silent(function() use ($sync) {
			$sync->sync();
		});
		theFile($self->peardir.'/pgs')->shouldNotExist();
//		$pkgs = $self->repo->listAllPackages();
//		theValueOf($pkgs['pear.pagosoft.com'])->shouldNotContain('util');
	});
	
	it('should remove channels', function($self) {
		$sync = new Sync($self->repo);
		
		$channels = $self->repo->listChannels();
		the($channels)->shouldContain('pear.pagosoft.com');
		pantr::silent(function() use ($sync) {
			$sync->sync();
		});
		$channels = $self->repo->listChannels();
		the($channels)->shouldNotContain('pear.pagosoft.com');
	});
});