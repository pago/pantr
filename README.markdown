pake is a simple php build tool
===============================

With pake you can manage automate tasks (for example the build process and distribution)
using the PHP language. You can also use it as a project local PEAR installer.

pake requires at least PHP 5.3.0 for the build file (usually called "pakefile.php"), your
project can use whatever PHP version it needs.

Installing pake
---------------

pake is distributed using PEAR. Use the following commands to install it:

	$ pear channel-discover pear.pagosoft.com
	$ pear install pgs/pake-beta

After you've done this, check if pake was installed correctly:

	$ pake help -g

You should see a copyright notice as well as at least one available task.

Getting started (the pakefile)
------------------------------
Create a pakefile.php at the top level of your application and add tasks.
A task in pake is defined using PHP 5.3.0 syntax. For example:

	use pake\Pake;

	Pake::task('greet', 'Says hello to the user')
		->run(function() {
			Pake::writeln('Hello my friend!');
		});
	
To invoke use the pake command:

	$ pake greet
	Hello my friend!
	
Pake can also handle task dependencies and tasks may have their own parameters and arguments.
To improve the previous example:

	use pake\Pake;

	Pake::task('say-hello', 'Says hello')
		->run(function() {
			Pake::write('Hello ');
		});
	
	Pake::task('greet', 'Greets the user')
		->usage('greet [-i|--important] <name>')
		->expectedNumArgs(1)
		->dependsOn('say-hello')
		->option('important')
			->shorthand('i')
			->desc('If set the user name will be displayed in red.')
		->run(function($req) {
			if(isset($req['important'])) {
				Pake::writeln($req[0], Pake::WARNING);
			} else {
				Pake::writeln($req[0], Pake::COMMENT);
			}
		});

Invoke it:

	$ pake greet Patrick
	Hello Patrick

If your terminal supports it, "Patrick" will be displayed using a different color.
pake also includes a help system, invoked through the

	$ pake help

command. It will list all defined tasks in the pakefile as well as all project-based
standard tasks.

To get an overview of pakes project creation tasks you can use

	$ pake help -g

pake as PEAR installer
----------------------

pake can be used as a project-based PEAR installer. This means you can install and manage
PEAR dependencies locally for every project instead of installing them as global packages.
For example pear includes the pgs/util, pgs/parser and pgs/cli libraries without cluttering
your global PEAR configuration.

You do not have to have a pakefile to use this functionality.

To setup a local pear repository, use the pear:init task

	$ pake pear:init lib

The last argument is the directory in which PEAR dependencies will be installed. Using the
above command will install it in the lib directory (which is the default thus you theoretically don't need to provide it).

Change into the specified directory (which was created if it didn't exist before) and start using
your local pear repository:

	$ pake pear:channel-discover pear.pagosoft.com
	$ pake pear:install pgs/parser

and so on.

You can use PEAR in your pakefile to automate certain tasks, too:

	use pake\tasks\PEAR;

	Pake::task('pear-upgrade', 'Updates all dependencies')
		->run(function() {
			$pear = new PEAR('lib/.pearrc');
			$pear->upgrade();
		});
	
pake as a project generator
---------------------------

pake can also create a project for you. This functionality is in its infancy, but it might still
be useful to get you started.

	$ pake pake:new-project --with-local-pear --with-pear-package test

will create a new project directory (test) and create a pakefile for you
that can build a pear package from the project as well as a phar file.
It will also automatically generate a test/lib directory in which you can manage your
project local pear dependencies.