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

use pantr\pantr;
use pantr\core\Status;
use pantr\core\Application;
use pantr\ext\Phar;
use pantr\ext\PEAR;

PEAR::registerTasks();

pantr::task('help', 'Display this help message')
	->usage('help <task>')
	->option('global-tasks')
		->shorthand('g')
		->desc('Show global pantr tasks only')
	->run(function($req) {
		// show info on one task
		if(isset($req[0])) {
			$taskName = $req[0];
			$tasks = pantr::getDefinedTasks();
			if(isset($tasks[$taskName])) {
				$task = $tasks[$taskName];
				if($task->getName() == 'help') {
					$task->desc('Display detailed information for a task');
				}
				$task->printHelp();
			}
		} else {
			// show summary of all tasks
			pantr::writeln('Usage:', pantr::SECTION)
				->write('pantr [options]')
				->write(' <task> ', pantr::PARAMETER)
				->writeln('[args]');
			Application::printOptions();
			pantr::out()->nl()
				->writeln('Available tasks:', pantr::SECTION);
			$showGlobalTasks = isset($req['global-tasks']);
			$tasks = pantr::getDefinedTasks();
			$names = array_keys($tasks);
			sort($names);
			foreach($names as $key) {
				$task = $tasks[$key];
				$taskName = $task->getName();
				$isGlobalTask = $task->isGlobal();
				if(($showGlobalTasks && $isGlobalTask) || (!$showGlobalTasks && !$isGlobalTask)) {
					if($task->getName() != $key) {
						pantr::writeAction($key, 'Alias for ['.$task->getName().'|INFO]');
					} else {
						pantr::writeAction($key, $task->getDescription());
					}
				}
			}
			pantr::out()->nl()->writeln('Help', pantr::SECTION)
				->write('See ')
				->write('pantr help ', pantr::COMMENT)
				->write('<task> ', pantr::PARAMETER)
				->writeln(' for help with a specific task.');
		}
	});