pantr (formerly known as pake) is a simple php task automation and build tool
===============================

With pantr you can manage automate tasks (for example the build process and distribution)
using the PHP language. You can also use it as a project local PEAR installer.

pantr requires at least PHP 5.3.0 for the build file (usually called "pantrfile.php"), your
project can use whatever PHP version it needs.

Installing pantr
---------------

pantr is distributed using PEAR. Use the following commands to install it:

	$ pear channel-discover pear.pagosoft.com
	$ pear install pgs/pantr-beta

After you've done this, check if pantr was installed correctly:

	$ pantr help -g

You should see a copyright notice as well as at least one available task.

Getting started (the pantrfile)
------------------------------
Create a pantrfile.php at the top level of your application and add tasks.
A task in pantr is defined using PHP 5.3.0 syntax. For example:

	use pantr\pantr;

	pantr::task('greet', 'Says hello to the user')
		->run(function() {
			pantr::writeln('Hello my friend!');
		});
	
To invoke use the pantr command:

	$ pantr greet
	Hello my friend!
	
Since version 0.8.0 there is also an alternative shorter syntax. It works by using the pantr\file namespace which provides a couple of functions:

	namespace pantr\file;
	
	/**
	 * Says hello to the user
	 */
	task('greet', function() {
		pantr::writeln('Hello my friend!');
	});
	
The pantrfile.php is a great sample of how to use this new API.
	
pantr can also handle task dependencies and tasks may have their own parameters and arguments.
To improve the previous example:

	use pantr\pantr;

	pantr::task('say-hello', 'Says hello')
		->run(function() {
			pantr::write('Hello ');
		});
	
	pantr::task('greet', 'Greets the user')
		->usage('greet [-i|--important] <name>')
		->expectedNumArgs(1)
		->dependsOn('say-hello')
		->option('important')
			->shorthand('i')
			->desc('If set the user name will be displayed in red.')
		->run(function($req) {
			if(isset($req['important'])) {
				pantr::writeln($req[0], pantr::WARNING);
			} else {
				pantr::writeln($req[0], pantr::COMMENT);
			}
		});

Invoke it:

	$ pantr greet Patrick
	Hello Patrick

If your terminal supports it, "Patrick" will be displayed using a different color.
To invoke the version with argument:

	$ pantr greet -i Patrick

pantr also includes a help system, invoked with the

	$ pantr help

command. It will list all defined tasks in the pantrfile as well as all project-based
standard tasks.

To get an overview of pantrs project creation tasks you can use

	$ pantr help -g

pantr as PEAR installer
----------------------

pantr can be used as a project-based PEAR installer. This means you can install and manage
PEAR dependencies locally for every project instead of installing them as global packages.
For example pear includes the pgs/util, pgs/parser and pgs/cli libraries without cluttering
your global PEAR configuration.

You do not have to have a pantrfile to use this functionality.

To setup a local pear repository, use the pear:init task

	$ pantr pear:init lib

The last argument is the directory in which PEAR dependencies will be installed. Using the
above command will install it in the lib directory (which is the default thus you theoretically don't need to provide it).

Change into the specified directory (which was created if it didn't exist before) and start using
your local pear repository:

	$ pantr pear:channel-discover pear.pagosoft.com
	$ pantr pear:install pgs/parser

and so on.

You can use PEAR in your pantrfile to automate certain tasks, too:

	use pantr\ext\PEAR;

	pantr::task('pear-upgrade', 'Updates all dependencies')
		->run(function() {
			$pear = new PEAR('lib/.pearrc');
			$pear->upgrade();
		});
		
Since version 0.7.0 pantr supports handling your PEAR dependencies in a much nicer way.
Instead of issuing a pear command from the command line you can specify your PEAR
dependencies in the pantrfile and pantr will sync it automatically, thus removing obsolete
and installing new packages.

	pantr::dependencies()->in('lib')
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
your pantrfile (always invoked when pantr is run).
The preferred way is to include it in a task.

Creating a PEAR Package
-----------------------

Since version 0.7.3 there is a new API for creating PEAR packages.
In order to use the API you will have to have Pearfarm installed.

	$ pear channel-discover pearfarm.pearfarm.org
	$ pear install pearfarm/pearfarm

After you have performed those tasks, you could use pearfarm directly to build and publish
your PEAR packages, or you can use pantr to do the same.

To create your PEAR package just create a new task:

	use pantr\ext\Pearfarm\PackageSpec;
	pantr::task('build:package', 'Build a pear package')
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
			->addFiles(pantr::fileset()
				->ignore_version_control()
				->relative()
				->in('src'))
			->createPackage();
		});

The PackageSpec class extends Pearfarms Pearfarm_PackageSpec class and adds a few methods.
You have, however, the full Pearfarm API at your disposal.

After you have created your package you can deploy it using Pirum on your own server or
on Pearfarm. The APIs for Pirum already exist (pantr\ext\Pirum) and we will improve
Pearfarm deployment support soon.

Storing configuration
----------------------

pantr bundles the really nice sfYaml library and makes it available in any pantrfile.
In order to help you with your configuration needs, pantr provides a config cascade.
Whenever you use the pantr::loadProperties function to load a config file, pantr
will try to load a file with the same name in ~/.pantr first.
You can change the directory by setting the PAKE_HOME environment variable.
If you want to disable the cascade you can pass in "false" as the second value
of pantr::loadProperties (the first argument being the name of the file you'd like to load).

After you have loaded a configuration file, you can access its data using

	pantr::property('name')

Since yaml files are usually deeply nested trees pantr provides a special syntax
to help you get the property you'd like to get a little bit easier.
If the property name contains a ":" (colon), pantr will traverse the tree until
it reaches the specified property or ends up with a dead end (in that case *null* is returned).

~/.pantr/pantr.yml

	pear:
		local:
			server: pear.localhost.lo

Usage in pantrfile:

	pantr::property('pear:local:server');

To set a property in your pantrfile you can provide its value as the second parameter to the
*pantr::property* function:

	pantr::property('foo', 'bar');

Note that you cannot set a nested property using the colon syntax.

Installing libraries for use in pantrfiles
------------------------------------------

Since pantr has been created to automate your usual processes, you might want to
make certain libraries available in all your pantrfiles. A rather stupid solution
is to install those libraries using pear. It's stupid since it'll force you
to use that library version in all of your php projects. You could fix that if you
lay out your include path properly but easy is usually better than smart, thus
pantr provides a set of commands to manage a pear repository which is made available
to your pantrfiles but not outside of those.

To access these specific pear commands, use the :bundle command:

	pantr :bundle channel-discover pear.pagosoft.com
	pantr :bundle install pgs/util

and so on. pantr stores these files in *PAKE_HOME* (default: ~/.pantr).
It is managed with pear but you can place any files you'd like to be available
in all your pantrfiles in that directory. This includes configuration files (the config facade)
and any php file.

PAKE_HOME
---------

pantr tries to resolve its home directory through the PAKE_HOME environment setting.
If you do not specify such a variable it'll try to select a sensible default path.

If there is a *~/Library/Application Support* directory (OS X has those by default):

	~/Library/Application Support/pantr

Otherwise:

	~/.pantr

License
---------

pantr is released under the OpenSource MIT license.