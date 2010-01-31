<?php
namespace loader;

class PharClassLoader extends DirectoryClassLoader {
	private $phar;
	public function __construct($phar) {
		parent::__construct('phar://'.$phar);
	}
}