<?php
namespace pake\ext;

class Pirum {
	private static function loadPirum() {
		if(class_exists('\Pirum_CLI')) {
			return;
		}
		// look for pirum in include path
		$incl_path = explode(PATH_SEPARATOR, get_include_path());
		$found = false;
		foreach($incl_path as $path) {
			$file = $path . DIRECTORY_SEPARATOR . 'pirum';
			if(file_exists($file)) {
				require_once $file;
				return;
			}
		}
		
		require_once 'PEAR/Config.php';
		$pirumFile = PEAR_CONFIG_DEFAULT_BIN_DIR . DIRECTORY_SEPARATOR . 'pirum';
		if(file_exists($pirumFile)) {
			require_once $pirumFile;
		}
		
		throw new \Exception('Pirum was not found.');
	}
	
	// pirum add pear $1/dist/$1-$2.tgz
	private $channel;
	public function __construct($channel) {
		$this->channel = $channel;
		
		self::loadPirum();
	}
	
	public static function onChannel($channel) {
		return new Pirum($channel);
	}
	
	public function add($pearPackage) {
		$this->exec('add', $this->channel, $pearPackage);
	}
	
	public function addLatestVersion($dir) {
		$files = Pake::fileset()->name('*.tgz')->relative()->in($dir);
		$highest = array_reduce($files, function($pkg1, $pkg2) {
			if(is_null($pkg1)) {
				return $pkg2;
			}
			list($pkgname, $a) = explode('-', basename($pkg1, '.tgz'));
			list($pkgname, $b) = explode('-', basename($pkg2, '.tgz'));
			return version_compare($a, $b, '<') ? $pkg1 : $pkg2;
		}, null);
		$this->add($dir . DIRECTORY_SEPARATOR . $highest);
	}
	
	public function build() {
		$this->exec('build', $this->channel);
	}
	
	public function exec() {
		$args = func_get_args();
		array_unshift($args, '');
		$pirum = new \Pirum_CLI($args);
		$pirum->run();
	}
}