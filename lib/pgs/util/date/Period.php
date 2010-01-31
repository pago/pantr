<?php
namespace pgs\util\date;

use pgs\parser as Parser;

require_once 'Zend/Date.php';

/* 
 * Copyright (c) 2009 Patrick Gotthardt
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Description of Period
 *
 * @author pago
 */
class Period {
	private $years, $months, $days, $hours, $minutes, $seconds;

	private function __construct($years, $months,  $days, $hours, $minutes, $seconds) {
		$this->years = $years;
		$this->months = $months;
		$this->days = $days;
		$this->hours = $hours;
		$this->minutes = $minutes;
		$this->seconds = $seconds;
	}

	// ---------------------------------------
	// getters and setters

	public function getYears() {
		return $this->years;
	}

	public function getMonths() {
		return $this->months;
	}

	public function getDays() {
		return $this->days;
	}

	public function getHours() {
		return $this->hours;
	}

	public function getMinutes() {
		return $this->minutes;
	}

	public function getSeconds() {
		return $this->seconds;
	}

	public function equals(Period $other) {
		return
			$this->years == $other->years
			&& $this->months == $other->months
			&& $this->days == $other->days
			&& $this->hours == $other->hours
			&& $this->minutes == $other->minutes
			&& $this->seconds == $other->seconds;
	}

	// ---------------------------------------
	// zero handling
	private static $zero = null;
	/**
	 * @return Period
	 */
	public static function zero() {
		if(is_null(self::$zero)) {
			self::$zero = new Period(0, 0, 0, 0, 0, 0);
		}
		return self::$zero;
	}

	public function isZero() {
		return $this == self::$zero;
	}

	// ---------------------------------------
	// static constructors

	/**
	 * @return Period
	 */
	public static function period($years=0, $months=0, $days=0, $hours=0, $minutes=0, $seconds=0) {
		if(($years | $months | $days | $hours | $minutes | $seconds) == 0) {
			return self::zero();
		}
		return new Period($years, $months, $days, $hours, $minutes, $seconds);
	}

	/**
	 * @return Period
	 */
	public static function years($years) {
		return self::period($years);
	}

	/**
	 * @return Period
	 */
	public static function months($months) {
		return self::period(0, $months);
	}

	/**
	 * @return Period
	 */
	public static function days($days) {
		return self::period(0, 0, $days);
	}

	/**
	 * @return Period
	 */
	public static function hours($hours) {
		return self::period(0, 0, 0, $hours);
	}

	/**
	 * @return Period
	 */
	public static function minutes($minutes) {
		return self::period(0, 0, 0, 0, $minutes);
	}

	/**
	 * @return Period
	 */
	public static function seconds($seconds) {
		return self::period(0, 0, 0, 0, 0, $seconds);
	}

	/**
	 * @return Period
	 */
	public static function between(\Zend_Date $start, \Zend_Date $end) {
		// we can only work with it this way
		$switchedTime = false;
		if($start->isLater($end)) {
			$temp = $start;
			$start = $end;
			$end = $temp;
			$switchedTime = true;
		}
		$years   = $end->get(\Zend_Date::YEAR)   - $start->get(\Zend_Date::YEAR);
		$months  = $end->get(\Zend_Date::MONTH)  - $start->get(\Zend_Date::MONTH);
		$days    = $end->get(\Zend_Date::DAY)    - $start->get(\Zend_Date::DAY);
		$hours   = $end->get(\Zend_Date::HOUR)   - $start->get(\Zend_Date::HOUR);
		$minutes = $end->get(\Zend_Date::MINUTE) - $start->get(\Zend_Date::MINUTE);
		$seconds = $end->get(\Zend_Date::SECOND) - $start->get(\Zend_Date::SECOND);

		if($months < 0) {
			// handle overflow
			$years -= 1;
			$months = $end->get(\Zend_Date::MONTH) + 12 - $start->get(\Zend_Date::MONTH);
		}
		if($days < 0) {
			// handle overflow
			$months -= 1;
			if($months < 0) {
				$months = 12 + $months;
				$years -= 1;
			}
			$days = $end->get(\Zend_Date::DAY)
				+ ($start->get(\Zend_Date::MONTH_DAYS) - $start->get(\Zend_Date::DAY));
		}
		if($hours < 0) {
			// if hours overflow, the $days must be at least 1
			$days -= 1;
			$hours = 24 + $hours;
			// if hours were overflown, we also need to adjust the minutes
			// unless they overflow anyway
			if($minutes > 0) {
				$minutes = 60 - $minutes;
				$hours -= 1;
			}
		}
		if($minutes < 0) {
			$hours -= 1;
			$minutes += 60;
		}
		if($seconds < 0) {
			$minutes -= 1;
			$seconds += 60;
			if($minutes < 0) {
				$minutes = 60 + $minutes;
				$hours -= 1;
			}
		}

		$period = self::period($years, $months, $days, $hours, $minutes, $seconds);
		if($switchedTime) {
			$period = $period->multipliedBy(-1);
		}
		return $period;
	}

