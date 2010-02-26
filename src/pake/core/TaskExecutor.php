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

use pgs\cli\RequestContainer;
use pake\Task;
use pake\Pake;

class TaskExecutor {
	private $taskRepository, $executionStrategyFactory, $args;
	public function __construct(TaskRepository $taskRepository,
			ExecutionStrategyFactory $executionStrategyFactory, RequestContainer $args) {
		$this->taskRepository = $taskRepository;
		$this->executionStrategyFactory = $executionStrategyFactory;
		$this->args = $args;
	}
	
	public function execute(Task $task, $abortOnFailure=true) {
		$path = $this->executionStrategyFactory->get($task);
		foreach($path as $subTaskName) {
			$subTask = $this->taskRepository->getTask($subTaskName);
			$status = $subTask($this->args);
			if($status == Status::FAILURE && $abortOnFailure) {
				Pake::writeln('Task "'.$subTaskName.'" failed. Aborting.', Pake::ERROR);
				return Status::FAILURE;
			}
		}
		return Status::SUCCESS;
	}
}