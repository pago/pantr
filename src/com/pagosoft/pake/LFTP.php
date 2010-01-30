<?php
namespace com\pagosoft\pake;

class LFTP {
	private $source, $ftppath = '', $user, $pass, $ftpurl;
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
	
	public function into($ftppath) {
		$this->ftppath = $ftppath;
		return $this;
	}
	
	public function upload() {
		$cmd = 'lftp -c \'open -e "mirror -R '
			. $this->source . ' ' . $this->ftppath
			. '" -u ' . $this->user.','.$this->pass.' '.$this->ftpurl.'\'';
		
	}
	
	public static function forServer($ftpurl) {
		if(substr($ftpurl, 0, 6) != 'ftp://') {
			$ftpurl = 'ftp://' . $ftpurl;
		}
		return new LFTP($ftpurl);
	}
}