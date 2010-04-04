<?php
/* 
 * Copyright (c) 2009 Patrick Gotthardt
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
namespace pantr\file;

use pantr\pantr;
use pantr\Finder;

// Task Management

function task($name, $fnOrDesc=null, $fn=null) {
	return pantr::task($name, $fnOrDesc, $fn);
}

function setDefault($name) {
	pantr::setDefault($name);
}

// Finders

function fileset($glob=null) {
	return finder(Finder::TYPE_FILES, $glob);
}

function find($glob=null) {
	return finder(Finder::TYPE_ANY, $glob);
}

function finder($type, $glob=null) {
	$finder = Finder::type($type);
	if(!is_null($glob)) {
		$finder->name($glob);
	}
	return $finder;
}

// Utilities
/**
 * Log a message at a priority.
 * If called with no arguments at all, this method will return
 * the current Zend_Log instance.
 *
 * @param  string   $message   Message to log
 * @param  integer  $priority  Priority of message
 * @param  mixed    $extras    Extra information to log in event
 * @return void
 * @throws Zend_Log_Exception
 */
function log($message=null, $priority=null, $extras = null) {
	return pantr::log($message, $priority, $extras);
}

function property($key, $value=null) {
	return pantr::property($key, $value);
}

/** 
 * Loads the specified yaml file from PAKE_HOME (if it exists)
 * and a local file (if it exists).
 * This mechanism can be used to build config cascades.
 *
 * If $onlyLocal is true the cascade mechanism is deactivated.
 */
function loadProperties($name='pantr.yaml', $onlyLocal=false) {
	pantr::loadProperties($name, $onlyLocal);
}

/** 
 * Instead of manually tracking the beginning and end of a silent
 * block you can use this method to execute a function silently.
 * Overloaded method calls:
 * <code>silent($action, $msg, $fn)</code>
 * <code>silent($msg, $fn)</code>
 * <code>silent($fn)</code>
 *
 * If $action and $msg (or just $msg) are specified pantr will emit
 * them as output before switching to silent mode.
 * It is recommended to do this in order to notify the user of
 * what is happening.
 */
function silent() {
	$args = func_get_args();
	call_user_func_array(array('\pantr\pantr', 'silent'), $args);
}

/**
 * Outputs the given text as it is (without indenting it)
 * @param string $t
 * @return Output
 */
function write($t, $style = null) {
	return pantr::console()->out()->write($t, $style);
}

/**
 * @return Output
 */
function writeln($t='', $style = null) {
	return pantr::console()->out()->writeln($t, $style);
}

/** 
 * Output a formatted message to the user explaining
 * the current task. $action is the performed task (i.e. "move", "build", "create")
 * and $desc is the more detailed description ("to some other directory",
 * "the PEAR channel", "a PEAR package")
 *
 * It will also log the action with a Zend_Log::NOTICE priority.
 *
 * @return Output
 */
function writeAction($action, $desc, $style='PARAMETER') {
	pantr::writeAction($action, $desc, $style);
}