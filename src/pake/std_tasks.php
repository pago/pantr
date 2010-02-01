<?php
use pake\Pake;
use pake\Phar;
use pgs\util\Finder;

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

Pake::task('pake:new-project', 'Creates a new project')
	->usage('pake:new-project <name>')
	->desc('Creates a new project directory called [<name>|PARAMETER] and initializes a pakefile '
		. 'for pear-packaging and phar distribution.')
	->expectNumArgs(1)
	->run(function($req) {
		$req = Pake::getArgs();
		$projectName = $req[1];
		Pake::mkdirs($projectName);
		foreach(array('src', 'lib', 'test') as $dir) {
			Pake::mkdirs(getcwd() . DIRECTORY_SEPARATOR . $projectName . DIRECTORY_SEPARATOR . $dir);
		}
		Pake::copy(__DIR__.'/new_project_template/pakefile.php',
			getcwd() . DIRECTORY_SEPARATOR . $projectName . DIRECTORY_SEPARATOR . 'pakefile.php',
			array('PROJECT_NAME' => $projectName));
		Pake::copy(__DIR__.'/new_project_template/package.xml',
			getcwd() . DIRECTORY_SEPARATOR . $projectName . DIRECTORY_SEPARATOR . 'package.xml');
		});