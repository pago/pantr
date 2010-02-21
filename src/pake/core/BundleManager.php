<?php
namespace pake\core;

use pake\Pake;

class BundleManager {
	private $homePathProvider;
	public function __construct(HomePathProvider $homePathProvider) {
		$this->homePathProvider = $homePathProvider;
	}
	
	public function registerIncludePath() {
		$path = $this->homePathProvider->get();
		if(!is_null($path)) {
			set_include_path(get_include_path() . PATH_SEPARATOR . $path);
		}
	}
	
	public function loadBundles() {
		$path = $this->homePathProvider->get();
		if(!is_null($path)) {
			// load all phar files
			$phars = Pake::fileset()
				->name('*.phar')
				->in($path);
			foreach($phars as $phar) {
				require_once $phar;
			}
		}
	}
}