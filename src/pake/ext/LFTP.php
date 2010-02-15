<?php
namespace pake\ext;

class LFTP {
	private $source, $ftppath = '', $user, $pass, $ftpurl, $exclude;
	private function __construct($server) {
		$this->ftpurl = $server;
	}
	
	public function loginAs($user, $pass) {
		$this->user = $user;
		$this->pass = $pass;
		return $this;
	}
	
	public function from($source) {
		$this->source = $source;
		return $this;
	}
	
	public function exclude($exclude) {
		$this->exclude = $exclude;
		return $this;
	}
	
	public function into($ftppath) {
		$this->ftppath = $ftppath;
		return $this;
	}
	
	public function upload($ftppath=null) {
		$ftppath = $ftppath ?: $this->ftppath;
		if($this->exclude != null) {
			$x = '-X ' . $this->exclude . ' ';
		} else {
			$x = '';
		}
		$cmd = 'lftp -c \'open -e "mirror -R ' . $x
			. $this->source . ' ' . $ftppath
			. '" -u ' . $this->user.','.$this->pass.' '.$this->ftpurl.'\'';
		passthru($cmd, $return);
		if($return > 0) {
			echo "Upload was not successful.\n";
		}
	}
	
	public static function forServer($ftpurl) {
		if(substr($ftpurl, 0, 6) != 'ftp://') {
			$ftpurl = 'ftp://' . $ftpurl;
		}
		return new LFTP($ftpurl);
	}
}