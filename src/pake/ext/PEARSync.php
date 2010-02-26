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
namespace pake\ext;

use pake\Pake;

class PEARSync {
	private $in;
	private $channels;
	
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
		if(is_dir($in)) {
			$file = $in . DIRECTORY_SEPARATOR . '.pearrc';
			if(!file_exists($file)) {
				PEAR::init($in);
			}
		} else {
			$file = $in;
		}
		$this->in = $file;
		return $this;
	}
	
	private $lastChannel;
	public function fromChannel($channel) {
		$this->channels[$channel] = array();
		$this->lastChannel = $channel;
		return $this;
	}
	
	public function package($pkg, $v='') {
		$this->channels[$this->lastChannel][$pkg] = $v;
		return $this;
	}
	
	public function sync($silent=true) {
		require_once 'PEAR/Registry.php';
		$registry = new \PEAR_Registry('lib');
		$pkgs = $registry->listAllPackages();
		$pear = new PEAR($this->in);
		Pake::writeAction('sync', 'PEAR repository');
		foreach($this->channels as $channel => $packages) {
			// discover channel if it is new
			if(!isset($pkgs[$channel])) {
				if($silent) Pake::beginSilent('add', 'channel '.$channel);
				$pear->channel_discover($channel);
				if($silent) Pake::endSilent();
				$pkgs[$channel] = array();
			}
			// now check if all packages from this channel are installed
			foreach($packages as $pkg => $version) {
				if(!in_array($pkg, $pkgs[$channel])) {
					// $channel/$pkg[-$version]
					if($silent) Pake::beginSilent('install', 'package '.$pkg);
					try {
						$pear->install($channel.'/'.$pkg.($version != '' ? '-'.$version : ''));
					} catch(\Exception $ex) {
						Pake::writeln($ex, Pake::WARNING);
					}
					if($silent) Pake::endSilent();
					$pkgs[$channel][] = $pkg;
				}
			}
			// check if packages are installed that are not specified
			foreach($pkgs[$channel] as $pkg) {
				if(!isset($packages[$pkg])) {
					if($silent) Pake::beginSilent('delete', 'package '.$pkg);
					$pear->uninstall($channel.'/'.$pkg);
					if($silent) Pake::endSilent();
				}
			}
		}
		// delete unknown channels
		foreach($pkgs as $channel => $packs) {
			if(!isset($this->channels[$channel])) {
				Pake::writeln($channel);
				print_r($packs);
				foreach($packs as $pkg) {
					if($silent) Pake::beginSilent('delete', 'package '.$pkg);
					$pear->uninstall($channel.'/'.$pkg);
					if($silent) Pake::endSilent();
				}
				if($silent) Pake::beginSilent('delete', 'channel '.$channel);
				$pear->channel_delete($channel);
				if($silent) Pake::endSilent();
			}
		}
	}
}