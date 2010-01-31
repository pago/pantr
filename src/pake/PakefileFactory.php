<?php
namespace pake;

interface PakefileFactory {
	public function getPakefile($name);
}