<?php
namespace pake\core;

/**
 * Provides the pake home directory where global pake configuration data
 * and pake plugins are located.
 */
interface HomePathProvider {
	public function get();
}