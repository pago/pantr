<?php
require_once __DIR__.'/../bootstrap.php';

use pake\core\CyclicResolutionPakefileFactory;

class CyclicResolutionPakefileFactoryTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('r'));
	}
	
	/**
	 * it should find the pakefile
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_find_the_pakefile() {
		$fname = uniqid();
		$file = vfsStream::url('r/'.$fname.'.php');
		file_put_contents($file, 'test');
		
		$fac = new CyclicResolutionPakefileFactory(vfsStream::url('r'));
		$pfile = $fac->getPakefile($fname);
		$this->assertNotNull($pfile);
	} // it should find the pakefile
}