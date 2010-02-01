<?php
namespace pgs\cli;

use pgs\util\Filter;

/**
 * Description of Controller
 *
 * @author pago
 */
abstract class Controller implements \ArrayAccess {
	/**
	 *
	 * @var Request Request
	 */
    protected $request;
	/**
	 *
	 * @var Output
	 */
	protected $out;
	private $requestAliasTable = array();
	private $descriptionTable = array();
	private $requiredArguments = array();
	protected $unnamedArgumentCountExpectation = 0;

	public function __construct(Request $request, Output $output) {
		$this->request = $request;
		$this->out = $output;
		$this->setUp();
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

	final public function dispatch() {
		// run validation
		foreach($this->requiredArguments as $arg) {
			if(!isset($this[$arg])) {
				$this->displayUsage();
				return false;
			}
		}
		if($this->request->getUnnamedArgumentCount() < $this->unnamedArgumentCountExpectation) {
			$this->displayUsage();
			return false;
		}
		// run controller
		$this->handle();
	}

	public function displayUsage() {
		$command = get_class($this);
		$command = substr($command, 0, -strlen('Controller'));
		$command = Filter::camelCaseToDash($command);
		$command = Filter::underscoreToColon($command);
		$command = Filter::stringToLower($command);
		$this->out->writeHelp(
			$command . ' -- ' . $this->getDescription(),
			$this->getUsage(),
			$this->getLongDescription()
		);
		if(count($this->descriptionTable) > 0) {
			$this->out->nl()->writeln('Options:', 'BOLD');
			foreach($this->descriptionTable as $long => $desc) {
				// check if there is a short version
				if(isset($this->requestAliasTable[$long])) {
					$short = $this->requestAliasTable[$long];
					$this->out->writeOption($short, $long, $desc);
				} else {
					$this->out->writeOption($long, $desc);
				}
			}
		}
	}

	/**
	 * Method is called once dispatch is executed. It's run before validation.
	 * Should register all known parameters.
	 */
	protected function setUp() {}

	/**
	 * Method is executed in dispatch process, after the validation.
	 */
	protected abstract function handle();

	/**
	 * Returns a description of the controller.
	 * Should fit in a short line. Maybe 40 characters long.
	 */
	public abstract function getDescription();

	/**
	 * Returns a more detailed description of the controller.
	 * 
	 * @return String
	 */
	public function getLongDescription() {
		return '';
	}

	/**
	 * Returns a string describing the usage of the controller.
	 *
	 * @return String
	 */
	public function getUsage() {
		return '';
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
		throw new Exception('Controller is not to be written to');
	}

	public function offsetUnset($offset) {
		throw new Exception('Controller is not to be written to');
	}
}