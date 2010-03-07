<?php
namespace Pagosoft\Console;

/**
 * This class acts as a console input reader.
 */
class Input {
	/**
	 * If a valid connection to stdin can be established
	 * the supplied function will be executed on the
	 * connection. After the function finishes, stdin is closed
	 * and the result of the function is returned.
	 *
	 * The function therefore must have the following signature:
	 * fn(fp:Resource):mixed
	 */
	public function with($fn) {
		$fp = fopen('php://stdin', 'r');
		if($fp === false) throw new \Exception('Could not read from stdin');
		$result = $fn($fp);
		fclose($fp);
		return $result;
	}
	
	
	public function readline() {
		return $this->with(function($fp) {
			return rtrim(fgets($fp), "\r\n");
		});
	}
	
	/**
	 * Reads input until the EOF is read or an empty line is read.
	 * You can deactivate the latter by passing
	 * <code>false</code> as argument.
	 *
	 * The EOF can be send using either ctrl+d (unix/linux)
	 * or ctrl+z (Windows).
	 */
	public function read($breakOnEmptyLine=true) {
		return $this->with(function($fp) use ($breakOnEmptyLine) {
			$input = '';
			do {
				$line = fgets($fp);
				if($breakOnEmptyLine && rtrim($line, "\r\n") == '') {
					break;
				}
				$input .= $line;
			} while(!feof($fp));
			return $input;
		});
	}
}