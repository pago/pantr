pake is a simple php task automation and build tool
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
To invoke the version with argument:

	$ pake greet -i Patrick

pake also includes a help system, invoked with the

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

	use pake\ext\PEAR;

	Pake::task('pear-upgrade', 'Updates all dependencies')
		->run(function() {
			$pear = new PEAR('lib/.pearrc');
			$pear->upgrade();
		});
		
Since version 0.7.0 pake supports handling your PEAR dependencies in a much nicer way.
Instead of issuing a pear command from the command line you can specify your PEAR
dependencies in the pakefile and pake will sync it automatically, thus removing obsolete
and installing new packages.

	Pake::dependencies()->in('lib')
		->fromChannel('pear.pagosoft.com')
			->usePackage('util')
			->usePackage('cli')
			->usePackage('parser')
		->fromChannel('pear.php-tools.net')
			->usePackage('vfsstream', 'alpha')
		->fromChannel('pear.symfony-project.com')
			->usePackage('yaml')
		->sync();

It is up to you to either use it as a task (invoked manually) or put at the beginning/end of
your pakefile (always invoked when pake is run).
The preferred way is to include it in a task.

Creating a PEAR Package
-----------------------

Since version 0.7.3 there is a new API for creating PEAR packages.
In order to use the API you will have to have Pearfarm installed.

	$ pear channel-discover pearfarm.pearfarm.org
	$ pear install pearfarm/pearfarm

After you have performed those tasks, you could use pearfarm directly to build and publish
your PEAR packages, or you can use pake to do the same.

To create your PEAR package just create a new task:

	use pake\ext\Pearfarm\PackageSpec;
	Pake::task('build:package', 'Build a pear package')
		->run(function() {
			$spec = PackageSpec::in('src')
			->setName('<project name>')
			->setChannel('pear.yourserver.com')
			->setSummary('whatever')
			->setDescription('whatever')
			->setNotes('n/a')
			->setVersion('1.0.0')
			->setStability('stable')
			->setLicense(PackageSpec::LICENSE_MIT)
			->addMaintainer('lead', '<your name>', '<username>', '<email>')
			->setDependsOnPHPVersion('5.3.0')
			->addFiles(Pake::fileset()
				->ignore_version_control()
				->relative()
				->in('src'))
			->createPackage();
		});

The PackageSpec class extends Pearfarms Pearfarm_PackageSpec class and adds a few methods.
You have, however, the full Pearfarm API at your disposal.

After you have created your package you can deploy it using Pirum on your own server or
on Pearfarm. The APIs for Pirum already exist (pake\ext\Pirum) and we will improve
Pearfarm deployment support soon.

Storing configuration
----------------------

pake bundles the really nice sfYaml library and makes it available in any pakefile.
In order to help you with your configuration needs, pake provides a config cascade.
Whenever you use the Pake::loadProperties function to load a config file, pake
will try to load a file with the same name in ~/.pake first.
You can change the directory by setting the PAKE_HOME environment variable.
If you want to disable the cascade you can pass in "false" as the second value
of Pake::loadProperties (the first argument being the name of the file you'd like to load).

After you have loaded a configuration file, you can access its data using

	Pake::property('name')

Since yaml files are usually deeply nested trees pake provides a special syntax
to help you get the property you'd like to get a little bit easier.
If the property name contains a ":" (colon), pake will traverse the tree until
it reaches the specified property or ends up with a dead end (in that case *null* is returned).

~/.pake/pake.yml

	pear:
		local:
			server: pear.localhost.lo

Usage in pakefile:

	Pake::property('pear:local:server');

To set a property in your pakefile you can provide its value as the second parameter to the
*Pake::property* function:

	Pake::property('foo', 'bar');

Note that you cannot set a nested property using the colon syntax.

Installing libraries for use in pakefiles
------------------------------------------

Since pake has been created to automate your usual processes, you might want to
make certain libraries available in all your pakefiles. A rather stupid solution
is to install those libraries using pear. It's stupid since it'll force you
to use that library version in all of your php projects. You could fix that if you
lay out your include path properly but easy is usually better than smart, thus
pake provides a set of commands to manage a pear repository which is made available
to your pakefiles but not outside of those.

To access these specific pear commands, use the :bundle command:

	pake :bundle channel-discover pear.pagosoft.com
	pake :bundle install pgs/util

and so on. pake stores these files in *PAKE_HOME* (default: ~/.pake).
It is managed with pear but you can place any files you'd like to be available
in all your pakefiles in that directory. This includes configuration files (the config facade)
and any php file.

PAKE_HOME
---------

pake tries to resolve its home directory through the PAKE_HOME environment setting.
If you do not specify such a variable it'll try to select a sensible default path.

If there is a *~/Library/Application Support* directory (OS X has those by default):

	~/Library/Application Support/pake

Otherwise:

	~/.pake

License
---------

pake is released under the OpenSource MIT license.