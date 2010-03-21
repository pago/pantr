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

use PHPUnit_Framework_Assert as Assert;

class Matcher {
	private $it;
	public function __construct($it) {
		$this->it = $it;
	}
	
	public function shouldBe($value) {
		Assert::assertEquals($this->it, $value);
	}
	
	public function shouldNotBe($value) {
		Assert::assertNotEquals($this->it, $value);
	}
	
	public function shouldBeTrue() {
		Assert::assertTrue($this->it);
	}
	
	public function shouldBeFalse() {
		Assert::assertFalse($this->it);
	}
	
	public function shouldHaveKey($key) {
		if($this->it instanceof \ArrayAccess) {
			Assert::assertEquals(isset($this->it[$key]), true, 'key '.$key.' does not exist');
		} else {
			Assert::assertArrayHasKey($key, $this->it);
		}
	}
	
	public function shouldHaveAttribute($key) {
		Assert::assertClassHasAttribute($key, $this->it);
	}
	
	public function shouldContain($value) {
		Assert::assertContains($value, $this->it);
	}
	
	public function shouldNotContain($value) {
		Assert::assertThat($this->it, Assert::logicalNot(Assert::contains($value)));
	}
	
	public function shouldProduce($name, $msg=null) {
		$fn = $this->it;
		try {
			$fn();
			Assert::fail();
		} catch(\Exception $e) {
			if($name) {
				Assert::assertEquals($name, get_class($e));
			}
			if(!is_null($msg)) {
				Assert::assertEquals($msg, $e->getMessage());
			}
		}
	}
	
	public function shouldProduceOutput($out) {
		ob_start();
		$fn = $this->it;
		$fn();
		$x = ob_get_contents();
		ob_end_clean();
		Assert::assertEquals($out, $x);
	}
}