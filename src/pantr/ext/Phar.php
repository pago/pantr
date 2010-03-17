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
namespace pantr\ext;

use pantr\pantr;

class Phar extends \Phar {
	public function getAutoloadManifest() {
		$mf = <<<'EOF'
<?php
spl_autoload_register(function($classname) {
	$pearStyle = str_replace('_', '/', $classname) . '.php';
	$nsStyle = str_replace('\\', '/', $classname) . '.php';
	$files = array($nsStyle, $pearStyle);

	foreach($files as $file) {
		if(file_exists($file)) {
			require_once $file;
			return true;
		}
	}
	return false;
});
EOF;
		return $mf;
	}
	
	public function setDefaultStub() {
		if(!isset($this['stub.php'])) {
			$this->addFromString('stub.php', $this->getAutoloadManifest());
		}
		$this->setStub($this->createDefaultStub('stub.php'));
	}
	
	public function addDirectories($src) {
		$files = pantr::_getFinderFromArg($src);
		foreach($files as $dir) {
			pantr::writeAction('phar incl', $dir);
			$this->buildFromDirectory($dir);
		}
	}
	
	public static function create($phar, $src='src') {
		pantr::writeAction('phar', $phar);
		$p = new Phar($phar);
		$p->startBuffering();
		if(is_callable($src)) {
			$src($p);
		} else {
			$p->addDirectories($src);
			$p->setDefaultStub();
		}
		$p->stopBuffering();
	}
}