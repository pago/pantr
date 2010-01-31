<?php
namespace pgs\parser;

class TokenStream {
	protected $lexer, $tokens, $lastMarker, $p = -1, $tokensSize;
	
	public function __construct($lexer=null) {
		$this->tokens = array();
		$this->lexer = $lexer;
	}
	
	public function setTokenSource(Lexer $lexer) {
		$this->lexer = $lexer;
		$this->tokens = array();
		$this->p = -1;
	}
	
	public function setTokens($tokens) {
		$this->tokens = $tokens;
		$this->tokensSize = count($this->tokens);
		$this->p = 0;
	}
	
	protected function fillBuffer() {
		$index = 0;
		$t = $this->lexer->nextToken();
		while($t != null && $t != EOF_TOKEN) {
			$t->tokenIndex = $index;
			$this->tokens[] = $t;
			$index++;
			$t = $this->lexer->nextToken();
		}
		$this->p = 0;
		$this->tokensSize = count($this->tokens);
	}
	
	public function consume() {
		if($p < $this->tokensSize) {
			$this->p++;
		}
	}
	
	public function LT($k) {
		if($this->p == -1) $this->fillBuffer();
		if($k == 0) return null;
		if($k < 0) return $this->LB(-$k);
		if(($this->p+$k-1) >= $this->tokensSize) {
			return EOF_TOKEN;
		}
		return $this->tokens[$this->p+$k-1];
	}
	
	public function LB($k) {
		if($this->p == -1) $this->fillBuffer();
		if($k == 0) return null;
		if(($this->p-$k) < 0) return null;
		return $this->tokens[$this->p-$k];
	}
	
	public function LA($k) {
		return $this->LT($k)->type;
	}
	
	public function mark() {
		if($this->p == -1) $this->fillBuffer();
		$this->lastMarker = $this->index();
		return $this->lastMarker;
	}
	
	public function release($marker) {}
	
	public function size() {
		return $this->tokensSize;
	}
	
	public function index() {
		return $this->p;
	}
	
	public function rewind($marker=-1) {
		if($marker == -1) $marker = $this->lastMarker;
		$this->seek($marker);
	}
	
	public function seek($index) {
		$this->p = $index;
	}
	
	public function getTokenSource() {
		return $this->lexer;
	}
}