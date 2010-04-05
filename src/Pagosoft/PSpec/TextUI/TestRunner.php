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

require_once 'PHPUnit/TextUI/TestRunner.php';

class TestRunner extends \PHPUnit_TextUI_TestRunner {
	/**
	 * @param  mixed $test
	 * @param  array $arguments
	 * @throws InvalidArgumentException
	 */
	public static function run($test, array $arguments = array()) {
		if($test instanceof \ReflectionClass) {
			$test = new \PHPUnit_Framework_TestSuite($test);
		}

		if($test instanceof \PHPUnit_Framework_Test) {
			$aTestRunner = new self;

			return $aTestRunner->doRun($test, $arguments);
		} else {
			throw new \InvalidArgumentException(
			  'No test case or test suite found.');
		}
	}
	
	/**
	 * @param  PHPUnit_Framework_Test $suite
	 * @param  array				  $arguments
	 * @return PHPUnit_Framework_TestResult
	 */
	public function doRun(\PHPUnit_Framework_Test $suite, array $arguments = array()) {
		$this->handleConfiguration($arguments);

		if($this->printer === NULL) {
			if(isset($arguments['printer']) &&
				$arguments['printer'] instanceof \PHPUnit_Util_Printer) {
				$this->printer = $arguments['printer'];
			} else {
				$this->printer = new ResultPrinter(
					NULL,
					!$arguments['verbose'],
					$arguments['colors'],
					$arguments['debug']
				);
			}
		}
		
		// supress the version string if we're not verbose
		if(!$arguments['verbose']) {
			self::$versionStringPrinted = true;
		}

		return parent::doRun($suite, $arguments);
	}
}