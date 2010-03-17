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

class Pirum {
	private static function loadPirum() {
		if(class_exists('\Pirum_CLI')) {
			return;
		}
		// look for pirum in include path
		$incl_path = explode(PATH_SEPARATOR, get_include_path());
		$found = false;
		foreach($incl_path as $path) {
			$file = $path . DIRECTORY_SEPARATOR . 'pirum';
			if(file_exists($file)) {
				require_once $file;
				return;
			}
		}
		
		require_once 'PEAR/Config.php';
		$pirumFile = PEAR_CONFIG_DEFAULT_BIN_DIR . DIRECTORY_SEPARATOR . 'pirum';
		echo "$pirumFile\n";
		if(file_exists($pirumFile)) {
			ob_start();
			require_once $pirumFile;
			ob_end_clean();
			return;
		}
		
		throw new \Exception('Pirum was not found.');
	}
	
	// pirum add pear $1/dist/$1-$2.tgz
	private $channel;
	public function __construct($channel) {
		$this->channel = $channel;
		
		self::loadPirum();
	}
	
	public static function onChannel($channel) {
		return new Pirum($channel);
	}
	
	public function add($pearPackage) {
		pantr::writeAction('pirum', 'Adding '.$pearPackage.' to '.$this->channel);
		$this->exec('add', $this->channel, $pearPackage);
	}
	
	public function addLatestVersion($dir) {
		$files = pantr::fileset()->name('*.tgz')->relative()->in($dir);
		$highest = array_reduce($files, function($pkg1, $pkg2) {
			if(is_null($pkg1)) {
				return $pkg2;
			}
			list($pkgname, $a) = explode('-', basename($pkg1, '.tgz'));
			list($pkgname, $b) = explode('-', basename($pkg2, '.tgz'));
			return version_compare($a, $b, '<') ? $pkg1 : $pkg2;
		}, null);
		$this->add($dir . DIRECTORY_SEPARATOR . $highest);
	}
	
	public function build() {
		$this->exec('build', $this->channel);
	}
	
	public function exec() {
		$args = func_get_args();
		array_unshift($args, '');
		$pirum = new \Pirum_CLI($args);
		$pirum->run();
	}
}