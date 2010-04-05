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
use pantr\Task;

/**
 * File Tasks are hidden by default.
 */
class File extends Task {
	private $src, $target;
	
	public function __construct($name, $desc='') {
		parent::__construct($name, $desc);
		$this->isHidden(true);
	}
	
	public function run($fn) {
		$src = $this->src;
		$target = $this->target;
		parent::run(function() use ($src, $target, $fn) {
			if(!file_exists($target) || filemtime($target) < filemtime($src)) {
				$fn($src, $target);
			}
		});
        return $this;
    }

	public function withContent($fn) {
		return $this->run(function($src, $target) use ($fn) {
			$content = file_get_contents($src);
			$content = $fn($content);
			file_put_contents($target, $content);
		});
	}
	
	public static function task($name, $file, $spec) {
		$out = \pantr\fileNameTransform($file, $spec);
		$task = new File($name, 'create file '.basename($out).' if it does not exist');
		$task->src = $file;
		$task->target = $out;
		pantr::getTaskRepository()->registerTask($task);
		return $task;
	}
}