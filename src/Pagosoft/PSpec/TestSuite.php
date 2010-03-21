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

class TestSuite extends \PHPUnit_Framework_TestSuite {
	private $selfObject, $beforeEach, $afterEach, $afterAll;
	public function __construct($name, $selfObject) {
		parent::__construct($name);
		$this->setBackupGlobals(false);
		$this->selfObject = $selfObject;
	}
	
	private $parent;
	public function setParent($suite) {
		$this->parent = $parent;
	}
	
	public function toString() {
		$str = parent::toString();
		if(!is_null($this->parent)) {
			$str = $this->parent->toString() . ' ' . $str;
		}
		return $str;
	}
	
	public function setBeforeEach($fn) {
		$this->beforeEach = $fn;
	}
	
	public function setAfterEach($fn) {
		$this->afterEach = $fn;
	}
	
	public function setAfterAll($fn) {
		$this->afterAll = $fn;
	}
	
	public function getSelfObject() {
		return $this->selfObject;
	}
	
	protected function tearDown() {
		if(is_null($this->afterAll)) return;
		
		$fn = $this->afterAll;
		$fn($this->selfObject);
	}
	
	public function runBeforeEach() {
		if(is_null($this->beforeEach)) return;
		
		$self = new SelfObject($this->selfObject);
		$fn = $this->beforeEach;
		$fn($self);
		$this->selfObject = $self;
	}
	
	public function runAfterEach() {
		if(is_null($this->afterEach)) return;
		
		$fn = $this->afterEach;
		$e = null;
		try {
			$fn($this->selfObject);
		} catch(\Exception $_e) {
			$e = $_e;
		}
		$this->selfObject = $self->getParent();
		if(!is_null($e)) {
			throw $e;
		}
	}
}