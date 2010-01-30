<?php
namespace com\pagosoft\pake;

class CyclicResolutionPakefileFactory implements PakefileFactory {
	public function getPakefile($name) {
		$start = $here = getcwd();
		while(!$this->pakefileExists($name)) {
			chdir('..');
			if(getcwd() == $here) {
				chdir($start);
				throw new Exception('Could not locate pakefile. Was looking for '.$name.'.php');
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