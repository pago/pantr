<?php
namespace pgs\cli;

use pake\Pake;

class RequestContainer implements \ArrayAccess {
	private $request;
	private $requestAliasTable = array();
	private $descriptionTable = array();
	private $requiredArguments = array();
	private $unnamedArgumentCountExpectation = 0;
	
	public function __construct(Request $req) {
		$this->request = $req;
	}
	
	public function registerParameter($long, $description='', $short=null, $required=false) {
		if(!is_null($short)) {
			$this->requestAliasTable[$long] = $short;
			$this->requestAliasTable[$short] = $long;
		}
		$this->descriptionTable[$long] = $description;
		if($required) {
			$this->requiredArguments[] = $long;
		}
	}
	
	public function expectNumArgs($num) {
		$this->unnamedArgumentCountExpectation = $num;
	}
	
	public function isValid() {
		foreach($this->requiredArguments as $arg) {
			if(!isset($this[$arg])) {
				return false;
			}
		}
		if($this->request->getUnnamedArgumentCount() < $this->unnamedArgumentCountExpectation) {
			return false;
		}
		return true;
	}
	
	public function printOptions() {
		if(count($this->descriptionTable) > 0) {
			Pake::nl()->writeln('Options:', 'BOLD');
			foreach($this->descriptionTable as $long => $desc) {
				// check if there is a short version
				if(isset($this->requestAliasTable[$long])) {
					$short = $this->requestAliasTable[$long];
					Pake::writeOption($short, $long, $desc);
				} else {
					Pake::writeOption($long, $desc);
				}
			}
		}
	}
	
	// ArrayAccess implementation
	public function get($key) {
		if(isset($this->request[$key])) {
			return $this->request[$key];
		} elseif(isset($this->requestAliasTable[$key])) {
			// translate key
			$key = $this->requestAliasTable[$key];
			if(isset($this->request[$key])) {
				return $this->request[$key];
			}
		}
		return null;
	}

	public function offsetExists($offset) {
		if(isset($this->request[$offset])) {
			return true;
		} elseif(isset($this->requestAliasTable[$offset])) {
			// translate offset
			$offset = $this->requestAliasTable[$offset];
			if(isset($this->request[$offset])) {
				return true;
			}
		}
		return false;
	}

	public function offsetGet($offset) {
		return $this->get($offset);
	}

	public function offsetSet($offset, $value) {
		throw new \Exception('Controller is not to be written to');
	}

	public function offsetUnset($offset) {
		throw new \Exception('Controller is not to be written to');
	}
}