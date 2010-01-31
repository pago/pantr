<?php
namespace pgs\util\date;

require_once 'Zend/Date.php';
require_once 'Zend/Locale.php';
require_once 'Zend/Registry.php';
require_once 'Zend/Locale/Format.php';

/**
 * 
 */
class Date extends \Zend_Date {
	/**
	 * If set, Date::now will always return this instance.
	 * @var Date
	 */
	private static $frozenNow = null;
	/**
	 * Freezes the self::now function to return only a fixed date.
	 * This should be used in UnitTests.
	 * Make sure to call Date::setUseNowFromTimestamp in the tearDown method.
	 */
	public static function freezeNow($date = null) {
		if($date instanceof Date) {
			self::$frozenNow = $date;
		} elseif(is_int($date)) {
			self::$frozenNow = new Date($date);
		} else {
			self::$frozenNow = new Date();
		}
	}

	public static function unfreezeNow() {
		self::$frozenNow = null;
	}

	public static function now($locale = null) {
		if(!is_null(self::$frozenNow)) {
			// this class is mutable, thus we can only ever
			// return a new instance of it
			// otherwise the globally frozen date could become altered
			// at any place in the application
			$now = new Date(self::$frozenNow);
			if(!is_null($locale)) {
				$now->setLocale($locale);
			}
			return $now;
		}
		return new Date(time(), self::TIMESTAMP, $locale);
	}

	public function __construct($date = null, $part = null, $locale = null) {
		if(!is_null(self::$frozenNow) && is_null($date) && is_null($part) && is_null($locale)) {
			parent::__construct(self::$frozenNow);
		} else {
			parent::__construct($date, $part, $locale);
		}
	}

	public function add($date, $part = null, $locale = null) {
		if($date instanceof Period) {
			// now you've got to love that irony
			// because period actually returns a new date object (the world how it's supposed to be)
			// we can't do much with the result anyhow
			// and have to actually set the resulting (new) date as this date
			// cool, isn't it?
			$newSelf = $date->addToDate($this);
			return $this->set($newSelf);
		} else {
			return parent::add($date, $part, $locale);
		}
	}

	public static function fromTimestamp($string) {
		return new Date($string, \Zend_Date::ISO_8601);
	}

	public function toTimestamp() {
		return $this->toString('YYYY-MM-dd HH:mm:ss');
	}

	/**
	 * @deprecated Use new Date($string, $format) or new Date($string, $locale) instead!
	 * @param string $string
	 * @param string | \Zend_Locale $format
	 * @return Date The new date
	 */
	public static function fromString($string, $format=null) {
		if($string == null) return null;
		if(is_null($format) || $format instanceof \Zend_Locale) {
			$locale = null;
			if(!is_null($format)) {
				$locale = $format;
			}
			if(is_null($locale)) {
				if(\Zend_Registry::isRegistered('\Zend_Locale')) {
					$locale = \Zend_Registry::get('\Zend_Locale');
				} else {
					$locale = new \Zend_Locale();
				}
			}
			$dateParts = \Zend_Locale_Format::getDate($string, array(
				'date_format' => $format,
				'locale' => $locale
			));
			return new Date($dateParts);
		}
		return new Date($string, $format);
	}

	public function __toString() {
		return $this->toString(\Zend_Locale_Format::getDateFormat($this->getLocale()));
	}
}
