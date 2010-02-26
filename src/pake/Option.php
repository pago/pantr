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
namespace pake;

use pgs\cli\RequestContainer;

class Option {
	private $long, $short=null, $desc='', $required=false;
	private $task;
	
	public function __construct(Task $task, $long) {
		$this->task = $task;
		$this->long = $long;
	}
	
	public function shorthand($short) {
		$this->short = $short;
		return $this;
	}
	
	public function desc($desc) {
		$this->desc = $desc;
		return $this;
	}
	
	public function required() {
		$this->required = true;
		return $this;
	}
	
	public function printHelp() {
		if(!is_null($this->short)) {
			Pake::writeOption($this->short, $this->long, $this->desc);
		} else {
			Pake::writeOption($this->long, $this->desc);
		}
	}
	
	public function registerOn(RequestContainer $req) {
		$req->registerParameter($this->long, $this->desc, $this->short, $this->required);
	}
	
	public function option($long) {
		$this->task->registerOption($this);
		return $this->task->option($long);
	}
	
	public function run($fn) {
		$this->task->registerOption($this);
		return $this->task->run($fn);
	}
}