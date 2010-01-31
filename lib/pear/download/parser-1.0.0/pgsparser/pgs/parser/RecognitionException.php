<?php
namespace pgs\parser;

class RecognitionException extends Exception {
	public $fileName;
	
	public function __construct($msg, $fileName='Unknown') {
		parent::__construct($msg);
		$this->fileName = $fileName;
	}
}