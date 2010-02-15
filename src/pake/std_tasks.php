<?php
use pake\Pake;
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
			Pake::getArgs()->printOptions();
			Pake::nl()
				->writeln('Available tasks:', Pake::SECTION);
			$showPakeTasks = isset($req['global-tasks']);
			foreach(Pake::getDefinedTasks() as $key => $task) {
				$isPakeTask = 0 === strpos($task->getName(), 'pake:');
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