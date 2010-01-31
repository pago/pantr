<?php
namespace pgs\parser;

class Util {
	/**
	 * Generates a unique ID.
	 * It is guarenteed that gensym($seed) == gensym($seed).
	 */
	public static function gensym($seed='') {
		return '____'.md5($seed != '' ? $seed : microtime());
	}
}
