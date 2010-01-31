<?php
namespace pgs\util;

/**
 * Description of Filter
 *
 * @author pago
 */
final class Filter {
	private static $prefixes = array(
		'Zend_Filter_Word_',
		'Zend_Filter_',
		'pgs\\util\\filter\\');
	private function __construct() {
	}

	public static function __callStatic($name, $arguments) {
		$className = self::findClassName(ucfirst($name));
		if($className == null) {
			throw new FormattedException('Filter with name ' . $name . ' not found');
		}

		$filter = new $className();
		return $filter->filter($arguments[0]);
	}

	public function addFilterPrefix($prefix) {
		self::$prefixes[] = $prefix;
	}

	private function findClassName($name) {
		foreach(self::$prefixes as $prefix) {
			$className = $prefix . $name;
			if(class_exists($className)) {
				return $className;
			}
		}
		return null;
	}
}