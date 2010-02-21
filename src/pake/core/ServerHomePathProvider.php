<?php
namespace pake\core;

/**
 * Locates the home directory through the $_SERVER and its 'HOME' property.
 */
class ServerHomePathProvider implements HomePathProvider {
	public function get() {
		$home = null;
		if(isset($_SERVER['PAKE_HOME'])) {
			$home = $_SERVER['PAKE_HOME'];
		} else if(isset($_SERVER['HOME'])) {
			$home = $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.pake';
		}
		return $home;
	}
}