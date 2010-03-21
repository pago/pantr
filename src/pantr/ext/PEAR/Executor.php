<?php
namespace pantr\ext\PEAR;

class Executor {
	private $config;
	public function __construct($config) {
		$this->config = $config;
		\PEAR_Command::setFrontendType('CLI');
		\PEAR_Command::getFrontendObject()->setConfig($this->config);
	}
	
	public function invoke() {
		$params = func_get_args();
		switch(func_num_args()) {
			case 1:
				list($command) = $params;
				$options = array();
				$args = array();
				break;
			case 2:
				list($command, $options) = $params;
				if(is_string($options)) {
					$args = array($options);
					$options = array();
				} else {
					$args = array();
				}
				
				break;
			case 3:
				list($command, $options, $args) = $params;
				if(is_string($args)) {
					if(is_string($options)) {
						$args = array($options, $args);
						$options = array();
					} else {
						$args = array($args);
					}
				}
				break;
			default:
				$command = array_shift($params);
				if(is_array($params[0])) {
					$options = array_shift($params);
				}
				$args = $params;
		}
		$cmd = \PEAR_Command::factory($command, $this->config);
		$cmd->run($command, $options, $args);
	}
}