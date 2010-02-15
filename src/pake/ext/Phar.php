<?php
namespace pake\ext;

class Phar extends \Phar {
	public function getAutoloadManifest() {
		$mf = <<<'EOF'
<?php
spl_autoload_register(function($classname) {
	$pearStyle = str_replace('_', '/', $classname) . '.php';
	$nsStyle = str_replace('\\', '/', $classname) . '.php';
	$files = array($nsStyle, $pearStyle);

	foreach($files as $file) {
		if(file_exists($file)) {
			require_once $file;
			return true;
		}
	}
	return false;
});
EOF;
		return $mf;
	}
	
	public function setDefaultStub() {
		if(!isset($this['stub.php'])) {
			$this->addFromString('stub.php', $this->getAutoloadManifest());
		}
		$this->setStub($this->createDefaultStub('stub.php'));
	}
	
	public static function create($phar, $src='src') {
		Pake::writeAction('phar', $phar);
		$p = new Phar($phar);
		$p->startBuffering();
		$files = Pake::_getFinderFromArg($src);
		foreach($files as $dir) {
			Pake::writeAction('phar incl', $dir);
			$p->buildFromDirectory($dir);
		}
		$p->setDefaultStub();
		$p->stopBuffering();
	}
}