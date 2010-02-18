<?php
namespace pake\core;

/*
 * Locates a pakefile by looking at the current working directory and all of its
 * parent directories.
 */
class CyclicResolutionPakefileFactory implements PakefileFactory {
	public function getPakefile($name) {
		$start = $here = getcwd();
		while(!$this->pakefileExists($name)) {
			chdir('..');
			if(getcwd() == $here) {
				chdir($start);
				return null;
			}
			$here = getcwd();
		}
		$pakefile = new Pakefile($this->getPakefilePath($name));
		chdir($start);
		return $pakefile;
	}
	
	private function pakefileExists($name) {
		return file_exists($this->getPakefilePath($name));
	}
	
	private function getPakefilePath($name) {
		return getcwd() . DIRECTORY_SEPARATOR . $name . '.php';
	}
}