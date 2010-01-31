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
 * Description of StaticClassLoader
 *
 * @author pago
 */
final class StaticClassLoader extends ClassLoader {
	private $config;
	private $resourceDir;
	function __construct($config, $resourceDir = null) {
		$this->resourceDir = $resourceDir;
		// we can load a config file from in here
		if(!is_array($config)) {
			// see if it is a string and a file with that name exists
			if(is_string($config)) {
				$config = $this->getResource($config);
				if(file_exists($config)) {
					$this->config = include $config;
				}
			} else if(class_exists('Zend_Config') && $config instanceof Zend_Config) {
				$this->config = $config->toArray();
			}
			if(is_null($this->config)) {
				throw new InvalidArgumentException('Configuration is not an array nor a loadable php file', 500);
			}
		} else {
			$this->config = $config;
		}
	}


	public function getResource($resourceName) {
		$path = is_null($this->resourceDir)
			? $resourceName : ($this->resourceDir . DIRECTORY_SEPARATOR . $resourceName);
		return file_exists($path) ? $path : null;
	}
	
	public function loadClass($classname) {
		if(isset($this->config[$classname])) {
			require_once $this->config[$classname];
			return true;
		}
		return false;
	}
}