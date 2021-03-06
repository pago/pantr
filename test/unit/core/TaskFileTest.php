<?php
use pantr\core\TaskFile;
use pantr\core\TaskRepository;

class TaskFileTest extends PHPUnit_Framework_TestCase {
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
		$file = vfsStream::url('testroot/pantrfile.php');
		$unique_id = uniqid();
		$content = <<<PHP
<?php
define('$unique_id', true);
PHP;
		file_put_contents($file, $content);
		
		$pantrfile = new TaskFile($file);
		$pantrfile->load(new TaskRepository());
		
		$this->assertTrue(defined($unique_id));
	} // it should load the file	
}