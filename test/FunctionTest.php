<?php
require_once __DIR__.'/../src/pake/functions.php';

class FunctionTest extends PHPUnit_Framework_TestCase {
	private $file, $prefix;
	public function setUp() {
		$this->prefix = __DIR__.'/../src/pake';
		$this->file = $this->prefix.'/functions.php';
	}
	
	/**
	 * it should rename a file extension
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_rename_a_file_extension() {
		$this->assertPatternMatches(
			$this->prefix.'/functions.foo',
			':dirname/:filename.foo');
	} // rename a file extension
	
	/**
	 * it should rename to the basename
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_rename_to_the_basename() {
		$this->assertPatternMatches('functions.php', ':basename');
	} // rename to the basename
	
	/**
	 * it should provide the file extension
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_provide_the_file_extension() {
		$this->assertPatternMatches('php', ':extension');
	} // provide the file extension
	
	/**
	 * it should allow prefixes
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_allow_prefixes() {
		$this->assertPatternMatches('foo/functions.php', 'foo/:basename');
	} // allow prefixes
	
	
	private function assertPatternMatches($expected, $pattern) {
		$this->assertEquals(
			$expected,
			pake\fileNameTransform($this->file, $pattern));
	}
}