	// ---------------------------------------
	// support for builder pattern

	/**
	 * @return Period
	 */
	public function multipliedBy($factor) {
		if($this === self::zero() || $factor == 1) {
			return $this;
		}
		return self::period(
			$this->years   * $factor,
			$this->months  * $factor,
			$this->days    * $factor,
			$this->hours   * $factor,
			$this->minutes * $factor,
			$this->seconds * $factor
		);
	}

	/**
	 * @return Period
	 */
	public function dividedBy($divisor) {
		if($this === self::zero() || $divisor == 1) {
			return $this;
		}
		return self::period(
			intval($this->years   / $divisor),
			intval($this->months  / $divisor),
			intval($this->days    / $divisor),
			intval($this->hours   / $divisor),
			intval($this->minutes / $divisor),
			intval($this->seconds / $divisor)
		);
	}

	/**
	 * @return Period
	 */
	public function withYears($years) {
		if($this->years == $years) {
			return $this;
		}
		return self::period($years, $this->months, $this->days,
			$this->hours, $this->minutes, $this->seconds);
	}

	/**
	 * @return Period
	 */
	public function withMonths($months) {
		if($this->months == $months) {
			return $this;
		}
		return self::period($this->years, $months, $this->days,
			$this->hours, $this->minutes, $this->seconds);
	}

	/**
	 * @return Period
	 */
	public function withDays($days) {
		if($this->days == $days) {
			return $this;
		}
		return self::period($this->years, $this->months, $days,
			$this->hours, $this->minutes, $this->seconds);
	}

	/**
	 * @return Period
	 */
	public function withHours($hours) {
		if($this->hours == $hours) {
			return $this;
		}
		return self::period($this->years, $this->months, $this->days,
			$hours, $this->minutes, $this->seconds);
	}

	/**
	 * @return Period
	 */
	public function withMinutes($minutes) {
		if($this->minutes == $minutes) {
			return $this;
		}
		return self::period($this->years, $this->months, $this->days,
			$this->hours, $minutes, $this->seconds);
	}

	/**
	 * @return Period
	 */
	public function withSeconds($seconds) {
		if($this->seconds == $seconds) {
			return $this;
		}
		return self::period($this->years, $this->months, $this->days,
			$this->hours, $this->minutes, $seconds);
	}

	/**
	 * @return Period
	 */
	public function plusYears($years) {
		return $this->withYears($this->years + $years);
	}

	/**
	 * @return Period
	 */
	public function plusMonths($months) {
		return $this->withMonths($this->months + $months);
	}

	/**
	 * @return Period
	 */
	public function plusDays($days) {
		return $this->withDays($this->days + $days);
	}

	/**
	 * @return Period
	 */
	public function plusHours($hours) {
		return $this->withHours($this->hours + $hours);
	}

	/**
	 * @return Period
	 */
	public function plusMinutes($minutes) {
		return $this->withMinutes($this->minutes + $minutes);
	}

	/**
	 * @return Period
	 */
	public function plusSeconds($seconds) {
		return $this->withSeconds($this->seconds + $seconds);
	}

	/**
	 * @return Period
	 */
	public function minusYears($years) {
		return $this->withYears($this->years - $years);
	}

	/**
	 * @return Period
	 */
	public function minusMonths($months) {
		return $this->withMonths($this->months - $months);
	}

	/**
	 * @return Period
	 */
	public function minusDays($days) {
		return $this->withDays($this->days - $days);
	}

	/**
	 * @return Period
	 */
	public function minusHours($hours) {
		return $this->withHours($this->hours - $hours);
	}

	/**
	 * @return Period
	 */
	public function minusMinutes($minutes) {
		return $this->withMinutes($this->minutes - $minutes);
	}

	/**
	 * @return Period
	 */
	public function minusSeconds($seconds) {
		return $this->withSeconds($this->seconds - $seconds);
	}

	// actually usefull methods

