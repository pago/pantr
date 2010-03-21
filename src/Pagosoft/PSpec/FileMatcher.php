<?php
namespace Pagosoft\PSpec;

use PHPUnit_Framework_Assert as Assert;

class FileMatcher {
	private $it;
	public function __construct($it) {
		$this->it = $it;
	}
	
	public function shouldExist() {
		Assert::assertFileExists($this->it);
	}
	
	public function shouldNotExist() {
		Assert::assertFileNotExists($this->it);
	}
}