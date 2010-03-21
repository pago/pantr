<?php
/* 
 * Copyright (c) 2010 Patrick Gotthardt
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Pagosoft\PSpec;

class Test implements \PHPUnit_Framework_Test, \PHPUnit_Framework_SelfDescribing {
	private $suite, $msg, $fn, $numAssertions;
	
	public function __construct($suite, $msg, $fn) {
		$this->suite = $suite;
		$this->msg = 'it '.$msg;
		$this->fn = $fn;
	}
	
	public function toString() {
		return $this->suite->toString() . ': ' .$this->msg;
	}
	
	public function count() {
		return 1;
	}
	
	public function run(\PHPUnit_Framework_TestResult $result = NULL) {
		if(is_null($result)) {
			$result = $this->createResult();
		}
		$result->run($this);
		return $result;
	}
	
	public function runBare() {
		$this->numAssertions = 0;
		$fn = $this->fn;
		$this->suite->runBeforeEach();
		$fn($this->suite->getSelfObject());
		$this->suite->runAfterEach();
	}
	
	protected function createResult() {
		return new \PHPUnit_Framework_TestResult();
	}

	public function addToAssertionCount($count) {
		$this->numAssertions += $count;
	}
	
	public function getNumAssertions() {
		return $this->numAssertions;
	}
}