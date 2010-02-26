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

use pake\Task;

class ExecutionStrategy implements \IteratorAggregate {
	private $repo, $marked=array();
	private $path;
	public function __construct(TaskRepository $repo, Task $task) {
		$this->repo = $repo;
		$this->path = $this->getDependencyList($task);
	}
	
	public function getIterator() {
		return new \ArrayIterator($this->path);
	}
	
	private function getDependencyList(Task $task) {
		if(isset($this->marked[$task->getName()])) {
			return array();
		}
		$this->marked[$task->getName()] = true;
		$path = array();
		$deps = $this->asTasks($task->getDependencies());
		foreach($deps as $dep) {
			$path = array_merge($path, $this->getDependencyList($dep));
		}
		$path[] = $task->getName();
		return $path;
	}
	
	private function asTasks(array $deps) {
		$repo = $this->repo;
		return array_map(function($taskName) use ($repo) {
			return $repo->getTask($taskName);
		}, $deps);
	}
}