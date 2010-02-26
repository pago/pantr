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
namespace pake\core;

/*
 * Locates a pakefile by looking at the current working directory and all of its
 * parent directories.
 */
class CyclicResolutionPakefileFactory implements PakefileFactory {
	private $path;
	
	public function __construct($path=null) {
		if(is_null($path)) {
			$this->path = getcwd();
		} else {
			$this->path = $path;
		}
	}
	
	public function getPakefile($name) {
		$here = $this->path;
		while(!$this->pakefileExists($here, $name)) {
			$parent = dirname($here);
			if($parent == $here) {
				return null;
			}
			$here = $parent;
		}
		$pakefile = new Pakefile($this->getPakefilePath($here, $name));
		return $pakefile;
	}
	
	private function pakefileExists($here, $name) {
		return file_exists($this->getPakefilePath($here, $name));
	}
	
	private function getPakefilePath($here, $name) {
		return $here . DIRECTORY_SEPARATOR . $name . '.php';
	}
}