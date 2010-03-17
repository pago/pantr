<?php
namespace pantr\ext;

use pantr\pantr;

use Pagosoft\IO\FTPClient;

/**
 * A class to work with a FTP server.
 * Example:
 * FTPDeploy::onServer('ftp.localhost.lo')
 * 	->loginAs('user', 'pass')
 * 	->run(function($ftp) {
 *		$ftp->put('remote/test.php', 'local/test.php');
 * 	});
 */
class FTPDeploy {
	private $server, $port, $user, $pass;
	
	public function __construct($server, $port=21) {
		$this->server = $server;
		$this->port = $port;
	}
	
	public function loginAs($user, $pass) {
		$this->user = $user;
		$this->pass = $pass;
		return $this;
	}
	
	public function run($fn) {
		$ftp = new FTPClient();
		if($ftp->connect($this->server, $this->port)) {
			if($ftp->login($this->user, $this->pass)) {
				try {
					$ftp->pasv(true);
					$fn($ftp);
				} catch(\Exception $e) {
					$ftp->disconnect();
					throw $e;
				}
			}
			$ftp->disconnect();
		}
		return $this;
	}
	
	public function upload($files, $dest, $in=null) {
		$this->run(function($ftp) use ($files, $dest, $in) {
			// do the actual upload
			$ftp->mkdir($dest);
			$ftp->cd($dest);
			pantr::writeAction('ftp', 'upload');
			
			$action = new FTPDeployAction($ftp);
			$action->upload($files, $in);
			
			// erase message in line above
			pantr::write("\033[1A");
			pantr::writeAction('ftp', 'upload done');
		});
		return $this;
	}
	
	public static function onServer($server, $port=21) {
		return new FTPDeploy($server, $port);
	}
}

class FTPDeployAction {
	private $ftp, $progressBar;
	public function __construct($ftp) {
		$this->ftp = $ftp;
	}
	
	public function upload($files, $in) {
		$this->progressBar = new \Pagosoft\Console\ProgressBar(pantr::out(), count($files));
		$tree = $this->createTree($files);
		if(is_null($in)) {
			$stack = array();
		} else {
			$stack = array($in);
		}
		$this->progressBar->paint();
		$this->uploadTree($tree, $stack);
		$this->progressBar->erase();
	}
	
	private function visit($file, &$struct) {
		$path = explode('/', $file, 2);
		if(count($path) == 1) {
			$struct[] = $file;
		} else {
			if(!isset($struct[$path[0]])) {
				$struct[$path[0]] = array();
			}
			$this->visit($path[1], $struct[$path[0]]);
		}
	}
	
	private function createTree($files) {
		$tree = array();
		foreach($files as $file) {
			$this->visit($file, $tree);
		}
		return $tree;
	}
	
	public function uploadTree($tree, $stack=array()) {
		foreach($tree as $k => $file) {
			if(is_int($k)) {
				$lpath = implode('/', $stack) . '/' . $file;
				$this->ftp->put($file, $lpath);
				$this->progressBar->incProgress();
				pantr::log()->notice('ftp upload '.$lpath);
			} else {
				array_push($stack, $k);
				$this->ftp->mkdir($k);
				$this->ftp->cd($k);
				$this->uploadTree($file, $stack);
				$this->ftp->cdup();
				array_pop($stack);
			}
		}
	}
}