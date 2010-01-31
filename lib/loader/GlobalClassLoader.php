<?php
namespace loader;

class GlobalClassLoader extends ClassLoader {
	public function loadClass($classname) {
		foreach($this->translate($classname) as $file) {
			if(file_exists($file)) {
				require_once $file;
				return true;
			}
		}
		return false;
	}
	
	public function getResource($resourceName) {
		if(file_exists($resourceName)) {
			return $resourceName;
		}
		return null;
	}
}