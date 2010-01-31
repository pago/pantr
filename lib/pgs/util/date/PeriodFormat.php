<?php
namespace pgs\util\date;

require_once 'Zend/Locale.php';
require_once 'Zend/Registry.php';

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
 * Description of PeriodFormat
 *
 * @author pago
 */
class PeriodFormat {
	const UNIT = '%u';
	const AMOUNT = '%n';
	const DEFAULT_LANG = 'de';

	private $lang;

	function __construct(\Zend_Locale $locale = null) {
		if(is_null($locale)) {
			if(\Zend_Registry::isRegistered('\Zend_Locale')) {
				$locale = \Zend_Registry::get('\Zend_Locale');
			} else {
				$locale = new \Zend_Locale();
			}
		}
		$this->lang = $this->resolveLocale($locale);
	}

	private function resolveLocale(\Zend_Locale $locale) {
		$proposed = array($locale->toString(), $locale->getLanguage(), self::DEFAULT_LANG);
		foreach($proposed as $lang) {
			if(isset(self::$trans[$lang])) {
				return $lang;
			}
		}
	}

	public function format(Period $period) {
		// this method actually has a pretty bad smell on it.
		// it does what it's supposed to, but at one point or another it should be
		// refactored to use a combination of
		// the strategy and chain of responsibility-pattern
		// so the variants can be customized
		if($period->getYears() != 0) {
			return $this->doFormat($this->lang, 'years', $period->getYears());
		}
		if($period->getMonths() != 0) {
			return $this->doFormat($this->lang, 'months', $period->getMonths());
		}
		if($period->getDays() != 0) {
			$amount = $period->getDays();
			// if the amount of days is larger than 6 we'll want to
			// express it in weeks instead
			if($amount > 6 || $amount < -6) {
				$amount = intval($amount / 7);
				return $this->doFormat($this->lang, 'weeks', $amount);
			} else {
				return $this->doFormat($this->lang, 'days', $amount);
			}
		}
		if($period->getHours() != 0) {
			return $this->doFormat($this->lang, 'hours', $period->getHours());
		}
		if($period->getMinutes() != 0) {
			return $this->doFormat($this->lang, 'minutes', $period->getMinutes());
		}

		return $this->doFormat($this->lang, 'justNow', 0);
	}

	private function doFormat($lang, $unit, $amount) {
		$basic = self::$trans[$lang][$unit];
		$format = '';

		// select the format and adjust the amount to be a positive number if it
		// was negative before
		if($amount < 0) {
			$format = $basic['past'];
			$amount = 0 - $amount; // == abs($amount) but might be faster...
		} else {
			$format = $basic['future'];
		}
		
		// select the time unit based on the amount
		$unit = $amount == 1 ? $basic['singular'] : $basic['plural'];

		// see if we have a special variant for this amount
		if(isset($basic['amounts'][$amount])) {
			$amount = $basic['amounts'][$amount];
		}

		// do the formatting
		return str_replace(
			array(self::UNIT, self::AMOUNT), array($unit, $amount),
			$format
		);
	}

	// this array provides the translations for the period
	private static $trans = array(
		'de' => array(
			'years' => array(
				'past' => 'vor %n %u',
				'future' => 'in %n %u',
				'singular' => 'Jahr',
				'plural' => 'Jahren',
				'amounts' => array(
					1 => 'einem'
				)
			),
			'months' => array(
				'past' => 'vor %n %u',
				'future' => 'in %n %u',
				'singular' => 'Monat',
				'plural' => 'Monaten',
				'amounts' => array(
					1 => 'einem'
				)
			),
			'weeks' => array(
				'past' => 'vor %n %u',
				'future' => 'in %n %u',
				'singular' => 'Woche',
				'plural' => 'Wochen',
				'amounts' => array(
					1 => 'einer'
				)
			),
			'days' => array(
				'past' => 'vor %n %u',
				'future' => 'in %n %u',
				'singular' => 'Tag',
				'plural' => 'Tagen',
				'amounts' => array(
					1 => 'einem'
				)
			),
			'hours' => array(
				'past' => 'vor %n %u',
				'future' => 'in %n %u',
				'singular' => 'Stunde',
				'plural' => 'Stunden',
				'amounts' => array(
					1 => 'einer'
				)
			),
			'minutes' => array(
				'past' => 'vor %n %u',
				'future' => 'in %n %u',
				'singular' => 'Minute',
				'plural' => 'Minuten',
				'amounts' => array(
					1 => 'einer'
				)
			),
			'justNow' => array(
				'past' => 'vor wenigen Minuten',
				'future' => 'in kürze',
				'singular' => '',
				'plural' => ''
			)
		),
		'en' => array(
			'years' => array(
				'past' => '%n %u ago',
				'future' => '%n %u from now',
				'singular' => 'year',
				'plural' => 'years'
			),
			'months' => array(
				'past' => '%n %u ago',
				'future' => '%n %u from now',
				'singular' => 'month',
				'plural' => 'months'
			),
			'weeks' => array(
				'past' => '%n %u ago',
				'future' => '%n %u from now',
				'singular' => 'week',
				'plural' => 'weeks'
			),
			'days' => array(
				'past' => '%n %u ago',
				'future' => '%n %u from now',
				'singular' => 'day',
				'plural' => 'days'
			),
			'hours' => array(
				'past' => '%n %u ago',
				'future' => '%n %u from now',
				'singular' => 'hour',
				'plural' => 'hours'
			),
			'minutes' => array(
				'past' => '%n %u ago',
				'future' => '%n %u from now',
				'singular' => 'minute',
				'plural' => 'minutes'
			),
			'justNow' => array(
				'past' => 'moments ago',
				'future' => 'right now',
				'singular' => '',
				'plural' => ''
			)
		)
	);
}