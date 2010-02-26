<?php
/* 
 * Copyright (c) 2010 Patrick Gotthardt
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
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