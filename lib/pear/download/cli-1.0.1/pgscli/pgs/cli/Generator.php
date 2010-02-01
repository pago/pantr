<?php
namespace pgs\cli;

require_once 'Zend/View.php';

/**
 * Description of Generator
 *
 * @property Output $out
 * @author pago
 */
abstract class Generator extends Controller {
	protected $view;
	public function __construct(Request $request, Output $output) {
		parent::__construct($request, $output);
		$this->view = $this->initView();
	}

    protected function mkdir($dir, $mode = 0777) {
		if(is_dir($dir)) {
			$this->out->writeAction('skip', sprintf('Directory %s already exists', $dir), 'WARNING');
			return;
		}
		$this->out->writeAction('create', $dir);
		mkdir($dir);
	}

	protected function copy($source, $dest) {
		$source = $this->getBasePath() . '/templates/' . $source;
		$this->out->writeAction('create', $dest);
		return copy($source, $dest);
	}

	protected function render($source, $dest) {
		$this->out->writeAction('create', $dest);
		file_put_contents($dest, $this->view->render($source));
	}

	protected function getBasePath() {
		return dirname($this->request->getScriptPath());
	}

	protected function initView() {
		$baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
		$view = new \Zend_View(array(
			'scriptPath' => $baseDir,
			// enable short open tags, if disabled
			'useStreamWrapper' => (bool)ini_get('short_open_tag') == false
		));
		return $view;
	}
}