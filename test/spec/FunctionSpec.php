<?php
namespace PSpec;

describe('Function \\pantr\\fileNameTransform', function() {
	it('should interpret the pattern *.php as a change of the file extension', function() {
		$path = \pantr\fileNameTransform('test/me.st', '*.php');
		the($path)->shouldBe('test/me.php');
	});
});

describe('Function findCommonPrefix', function() {
	the(\pantr\findCommonPrefix(array(null, 'src/autoload.php')))->shouldBe('src/autoload.php');
	the(\pantr\findCommonPrefix(array('src/autoload.php', 'src/cliapp.php')))->shouldBe('src/');
	the(\pantr\findCommonPrefix(array('src/', 'src/cliapp.php')))->shouldBe('src/');

	$dir = realpath(__DIR__.'/../../src/');
	$files = \pantr\file\fileset('*.php')->in($dir);
	$prefix = \pantr\findCommonPrefix($files);
	the($prefix)->shouldBe($dir.'/');
});