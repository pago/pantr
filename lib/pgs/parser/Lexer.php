<?php
namespace pgs\parser;

abstract class Lexer {
	protected $input, $token, $tokenStartCharIndex = -1, $tokenStartLine,
					$tokenStartCharPositionInLine, $type, $text;
					
	public function __construct(StringStream $input = null) {
		$this->input = $input;
	}
	
	public function reset() {
		$this->token = null;
		$this->type = Token::INVALID_TOKEN_TYPE;
		$this->tokenStartCharIndex = -1;
		$this->tokenStartCharPositionInLine = -1;
		$this->tokenStartLine = -1;
		$this->text = null;
		if($this->input != null) $input->seek(0);
	}
	
	public function nextToken() {
		while(true) {
			$this->token = null;
			$this->tokenStartCharIndex = $this->input->index();
			$this->tokenStartCharPositionInLine = $this->input->getCharPositionInLine();
			$this->tokenStartLine = $this->input->getLine();
			$this->text = null;
			if($this->input->LA(1) == StringStream::EOF) {
				return EOF_TOKEN;
			}
			$this->mTokens();
			if($this->token == null) $this->emit();
			else if($this->token == SKIP_TOKEN) continue;
			return $this->token;
		}
	}
	
	public function skip() {
		$this->token = SKIP_TOKEN;
	}
	
	public abstract function mTokens();
	
	public function setCharStream(StringStream $input) {
		$this->input = null;
		$this->reset();
		$this->input = $input;
	}
	
	public function emit($token=null) {
		if($token != null) {
			$this->token = $token;
		} else {
			$t = new Token($this->input, $this->type, $this->tokenStartCharIndex, $this->getCharIndex()-1);
			$t->line = $this->tokenStartLine;
			$t->setText($this->text);
			$t->charPositionInLine = $this->tokenStartCharPositionInLine;
			$this->token = $t;
			return $t;
		}
	}
	
	/**
	 * Pass it an associative array and it'll try to match each key
	 * until none is left or one matches.
	 * If it found a match it'll set the type of the current token to the value
	 * in the array and return true, otherwise nothing will happen and it'll
	 * return false.
	 *
	 * Note that the order of elements is highly important. The first match will
	 * stop the search (for efficiency). Thus, if you happen to have
	 * "+" before "+=" in the array, you'll be bound to never encounter the
	 * "+=" token.
	 */
	public function matchSymbols($symbols) {
		foreach($symbols as $symbol => $type) {
			if($this->match($symbol)) {
				$this->type = $type;
				return true;
			}
		}
		return false;
	}
	
	public function match($s) {
		if(is_string($s)) {
			$i = 0;
			$length = strlen($s);
			$marker = $this->input->mark();
			while($i < $length) {
				if($this->input->LA(1)!=$s[$i]) {
					$this->input->rewind($marker);
					return false;
				}
				$i++;
				$this->input->consume();
			}
			$this->input->release($marker);
		} else {
			if($this->input->LA(1) != $s) {
				return false;
			}
			$this->input->consume();
		}
		return true;
	}
	
	public function matchAny() {
		$this->input->consume();
	}
	
	public function matchRange($a, $b) {
		if($this->input->LA(1) < $a || $this->input->LA(1) > $b) {
			return false;
		}
		$this->input->consume();
		return true;
	}
	
	public function getLine() {
		return $this->input->getLine();
	}
	
	public function getCharPositionInLine() {
		return $this->input->getCharPositionInLine();
	}
	
	public function getCharIndex() {
		return $this->input->index();
	}
	
	public function getText() {
		if($this->text != null) return $this->text;
		return $this->input->substring($this->tokenStartCharIndex, $this->getCharIndex()-1);
	}
	
	public function setText($text) {
		$this->text = $text;
	}
	
	protected function LA($i) {
		return $this->input->LA($i);
	}

	protected function matchWS() {
		$i = 0;
		while($this->isWhitespace($this->input->LA(1))) {$this->input->consume(); $i++;}
		$this->skip();
		return $i;
	}
	
	protected function isWhitespace($i) {
		return $i == ' ' || $i == "\t" || $i == "\n" || $i == "\r";
	}
}
?>