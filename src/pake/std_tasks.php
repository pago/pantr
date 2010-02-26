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

use pake\Pake;
use pake\core\Status;
use pake\core\Application;
use pake\ext\Phar;
use pake\ext\PEAR;

PEAR::registerTasks();

Pake::task('help', 'Display this help message')
	->usage('help <task>')
	->option('global-tasks')
		->shorthand('g')
		->desc('Show global pake tasks only')
	->run(function($req) {
		// show info on one task
		if(isset($req[0])) {
			$taskName = $req[0];
			$tasks = Pake::getDefinedTasks();
			if(isset($tasks[$taskName])) {
				$task = $tasks[$taskName];
				if($task->getName() == 'help') {
					$task->desc('Display detailed information for a task');
				}
				$task->printHelp();
			}
		} else {
			// show summary of all tasks
			Pake::writeln('Usage:', Pake::SECTION)
				->write('pake [options]')
				->write(' <task> ', Pake::PARAMETER)
				->writeln('[args]');
			Application::printOptions();
			Pake::nl()
				->writeln('Available tasks:', Pake::SECTION);
			$showPakeTasks = isset($req['global-tasks']);
			foreach(Pake::getDefinedTasks() as $key => $task) {
				$taskName = $task->getName();
				$isPakeTask = $taskName[0] == ':';
				if(($showPakeTasks && $isPakeTask) || (!$showPakeTasks && !$isPakeTask)) {
					if($task->getName() != $key) {
						Pake::writeAction($key, 'Alias for ['.$task->getName().'|INFO]');
					} else {
					Pake::writeAction($key, $task->getDescription());
					}
				}
			}
			Pake::nl()->writeln('Help', Pake::SECTION)
				->write('See ')
				->write('pake help ', Pake::COMMENT)
				->write('<task> ', Pake::PARAMETER)
				->writeln(' for help with a specific task.');
		}
	});

Pake::task(':new-project', 'Creates a new project')
	->usage('pake:new-project [args] <name>')
	->desc('Creates a new project directory called [<name>|PARAMETER] and initializes a pakefile '
		. 'for pear-packaging and phar distribution.')
	->expectNumArgs(1)
	->option('with-local-pear')
		->shorthand('p')
		->desc('creates a local pear repository within lib-directory')
	->option('with-phar')
		->desc('create a phar task')
	->option('with-pear-package')
		->desc('create a pear:package task')
	->option('type')
		->desc('select project type: lib|app')
	->run(function($req) {
		$projectName = $req[0];
		Pake::mkdirs($projectName);
		$basedir = getcwd() . DIRECTORY_SEPARATOR . $projectName . DIRECTORY_SEPARATOR;
		foreach(array('src', 'lib', 'test') as $dir) {
			Pake::mkdirs($basedir . $dir);
		}
		Pake::copy(__DIR__.'/new_project_template/pakefile.php',
			$basedir . 'pakefile.php',
			array('PROJECT_NAME' => $projectName));
		if(isset($req['with-pear-package'])) {
			Pake::copy(__DIR__.'/new_project_template/package.xml',
				$basedir . 'package.xml');
		}
		if(isset($req['with-local-pear'])) {
			PEAR::init($basedir . 'lib');
		}
	});

Pake::task(':pake-bundle-project', 'Creates a new project for a pake bundle')
	->expectNumArgs(1)
	->run(function($req) {
		$name = $req[0];
		Pake::mkdirs($name);
		Pake::mkdirs($name.DIRECTORY_SEPARATOR.'src');
		$pakefile = <<<'EOF'
<?php
use pake\Pake;
use pake\ext\Phar;

Pake::task('clean', 'Remove unused files')
	->run(function() {
		Pake::rm('##NAME##.phar');
	});
	
Pake::task('phar', 'Create a phar archive')
	->dependsOn('clean')
	->run(function() {
		Phar::create('##NAME##.phar', 'src');
	});
	
Pake::task('install', 'Install in PAKE_HOME')
	->dependsOn('phar')
	->run(function() {
		$home = Pake::getHomePath();
		Pake::move('##NAME##.phar', $home.DIRECTORY_SEPARATOR.'##NAME##.phar');
	});
EOF;
		Pake::writeAction('create', 'pakefile.php');
		file_put_contents($name.DIRECTORY_SEPARATOR.'pakefile.php',
			str_replace('##NAME##', $name, $pakefile)
		);
	});