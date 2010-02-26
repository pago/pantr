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

/**
 * Locates the home directory through the $_SERVER and its 'HOME' property.
 */
class ServerHomePathProvider implements HomePathProvider {
	public function get() {
		if(isset($_SERVER['PAKE_HOME'])) {
			return $_SERVER['PAKE_HOME'];
		} else if(isset($_SERVER['HOME'])) {
			$home = $_SERVER['HOME'];
			// backwards compatible and most intuitive to linux users
			if(file_exists($home . DIRECTORY_SEPARATOR . '.pake')) {
				return $home . DIRECTORY_SEPARATOR . '.pake';
			}
			// for mac users
			if(file_exists($home . '/Library/Application Support')) {
				return $home . '/Library/Application Support/pake';
			}
			// default to linux style
			return $home . DIRECTORY_SEPARATOR . '.pake';
		}
	}
}