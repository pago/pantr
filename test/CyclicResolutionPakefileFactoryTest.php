<?php
require_once 'bootstrap.php';

use com\pagosoft\pake\CyclicResolutionPakefileFactory;

class CyclicResolutionPakefileFactoryTest extends PHPUnit_Framework_TestCase {
	public function testPakefileFound() {
		$fac = new CyclicResolutionPakefileFactory();
		$pakefile = $fac->getPakefile('pakefile');
		$this->assertNotNull($pakefile);
	}
}