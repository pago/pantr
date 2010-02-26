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
namespace Pagosoft\ClassLoader;

/**
 * Description of ClassLoaderComposite
 *
 * @author pago
 */
class ClassLoaderComposite extends ClassLoader {
	private $loaders;
	function __construct(array $loaders = array()) {
		$this->loaders = $loaders;
	}

	public function addClassLoader(ClassLoader $loader) {
		if(!in_array($loader, $this->loaders)) {
			$this->loaders[] = $loader;
		}
	}

	public function addClassLoaders() {
		$args = func_get_args();
		if(count($args) == 1 && is_array($args[0])) {
			$args = $args[0];
		}
		foreach($args as $loader) {
			if($loader instanceof ClassLoader) {
				$this->addClassLoader($loader);
			}
		}
	}

	public function getResource($resourceName) {
		foreach($this->loaders as $loader) {
			$resource = $loader->getResource($resourceName);
			if(!is_null($resource)) {
				return $resource;
			}
		}
		return null;
	}
	
	public function loadClass($classname) {
		foreach($this->loaders as $loader) {
			if($loader->loadClass($classname)) {
				return true;
			}
		}
		return false;
	}
}