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

use pake\Pake;

class BundleManager {
	private $homePathProvider;
	public function __construct(HomePathProvider $homePathProvider) {
		$this->homePathProvider = $homePathProvider;
	}
	
	public function registerIncludePath() {
		$path = $this->homePathProvider->get();
		if(!is_null($path)) {
			set_include_path(get_include_path() . PATH_SEPARATOR . $path);
		}
	}
	
	public function loadBundles() {
		$path = $this->homePathProvider->get();
		if(!is_null($path)) {
			// load all phar files
			$phars = Pake::fileset()
				->name('*.phar')
				->in($path);
			foreach($phars as $phar) {
				require_once $phar;
			}
		}
	}
}