	/**
	 * @return Pgs_Date
	 */
	public function addToDate(\Zend_Date $date) {
		$date = new Date($date);

		// just a small notice on how much I hate PHP:
		// in PHP 0 === null yields... *drumroll* true!
		// I mean, c'mon! It's the triple equals-operator
		// at least that one should check the type
		// shouldn't it?

		if($this->years != 0)
			$date->add($this->years, \Zend_Date::YEAR);

		if($this->months != 0)
			$date->add($this->months, \Zend_Date::MONTH);

		if($this->days != 0)
			$date->add($this->days, \Zend_Date::DAY);

		if($this->hours != 0)
			$date->add($this->hours, \Zend_Date::HOUR);

		if($this->minutes != 0)
			$date->add($this->minutes, \Zend_Date::MINUTE);

		if($this->seconds != 0)
			$date->add($this->seconds, \Zend_Date::SECOND);

		return $date;
	}

	/**
	 * Returns the period in ISO8601 format.
	 */
	public function __toString() {
		return sprintf('P%dY%dM%dDT%dH%dM%dS',
			$this->years, $this->months, $this->days,
			$this->hours, $this->minutes, $this->seconds);
	}

	public static function parse($string) {
		$in = new Parser\StringStream($string);
		// check if it starts with a "P"
		if($in->LA(1) != 'P') {
			throw new InvalidArgumentException("'$string' does not start with a 'P", 500);
		}
		$in->consume();
		$data = array();
		// now until we've reached the "T" (or the end of the string) look for digits
		while(($c = $in->LA(1)) != 'T' && $c != Parser\StringStream::EOF) {
			$digit = self::parseDigit($in);
			// read the identifier
			$id = $in->LA(1);
			$in->consume();
			// make sure it has not already been declared
			if(isset($data[$id])) {
				throw new InvalidArgumentException("'$string' redeclares '$id'", 500);
			}
			// store data
			switch($id) {
				case 'Y':
					$data['Y'] = $digit;
					break;
				case 'M':
					if(!isset($data['Y'])) $data['Y'] = 0;
					$data['M'] = $digit;
					break;
				case 'D':
					if(!isset($data['Y'])) $data['Y'] = 0;
					if(!isset($data['M'])) $data['M'] = 0;
					$data['D'] = $digit;
					break;
				default:
					throw new InvalidArgumentException(
						"'$string' uses unknown identifier '$id' in Date-Part", 500);
			}
		}
		// there is no 'T' part
		if($in->LA(1) == Parser\StringStream::EOF) {
			return self::createPeriod($data);
		}

		// there is one, so let's get to the end of it
		$in->consume(); // eat the 'T'
		while($in->LA(1) != Parser\StringStream::EOF) {
			$digit = self::parseDigit($in);
			// read the identifier
			$id = $in->LA(1);
			$in->consume();
			if($id == 'M') $id = 'MI';
			// make sure it has not already been declared
			if(isset($data[$id])) {
				throw new InvalidArgumentException("'$string' redeclares '$id'", 500);
			}
			// store data
			switch($id) {
				case 'H':
					$data['H'] = $digit;
					break;
				case 'MI':
					if(!isset($data['H'])) $data['H'] = 0;
					$data['MI'] = $digit;
					break;
				case 'S':
					if(!isset($data['H'])) $data['H'] = 0;
					if(!isset($data['MI'])) $data['MI'] = 0;
					$data['S'] = $digit;
					// there can't be anything after the 'S' identifier
					return self::createPeriod($data);
				default:
					throw new InvalidArgumentException(
						"'$string' uses unknown identifier '$id' in Date-Part", 500);
			}
		}
		return self::createPeriod($data);
	}

	private static function createPeriod($data) {
		$defaults = array(
			'Y' => 0,
			'M' => 0,
			'D' => 0,
			'H' => 0,
			'MI' => 0,
			'S' => 0
		);
		$data = array_merge($defaults, $data);
		return self::period(
			$data['Y'], $data['M'], $data['D'],
			$data['H'], $data['MI'], $data['S']);
	}

	private static function parseDigit(Parser\StringStream $in) {
		$digit = '';
		$isNegative = false;
		// any digit might be negative
		if($in->LA(1) == '-') {
			$digit .= '-';
			$isNegative = true;
			$in->consume();
		}
		// range(0..9)
		while('0' <= ($d = $in->LA(1)) && $d <= '9') {
			$digit .= $d;
			$in->consume();
		}
		// make sure the digit is longer than 0 characters...
		if(strlen($digit) < ($isNegative ? 2 : 1)) {
			throw new InvalidArgumentException("'$digit' does not appear to be a valid number", 500);
		}
		return intval($digit);
	}
}