<?php
require_once __DIR__.'/../bootstrap.php';
require_once 'vfsStream/vfsStream.php';

use pake\core\Pakefile;

class PakefileTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('testroot'));
	}
	
	/**
	 * it should load the file
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_load_the_file() {
		$file = vfsStream::url('testroot/pakefile.php');
		$unique_id = uniqid();
		$content = <<<PHP
<?php
define('$unique_id', true);
PHP;
		file_put_contents($file, $content);
		
		$pakefile = new Pakefile($file);
		$pakefile->load();
		
		$this->assertTrue(defined($unique_id));
	} // it should load the file	
}