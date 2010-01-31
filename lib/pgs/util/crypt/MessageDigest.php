<?php
namespace pgs\util\crypt;

class MessageDigest {
	const SALT_LENGTH = 7;

	private static $siteSalt = 'THIS_SHOULD_BE_CHANGED_FOR_EVERY_SITE';
	private static $hashAlgo = null;
	public static function siteSalt($salt=null) {
		if($salt == null) return self::$siteSalt;
		
		self::$siteSalt = $salt;
	}

	public static function hashAlgorithm($hashAlgo=null) {
		if($hashAlgo == null) {
			if(self::$hashAlgo == null) {
				self::$hashAlgo = self::findBestAlgorithm();
			}
			return self::$hashAlgo;
		}

		self::$hashAlgo = $hashAlgo;
	}

	/**
	 * Initialize security settings based on the supplied
	 * config object.
	 * Required keys are 'hashAlgorithm' and 'siteSalt'
	 */
	public static function init(Zend_Config $config) {
		self::hashAlgorithm($config->hashAlgorithm);
		self::siteSalt($config->siteSalt);
	}

	public static function createInstance($algorithm=null) {
		return new HashAlgorithm($algorithm == null ? self::hashAlgorithm() : $algorithm, self::$siteSalt);
	}

	private static $algoFamilies = array(
		'md' => array(
			'md5', 'md4', 'md2'
		),
		'sha' => array(
			'sha512', 'sha384', 'sha256', 'sha1'
		),
		'ripemd' => array(
			'ripemd320', 'ripemd256', 'ripemd160', 'ripemd128'
		)
		// TODO: add other families (tiger, haval, ...)
	);
	public static function findBestOfFamily($family) {
		return array_shift(array_intersect(self::$algoFamilies[$family], hash_algos()));
	}

	public static function createInstanceOfFamily($family) {
		return self::createInstance(self::findBestOfFamily($family));
	}

	// based on http://www.larc.usp.br/~pbarreto/hflounge.html
	private static $preferredAlgos = array(
		'whirlpool',
		'ripemd320', 'ripemd256', 'ripemd160', 'ripemd128',
		'sha512', 'sha384', 'sha256', 'sha1',
		'md5' // note that md5 is the worst choice, sha1 is almost as bad
	);
	/**
	 * Returns the most suitable algorithm installed on this system.
	 * Note that we do not know if our guess is correct, so if you know better than we do:
	 * please choose for yourself.
	 */
	public static function findBestAlgorithm() {
		return array_shift(array_intersect(self::$preferredAlgos, hash_algos()));
	}
}
