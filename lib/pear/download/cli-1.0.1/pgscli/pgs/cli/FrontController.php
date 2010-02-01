<?php
namespace pgs\cli;

use pgs\util\Filter;

/**
 * Description of FrontController
 *
 * @author pago
 */
class FrontController extends Controller {
    /**
	 * Method is executed in dispatch process, after the validation.
	 */
	protected function handle() {
		if(!isset($this[0])) {
			$this->displayUsage();
			return;
		}
		$controller = $this[0];
		$file = $this->getPath($controller);
		$this->request->setScriptPath($file);
		if(file_exists($file)) {
			$this->request->shiftArguments();
			$controller = $this->loadClass($file, $this->getClassName($controller));
			$controller->dispatch();
		} else {
			$this->displayUsage();
		}
	}

	protected function getPath($controller) {
		$controller = Filter::dashToCamelCase($controller);
		$controller = Filter::colonToSlash($controller);
		return 'scripts' . DIRECTORY_SEPARATOR . $controller . 'Controller.php');
	}

	protected function getClassName($controller) {
		$controller = Filter::dashToCamelCase($controller);
		$controller = Filter::colonToUnderscore($controller);
		return $controller . 'Controller';
	}

	/**
	 * Returns a description of the controller.
	 */
	public function getDescription() {
		return 'should not be called (FrontController)';
	}

	protected function loadClass($source, $name) {
		require_once $source;
		return new $name($this->request, $this->out);
	}
}