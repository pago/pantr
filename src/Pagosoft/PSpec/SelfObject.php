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

class SelfObject {
	private $attributes = array(), $methods = array();
	private $parent;
	
	public function __construct($parent=null) {
		$this->parent = $parent;
	}
	
	public function addMethod($name, $fn) {
		$this->methods[$name] = $fn;
	}
	
	public function __set($name, $value) {
		$this->attributes[$name] = $value;
	}
	
	public function __get($name) {
		if(!isset($this->attributes[$name]) && !is_null($this->parent)) {
			return $this->parent->$name;
		}
		return $this->attributes[$name];
	}
	
	public function __call($name, $args) {
		if(!isset($this->methods[$name]) && !is_null($this->parent)) {
			return call_user_func_array(array($this->parent, $name), $args);
		}
		$fn = $this->methods[$name];
		return call_user_func_array($fn, $args);
	}
	
	public function getParent() {
		return $this->parent;
	}
}