<?php
namespace pake\core;

/*
 * Locates a pakefile by looking at the current working directory and all of its
 * parent directories.
 */
class CyclicResolutionPakefileFactory implements PakefileFactory {
	private $path;
	
	public function __construct($path=null) {
		if(is_null($path)) {
			$this->path = getcwd();
		} else {
			$this->path = $path;
		}
	}
	
	public function getPakefile($name) {
		$here = $this->path;
		while(!$this->pakefileExists($here, $name)) {
			$parent = dirname($here);
			if($parent == $here) {
				return null;
			}
			$here = $parent;
		}
		$pakefile = new Pakefile($this->getPakefilePath($here, $name));
		return $pakefile;
	}
	
	private function pakefileExists($here, $name) {
		return file_exists($this->getPakefilePath($here, $name));
	}
	
	private function getPakefilePath($here, $name) {
		return $here . DIRECTORY_SEPARATOR . $name . '.php';
	}
}