<?php
namespace Pagosoft\Console;

/**
 * This class can be used to output colorized and formatted
 * content to the console.
 *
 * @author pago
 */
class Output {
	private $level = 0;

	private static $colorizedOutputSupported = true;
	private static $options = array('bold' => 1, 'underscore' => 4,
		'blink' => 5, 'reverse' => 7, 'conceal' => 8);
    private static $foreground = array('black' => 30, 'red' => 31,
		'green' => 32, 'yellow' => 33, 'blue' => 34, 'magenta' => 35,
		'cyan' => 36, 'white' => 37);
    private static $background = array('black' => 40, 'red' => 41,
		'green' => 42, 'yellow' => 43, 'blue' => 44, 'magenta' => 45,
		'cyan' => 46, 'white' => 47);

	private $styles = array();

	function __construct() {
		if(self::canHandleColorizedOutput()) {
			self::$colorizedOutputSupported = false;
		}

		$this->registerStyle('PARAMETER', array('fg' => 'cyan'))
			->registerStyle('COMMENT', array('fg' => 'green'))
			->registerStyle('INFO', array('fg' => 'blue'))
			->registerStyle('ACTION_DELETE', array('fg' => 'red'))
			->registerStyle('BOLD', array('bold' => true))
			->registerStyle('ITALIC', array('italic' => true))
			->registerStyle('INFO', array('fg' => 'yellow'))
			->registerStyle('WARNING', array('fg' => 'red'))
			->registerStyle('ERROR', array('fg' => 'red', 'reverse' => true, 'bold' => true))
			->registerStyle('SECTION', array('bold' => true));
	}
	
	public static function disableColorizedOutput($disable=true) {
		self::$colorizedOutputSupported = !$disable && self::canHandleColorizedOutput();
	}
	
	private static function canHandleColorizedOutput() {
		return !(DIRECTORY_SEPARATOR == '\\' || !function_exists('posix_isatty') || !@posix_isatty(STDOUT));
	}

	public static function isColorizedOutputSupported() {
		return self::$colorizedOutputSupported == false;
	}

	/**
	 * @return Output
	 */
	public function registerStyle($name, array $def) {
		$this->styles[$name] = $def;
		return $this;
	}
	
	public function parseText($text) {
		$self = $this;
		$fn = function($matches) use ($self) {
			return $self->colorize($matches[1], $matches[2]);
		};
		return preg_replace_callback(
			'`\[(.+?)\|([a-zA-Z0-9]+)\]`is',
			$fn, $text);
	}

	public function colorize($t, $style='') {
		if(!self::$colorizedOutputSupported) {
			return $t;
		}
		$params = null;
		if(is_array($style)) {
			$params = $style;
		} elseif(isset($this->styles[$style])) {
			$params = $this->styles[$style];
		}
		$codes = array();
		if(isset($params['fg'])) $codes[] = self::$foreground[$params['fg']];
		if(isset($params['bg'])) $codes[] = self::$background[$params['bg']];
		// check which options have been set
		foreach(self::$options as $key => $value) {
			if(isset($params[$key]) && $params[$key]) $codes[] = $value;
		}
		return "\033[" . implode(';', $codes) . 'm' . $t. "\033[0m";
	}

	/**
	 * Outputs the given text as it is (without indenting it)
	 * @param string $t
	 * @return Output
	 */
	public function write($t, $style = null) {
		if($style == null) {
			echo $this->parseText($t);
		} else {
			echo $this->colorize($t, $style);
		}
		return $this;
	}

	/**
	 * @return Output
	 */
	public function writeln($t, $style = null) {
		return $this->write(str_repeat("\t", $this->level) . $t . "\n", $style);
	}

	/**
	 * @return Output
	 */
	public function writeblock($t) {
		$lines = explode("\n", $t);
		foreach($lines as $line) {
			$this->writeln($line);
		}
		return $this;
	}

	/**
	 * @return Output
	 */
	public function writeIndent() {
		echo str_repeat("\t", $this->level);
		return $this;
	}

	/**
	 * @return Output
	 */
	public function nl() {
		// I'm a mac user with a german keyboard... *sigh*
		echo "\n";
		return $this;
	}

	/**
	 * @return Output
	 */
	public function indent() {
		$this->level++;
		return $this;
	}

	/**
	 * @return Output
	 */
	public function dedent() {
		$this->level--;
		return $this;
	}
}