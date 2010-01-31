<?php
namespace pgs\cli;

use pgs\parser as Parser;

/**
 * Description of Request
 *
 * @author pago
 */
class Request implements \ArrayAccess, \Countable {
    private $store = array(), $indexedStore = array();
	private $index = 0;
	private $scriptPath;

	public function __construct($args=array()) {
		foreach($args as $arg) {
			$this->insertRaw($arg);
		}
	}

	public function setScriptPath($path) {
		$this->scriptPath = $path;
	}

	public function getScriptPath() {
		return $this->scriptPath;
	}

	/**
	 * Parses a raw command line argument and stores it within the request.
	 * EBNF:
	 * arg ::= parameter | flag | cluster | argument
	 * parameter ::= flag "=" argument
	 * flag ::= "--" argument | "-" char
	 * cluster ::= "-" char+
	 * argument ::= char+
	 * char ::= [A-Za-z0-9_]
	 *
	 * @param string $arg command line argument
	 */
	public function insertRaw($arg) {
		$i = new Parser\StringStream($arg);
		if($i->LA(1) == '-') {
			// could be: parameter, flag, cluster
			$i->consume();
			if($i->LA(1) == '-') {
				$i->consume();
				// could be: parameter, flag
				// look for "=" to decide; need's variable lookahead
				while($i->LA(1) != Parser\StringStream::EOF) {
					if($i->LA(1) == '=') {
						// FOUND: parameter
						$pos = $i->index();
						$key = substr($arg, 2, $pos-2);
						$value = substr($arg, $pos+1);
						$this[$key] = $value;
						return;
					}
					$i->consume();
				}

				// FOUND: flag
				$flag = substr($arg, 2);
				$this[$flag] = true;
			} elseif($i->LA(2) == '=') {
				// FOUND: parameter
				$key = $i->LA(1);
				$value = substr($arg, 3);
				$this[$key] = $value;
			} else {
				// FOUND: flag or cluster
				// both can be handled exactly the same
				while($i->LA(1) != Parser\StringStream::EOF) {
					$flag = $i->LA(1);
					$this[$flag] = true;
					$i->consume();
				}
			}
		} else {
			// FOUND: argument
			$this->indexedStore[] = $arg;
		}
	}

	public function getUnnamedArgumentCount() {
		return count($this->indexedStore) - $this->index;
	}

	public function shiftArguments($by=1) {
		$this->index += $by;
	}

	public function set($key, $value) {
		if(is_int($key)) {
			$this->indexedStore[$key + $this->index] = $value;
		}
		$this->store[$key] = $value;
	}

	public function get($key) {
		if(is_int($key)) {
			return $this->indexedStore[$key + $this->index];
		}
		return $this->store[$key];
	}
	
	// implement Countable
	public function count() {
		return count($this->store) + $this->getUnnamedArgumentCount();
	}

	// implement ArrayAccess
	public function offsetExists ($offset) {
		if(is_int($offset)) {
			return isset($this->indexedStore[$offset + $this->index]);
		}
		return isset($this->store[$offset]);
	}

	/**
	 * @param offset
	 */
	public function offsetGet ($offset) {
		return $this->get($offset);
	}

	/**
	 * @param offset
	 * @param value
	 */
	public function offsetSet ($offset, $value) {
		$this->set($offset, $value);
	}

	/**
	 * @param offset
	 */
	public function offsetUnset ($offset) {
		// not implemented
	}
}