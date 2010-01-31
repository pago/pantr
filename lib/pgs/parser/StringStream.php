<?php
namespace pgs\parser;

class StringStream {
	const EOF = -1;
	
	private $data;
	private $n, $p = 0, $line = 1, $charPositionInLine = 0, $markDepth = 0, $markers, $lastMarker;
	
	public function __construct($input, $numberOfActualCharsInArray=-1) {
		$this->data = $input;
		if($numberOfActualCharsInArray == -1) {
			$this->n = strlen($input);
		} else {
			$this->n = $numberOfActualCharsInArray;
		}
	}
	
	public function reset() {
		$this->p = 0;
		$this->line = 1;
		$this->charPositionInLine = 0;
		$this->markDepth = 0;
	}
	
	public function consume() {
		if($this->p < $this->n) {
			$this->charPositionInLine++;
			if($this->data[$this->p] == "\n") {
				$this->line++;
				$this->charPositionInLine = 0;
			}
			$this->p++;
		}
	}
	
	public function LA($i) {
		if($i == 0) {
			return 0; // undefined
		}
		if($i < 0) {
			$i++;
			if(($this->p+$i-1) < 0) {
				return self::EOF;
			}
		}
		if(($this->p+$i-1) >= $this->n) {
			return self::EOF;
		}
		return $this->data[$this->p+$i-1];
	}
	
	public function index() {
		return $this->p;
	}
	
	public function size() {
		return $this->n;
	}
	
	public function mark() {
		if($this->markers == null) {
			$this->markers = array();
			$this->markers[] = null;
		}
		$this->markDepth++;
		$state = null;
		if($this->markDepth >= count($this->markers)) {
			$state = new CharStreamState();
			$this->markers[] = $state;
		} else {
			$state = $this->markers[$this->markDepth];
		}
		$state->p = $this->p;
		$state->line = $this->line;
		$state->charPositionInLine = $this->charPositionInLine;
		$this->lastMarker = $this->markDepth;
		return $this->markDepth;
	}
	
	public function rewind($m=-1) {
		if($m == -1) $m = $this->lastMarker;
		$state = $this->markers[$m];
		$this->seek($state->p);
		$this->line = $state->line;
		$this->charPositionInLine = $state->charPositionInLine;
		$this->release($m);
	}
	
	public function release($marker) {
		$this->markDepth = $marker - 1;
	}
	
	public function seek($index) {
		if($index <= $this->p) {
			$this->p = $index;
			return;
		}
		while($this->p < $index) $this->consume();
	}
	
	public function substring($start, $stop) {
		return substr($this->data, $start, $stop-$start+1);
	}
	
	public function getLine() {
		return $this->line;
	}
	
	public function getCharPositionInLine() {
		return $this->charPositionInLine;
	}
	
	public function setLine($line) {
		$this->line = $line;
	}
	
	public function setCharPositionInLine($pos) {
		$this->charPositionInLine = $pos;
	}
}