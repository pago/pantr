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
namespace Pagosoft\PSpec\TextUI;

class ResultPrinter extends \PHPUnit_TextUI_ResultPrinter {
	public function __construct($out = NULL, $verbose = FALSE, $colors = FALSE, $debug = FALSE) {
		parent::__construct($out, $verbose, $colors, $debug);
	}
	
	/**
	 * A test ended.
	 *
	 * @param  PHPUnit_Framework_Test $test
	 * @param  float				  $time
	 */
	public function endTest(\PHPUnit_Framework_Test $test, $time) {
		parent::endTest($test, $time);
		if($test instanceof \Pagosoft\PSpec\Test) {
			$this->numAssertions += $test->getNumAssertions();
		}
	}
}