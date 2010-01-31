<?php
namespace pgs\util;

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2004-2006 Sean Kerr <sean@code-box.org>
 * (c) 2009      Patrick Gotthardt <patrick@pagosoft.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * FormattedException is the base class for all phoenix related exceptions and
 * provides an additional method for printing up a detailed view of an
 * exception.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Sean Kerr <sean@code-box.org>
 * @author     Patrick Gotthardt <patrick@pagosoft.com>
 */
class FormattedException extends \Exception {
	private $wrappedException;

	public static function createFromException(\Exception $e) {
		if($e instanceof FormattedException) {
			return $e;
		}

		$code = $e->getCode();
		$exception = new FormattedException(sprintf('Wrapped %s: %s', get_class($e), $e->getMessage()),
			$code != 0 ? $code : 500);
		$exception->setWrappedException($e);

		return $exception;
	}

	public function getName() {
		$e = is_null($this->wrappedException) ? $this : $this->wrappedException;
		return get_class($e);
	}

	/**
	* Changes the wrapped exception.
	*
	* @param Exception $e An Exception instance
	*/
	public function setWrappedException(\Exception $e) {
		$this->wrappedException = $e;
	}

	/**
   * Prints the stack trace for this exception.
   */
	public function printStackTrace() {
		$exception = is_null($this->wrappedException) ? $this : $this->wrappedException;

		if (!$isTestEnv) {
		// log all exceptions in php log
			error_log($exception->getMessage());

			// clean current output buffer
			while (ob_get_level()) {
				if (!ob_end_clean()) {
					break;
				}
			}

			ob_start();

			if($this->getCode() == 404) {
				header('HTTP/1.0 404 File Not Found');
			} else {
				header('HTTP/1.0 500 Internal Server Error');
			}
		}

		try {
			self::outputStackTrace($exception);
		}
		catch (Exception $e) {
		}

		if (!$isTestEnv) {
			exit(1);
		}
	}

	static protected function outputStackTrace(\Exception $exception) {
		$traces = self::getTraces($exception, 'html');
		echo '<ul><li>', implode('</li><li>', $traces), '</li></ul>';
	}

	/**
	* Returns an array of exception traces.
	*
	* @param Exception $exception An Exception implementation instance
	* @param string    $format    The trace format (plain or html)
	*
	* @return array An array of traces
	*/
	static protected function getTraces($exception, $format = 'plain') {
		$traceData = $exception->getTrace();
		array_unshift($traceData, array(
			'function' => '',
			'file'     => $exception->getFile() != null ? $exception->getFile() : 'n/a',
			'line'     => $exception->getLine() != null ? $exception->getLine() : 'n/a',
			'args'     => array(),
		));

		$traces = array();
		if ($format == 'html') {
			$lineFormat = 'at <strong>%s%s%s</strong>(%s)<br />in <em>%s</em> line %s <a href="#" onclick="toggle(\'%s\'); return false;">...</a><br /><ul id="%s" style="display: %s">%s</ul>';
		}
		else {
			$lineFormat = 'at %s%s%s(%s) in %s line %s';
		}
		for ($i = 0, $count = count($traceData); $i < $count; $i++) {
			$line = isset($traceData[$i]['line']) ? $traceData[$i]['line'] : 'n/a';
			$file = isset($traceData[$i]['file']) ? $traceData[$i]['file'] : 'n/a';
			$shortFile = $file;
			//$shortFile = preg_replace(array('#^'.preg_quote(sfConfig::get('sf_root_dir')).'#', '#^'.preg_quote(realpath(sfConfig::get('sf_symfony_lib_dir'))).'#'), array('SF_ROOT_DIR', 'SF_SYMFONY_LIB_DIR'), $file);
			$args = isset($traceData[$i]['args']) ? $traceData[$i]['args'] : array();
			$traces[] = sprintf($lineFormat,
				(isset($traceData[$i]['class']) ? $traceData[$i]['class'] : ''),
				(isset($traceData[$i]['type']) ? $traceData[$i]['type'] : ''),
				$traceData[$i]['function'],
				self::formatArgs($args, false, $format),
				$shortFile,
				$line,
				'trace_'.$i,
				'trace_'.$i,
				$i == 0 ? 'block' : 'none',
				self::fileExcerpt($file, $line)
			);
		}

		return $traces;
	}

  /**
   * Returns an HTML version of an array as YAML.
   *
   * @param array $values The values array
   *
   * @return string An HTML string
   */
	static protected function formatArrayAsHtml($values) {
		return '<pre>'.htmlspecialchars(@sfYaml::Dump($values), ENT_QUOTES, sfConfig::get('sf_charset', 'UTF-8')).'</pre>';
	}

  /**
   * Returns an excerpt of a code file around the given line number.
   *
   * @param string $file A file path
   * @param int    $line The selected line number
   *
   * @return string An HTML string
   */
	static protected function fileExcerpt($file, $line) {
		if (is_readable($file)) {
			$content = preg_split('#<br />#', highlight_file($file, true));

			$lines = array();
			for ($i = max($line - 3, 1), $max = min($line + 3, count($content)); $i <= $max; $i++) {
				$lines[] = '<li'.($i == $line ? ' class="selected"' : '').'>'.$content[$i - 1].'</li>';
			}

			return '<ol start="'.max($line - 3, 1).'">'.implode("\n", $lines).'</ol>';
		}
	}

  /**
   * Formats an array as a string.
   *
   * @param array   $args   The argument array
   * @param boolean $single
   * @param string  $format The format string (html or plain)
   *
   * @return string
   */
	static protected function formatArgs($args, $single = false, $format = 'html') {
		$result = array();

		$single and $args = array($args);

		foreach ($args as $key => $value) {
			if (is_object($value)) {
				$result[] = ($format == 'html' ? '<em>object</em>' : 'object').'(\''.get_class($value).'\')';
			}
			else if (is_array($value)) {
				$result[] = ($format == 'html' ? '<em>array</em>' : 'array').'('.self::formatArgs($value).')';
			}
			else if ($value === null) {
				$result[] = $format == 'html' ? '<em>null</em>' : 'null';
			}
			else if (!is_int($key)) {
				$result[] = $format == 'html' ? "'$key' =&gt; '$value'" : "'$key' => '$value'";
			}
			else {
				$result[] = "'".$value."'";
			}
		}

		return implode(', ', $result);
	}
}