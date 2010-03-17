<?php
namespace pantr\ext\Pearfarm;

use pantr\pantr;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'load.php';

class PackageSpec extends \Pearfarm_PackageSpec {
	public static function in($basedir) {
		return new self(array(self::OPT_BASEDIR => $basedir));
	}
	
	public function setVersion($v) {
		$this->setApiVersion($v);
		$this->setReleaseVersion($v);
		return $this;
	}
	
	public function setStability($s) {
		$this->setApiStability($s);
		$this->setReleaseStability($s);
		return $this;
	}
	
	public function addFiles($files) {
		$this->addFilesSimple($files);
		return $this;
	}
	
	public function createPackage($in=null) {
		$this->writePackageFile();
		if(!is_null($in)) {
			pantr::mkdirs($in);
		}
		$pkgFile = $this->options[self::OPT_BASEDIR].DIRECTORY_SEPARATOR.'package.xml';
		pantr::create_pear_package($pkgFile, $in);
	}
}