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
namespace pantr\ext\PEAR;

use pantr\pantr;
use pantr\ext\Pearfarm\PackageSpec;

class Sync {
	private $in;
	private $channels;
	private $repo;
	
	public function __construct($in='') {
		if($in != '') {
			$this->in($in);
		}
		$this->channels = array();
		$this->fromChannel('__uri')
			->fromChannel('doc.php.net')
			->fromChannel('pear.php.net')
			->fromChannel('pecl.php.net');
		$this->lastChannel = null;
	}
	
	public function in($in) {
		if($in instanceof Repository) {
			$this->repo = $in;
		} else {
			$this->repo = new Repository($in);
			if(!$this->repo->exists()) {
				$this->repo->create();
			}
		}
		return $this;
	}
	
	private $lastChannel;
	public function fromChannel($channel) {
		if(!isset($this->channels[$channel])) {
			$this->channels[$channel] = array();
		}
		$this->lastChannel = $channel;
		return $this;
	}
	
	/** 
	 * Specify the required package
	 * @deprecated use #usePackage($pkg, $v) instead
	 */
	public function package($pkg, $v='') {
		return $this->usePackage($pkg, $v);
	}
	
	public function usePackage($pkg, $v='') {
		$this->channels[$this->lastChannel][$pkg] = $v;
		return $this;
	}
	
	public function registerIn(PackageSpec $packageSpec) {
		foreach($this->channels as $channel => $packages) {
			foreach($packages as $pkg => $version) {
				$packageSpec->addPackageDependency($pkg, $channel);
			}
		}
	}
	
	public function sync($silent=true) {
		$pkgs = $this->repo->listAllPackages();
		pantr::writeAction('sync', 'PEAR repository');
		foreach($this->channels as $channel => $packages) {
			// discover channel if it is new
			if(!isset($pkgs[$channel])) {
				if($silent) pantr::beginSilent('add', 'channel '.$channel);
				$this->repo->discoverChannel($channel);
				if($silent) pantr::endSilent();
				$pkgs[$channel] = array();
			}
			// now check if all packages from this channel are installed
			foreach($packages as $pkg => $version) {
				if(!in_array($pkg, $pkgs[$channel])) {
					// $channel/$pkg[-$version]
					if($silent) pantr::beginSilent('install', 'package '.$pkg);
					try {
						$this->repo->install($channel.'/'.$pkg.($version != '' ? '-'.$version : ''));
					} catch(\Exception $ex) {
						pantr::writeln($ex, pantr::WARNING);
					}
					if($silent) pantr::endSilent();
					$pkgs[$channel][] = $pkg;
				}
			}
			// check if packages are installed that are not specified
			foreach($pkgs[$channel] as $pkg) {
				if(!isset($packages[$pkg])) {
					if($silent) pantr::beginSilent('delete', 'package '.$pkg);
					$this->repo->uninstall($channel.'/'.$pkg);
					if($silent) pantr::endSilent();
				}
			}
		}
		// delete unknown channels
		foreach($pkgs as $channel => $packs) {
			if(!isset($this->channels[$channel])) {
				pantr::writeln($channel);
				foreach($packs as $pkg) {
					if($silent) pantr::beginSilent('delete', 'package '.$pkg);
					$this->repo->uninstall($channel.'/'.$pkg);
					if($silent) pantr::endSilent();
				}
				if($silent) pantr::beginSilent('delete', 'channel '.$channel);
				$this->repo->deleteChannel($channel);
				if($silent) pantr::endSilent();
			}
		}
		return $this;
	}
}