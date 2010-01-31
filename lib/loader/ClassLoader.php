<?php
namespace loader;
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

/**
 * A ClassLoader is an object that is responsible for loading classes.
 * The way of loading the classes is an implementation detail
 * but will most likely be accomplished either by transforming the class name
 * into a file name and looking for this file in one or more places, or by looking it
 * up in a dictonary.
 *
 * @author pago
 */
abstract class ClassLoader {
	public abstract function loadClass($classname);
	public abstract function getResource($resourceName);

	public function registerAutoload() {
		spl_autoload_register(array($this, 'loadClass'));
	}
	
	protected function translate($classname) {
		return array(str_replace(
			array('_', '\\'),
			array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR),
			$classname) . '.php');
	}
}