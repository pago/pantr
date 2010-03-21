<?php
use pantr\core\CyclicResolutionTaskFileFactory;

class CyclicResolutionTaskFileFactoryTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('r'));
	}
	
	/**
	 * it should find the pantrfile
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_find_the_taskfile() {
		$fname = uniqid();
		$file = vfsStream::url('r/'.$fname.'.php');
		file_put_contents($file, 'test');
		
		$fac = new CyclicResolutionTaskFileFactory(vfsStream::url('r'));
		$pfile = $fac->getTaskFile($fname);
		$this->assertNotNull($pfile);
	} // it should find the pantrfile
	
	/**
	 * it should look for pantrfile in parent directories
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_look_for_taskfile_in_parent_directories() {
		$file = vfsStream::url('r/pantrfile.php');
		file_put_contents($file, 'test');
		
		$basedir = 'r/foo/bar/baz';
		$dir = vfsStream::newDirectory($basedir);
		$fac = new CyclicResolutionTaskFileFactory(vfsStream::url($basedir));
		$pfile = $fac->getTaskFile('pantrfile');
		$this->assertNotNull($pfile);
	} // it should look for pantrfile in parent directories
}