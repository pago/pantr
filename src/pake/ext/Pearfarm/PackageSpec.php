<?php
namespace pake\ext\Pearfarm;

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
}