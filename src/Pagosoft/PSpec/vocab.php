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
namespace PSpec;

use PHPUnit_Framework_Assert as Assert;
use Pagosoft\PSpec\Spec;
use Pagosoft\PSpec\Test;
use Pagosoft\PSpec\TestSuite;
use Pagosoft\PSpec\Matcher;
use Pagosoft\PSpec\FileMatcher;

function describe($desc, $fn) {
	Spec::addSuite($desc, $fn);
}

function beforeEach($fn) {
	Spec::getCurrentSuite()->setBeforeEach($fn);
}

function afterEach($fn) {
	Spec::getCurrentSuite()->setAfterEach($fn);
}

function afterAll($fn) {
	Spec::getCurrentSuite()->setAfterAll($fn);
}

function skip() {
	throw new \PHPUnit_Framework_SkippedTestError();
}

function it($should, $fn) {
	$suite = Spec::getCurrentSuite();
	$test = new Test($suite, $should, $fn);
	$suite->addTest($test);
}

function intercept() {
	$args = func_get_args();
	$name = $msg = $fn = null;
	switch(count($args)) {
		case 1:
			list($fn) = $args;
			break;
		case 2:
			list($name, $fn) = $args;
			break;
		case 3:
			list($name, $msg, $fn) = $args;
			break;
		default:
			throw new \InvalidArgumentException('intercept must be called with proper arguments');
	}
	try {
		$fn();
		Assert::fail();
	} catch(Exception $e) {
		if(!is_null($name)) {
			Assert::assertEquals($name, get_class($e));
		}
		if(!is_null($msg)) {
			Assert::assertEquals($msg, $e->getMessage());
		}
	}
}

function the($msg, $obj=null) {
	if(is_null($obj)) {
		$obj = $msg;
	}
	return new Matcher($obj);
}

function theValueOf($obj) {
	return new Matcher($obj);
}

function theFile($file) {
	return new FileMatcher($file);
}

function evaluating($fn) {
	return new Matcher($fn);
}

function failBecause($msg=null) {
	Assert::fail($msg);
}