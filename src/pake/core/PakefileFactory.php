<?php
namespace pake\core;

interface PakefileFactory {
	public function getPakefile($name);
}