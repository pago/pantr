<?php
namespace pake\ext\Pearfarm;

// try to load pearfarm
foreach(explode(PATH_SEPARATOR, get_include_path()) as $path) {
	$file = $path . DIRECTORY_SEPARATOR . 'pearfarm/src/Pearfarm/PackageSpec.php';
	if(file_exists($file)) {
		require_once $file;
	}
}
if(!class_exists('Pearfarm_PackageSpec')) {
	throw new \Exception('Pearfarm is not installed!');
}

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
	
	public function listFiles() {
		print_r($this->files);
	}
}