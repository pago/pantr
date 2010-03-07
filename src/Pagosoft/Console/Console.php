<?php
namespace Pagosoft\Console;

class Console {
	private $in, $out;
	
	public function __construct(Input $in, Output $out) {
		$this->in = $in;
		$this->out = $out;
	}
	
	public function in() {
		return $this->in;
	}
	
	public function out() {
		return $this->out;
	}
	
	public function prompt($prompt, $default=null) {
		$this->out->write($prompt);
		if(!is_null($default)) {
			$this->out->write(' ['.$default.']');
		}
		$this->out->write(':');
		$result = $this->in->readline();
		return $result == '' && !is_null($default)
			? $default
			: $result;
	}
	
	/**
	 * This method can be used to interact with another console program.
	 * The supplied function will be invoked with the standard pipes
	 * so it can read and write data to them.
	 * After the function has been executed, all pipes are closed,
	 * the process is exited and the result is returned.
	 */
	public function exec($cmd, $fn, $cwd='./') {
		$descriptorspec = array(
			0 => array("pipe","r"),
			1 => array("pipe","w"),
			2 => array("pipe","a")
		);

		// define current working directory where files would be stored
		$process = proc_open($cmd, $descriptorspec, $pipes, $cwd);

		if(is_resource($process)) {
			// anatomy of $pipes: 0 => stdin, 1 => stdout, 2 => error log
			$result = $fn($pipes[0], $pipes[1], $pipes[2]);
			
			// close pipes
			foreach($pipes as $pipe) {
				if(is_resource($pipe)) {
					fclose($pipe);
				}
			}

			// all pipes must be closed before calling proc_close. 
			// proc_close() to avoid deadlock
			proc_close($process);
		}
		
		return $result;
	}
}