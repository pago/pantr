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

class Spec {
	private static $currentSuites = array();
	private static $suites = array();
	private static $suiteDepth = 0;
	
	public static function pushSuite($suite) {
		self::$suites[] = $suite;
		array_push(self::$currentSuites, $suite);
		self::$suiteDepth++;
		if(self::$suiteDepth > 1) {
			$suite->setParent(self::getCurrentSuite());
		}
	}
	
	public static function popSuite() {
		self::$suiteDepth--;
		array_pop(self::$currentSuites);
	}
	
	public static function getCurrentSuite() {
		return self::$currentSuites[self::$suiteDepth-1];
	}
	
	public static function addSuite($desc, $fn) {
		if(self::$suiteDepth > 0) {
			$self = new SelfObject(self::getCurrentSuite()->getSelfObject());
		} else {
			$self = new SelfObject();
		}
		$suite = new TestSuite($desc, $self);
		Spec::pushSuite($suite);
		$fn($self);
		Spec::popSuite();
	}
	
	public static function getTestSuite() {
		$suite = new \PHPUnit_Framework_TestSuite();
		foreach(self::$suites as $test) {
			$suite->addTest($test);
		}
		return $suite;
	}
	
	public static function run($arguments=array()) {
		TextUI\TestRunner::run(self::getTestSuite(), $arguments);
	}
}