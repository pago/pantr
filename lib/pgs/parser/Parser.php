<?php
namespace pgs\parser;

class Parser {
	protected $input, $grammarName, $fileName;
	
	public function __construct(TokenStream $input, $grammarName, $fileName='') {
		$this->input = $input;
		$this->grammarName = $grammarName;
		$this->fileName = $fileName;
	}
	
	public function consume() {$this->input->consume();}
	public function LA($k) {return $this->input->LA($k);}
	public function LT($k) {return $this->input->LT($k);}
	public function LB($k) {return $this->input->LB($k);}
	
	public function match($i) {
		$token = $this->input->LT(1);
		if($token->type == $i) {
			$this->input->consume();
			return $token;
		}
		if(is_string($token)) {
				exit('token is string: '.$token);
		}
		$this->tokenMismatch($token, $i);
		return false;
	}
	
	public function jumpOver($id) {
		if($this->input->LA(1) == $id) $this->input->consume();
	}
	
	public function tokenMismatch(Token $found, $expected) {
		throw new RecognitionException(sprintf(
			"line %d:%d mismatched input '%s' expecting '%s'",
			$found->line, $found->charPositionInLine, $this->translateToken($found->type),
			$this->translateToken($expected)
		), $this->fileName);
	}
	
	public function noViableAlternative(Token $token, $alternatives=array()) {
		$msg = sprintf("line %d:%d no viable alternative at input '%s'",
			$found->line, $found->charPositionInLine, $this->translateToken($found->type));
		if(count($alternatives) > 0) {
			$msg .= ', expected one of ';
			foreach($alternatives as $a) {
				$msg .= $this->translateToken($a) . ',';
			}
			$msg = substr($msg, 0, strlen($msg)-1);
		}
		throw new RecognitionException($msg, $this->fileName);
	}
	
	public function translateToken($id) {
		return call_user_func($this->grammarName.'_translate_tokens', $id);
	}
}
?>