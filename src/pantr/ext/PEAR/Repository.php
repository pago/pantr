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

require_once 'PEAR/Registry.php';
require_once 'PEAR/Command.php';

class Repository {
	private $root, $config, $registry, $executor;
	
	public function __construct($root) {
		// root may not end with '/'
		if ($root[strlen($root) - 1] == '/') {
			$root = substr($root, 0, strlen($root) - 1);
		}
		$this->root = $root;
		
		if($this->exists()) {
			$this->prepare();
		}
	}
	
	public function discoverChannel($channel) {
		$this->executor->invoke('channel-discover', $channel);
		return $this;
	}
	
	public function deleteChannel($channel) {
		$this->executor->invoke('channel-delete', $channel);
		return $this;
	}
	
	public function updateChannels() {
		$this->executor->invoke('update-channels');
		return $this;
	}
	
	public function install() {
		$args = func_get_args();
		echo "args:\n"; print_r($args);
		if(is_array($args[0])) {
			$options = array_shift($args);
		} else {
			$options = array();
		}
		$this->executor->invoke('install', $options, $args);
		return $this;
	}
	
	public function uninstall() {
		$args = func_get_args();
		if(is_array($args[0])) {
			$options = array_shift($args);
		} else {
			$options = array();
		}
		$this->executor->invoke('uninstall', $options, $args);
		return $this;
	}
	
	public function upgrade() {
		$args = func_get_args();
		if(count($args) == 0) {
			$args = array();
			$options = array();
		} else if(is_array($args[0])) {
			$options = array_shift($args);
		} else {
			$options = array();
		}
		$this->executor->invoke('upgrade', $options, $args);
		return $this;
	}
	
	public function upgradeAllPackages($options=array()) {
		$this->executor->invoke('upgrade', $options);
	}
	
	public function listChannels() {		
		return $this->registry->listChannels();
	}
	
	public function listAllPackages() {
		return $this->registry->listAllPackages();
	}
	
	public function exists() {
		return file_exists($this->root . '/.pearrc');
	}
	
	public function hasChannel($channel) {
		return in_array($channel, $this->listChannels());
	}
	
	public function create() {
		$old = error_reporting(0);
		
		$root = $this->root;
		$configFile = $root . '/.pearrc';

		$config = new \PEAR_Config($configFile, '#no#system#config#', false, false);

		$config->noRegistry();
		$config->set('php_dir',      $root, 'user');
		$config->set('data_dir',     "$root/pear/data");
		$config->set('www_dir',      "$root/pear/www");
		$config->set('cfg_dir',      "$root/pear/cfg");
		$config->set('ext_dir',      "$root/pear/ext");
		$config->set('doc_dir',      "$root/pear/docs");
		$config->set('test_dir',     "$root/pear/tests");
		$config->set('cache_dir',    "$root/pear/cache");
		$config->set('download_dir', "$root/pear/download");
		$config->set('temp_dir',     "$root/pear/temp");
		$config->set('bin_dir',      "$root/");
		$config->writeConfigFile();
		
		error_reporting($old);
		
		$this->prepare();
	}
	
	private function prepare() {
		if(is_null($this->config)) {
			$this->config = \PEAR_Config::singleton($this->root . '/.pearrc', '#no#system#config');
		}
		$this->executor = new Executor($this->config);
		if(is_null($this->registry)) {
			$this->registry = new \PEAR_Registry();
			$this->registry->setConfig($this->config);
		}
	}
}