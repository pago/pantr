<?php
namespace pgs\parser;

define("EOF_TOKEN", new Token(Token::EOF));
define("INVALID_TOKEN", new Token(Token::INVALID_TOKEN_TYPE));
define("SKIP_TOKEN", new Token(Token::INVALID_TOKEN_TYPE));

class Token {
	const EOF = StringStream::EOF;
	const INVALID_TOKEN_TYPE = 0;
	
	public $type, $line, $charPositionInLine=-1, $start, $stop, $tokenIndex, $payload;
	private $text, $input;
	
	public function __construct($typeOrInput, $typeOrText=-1, $start=-1, $stop=-1) {
		// oh man, method overloading in PHP sucks badly
		if(is_int($typeOrInput)) {
			$this->type = $typeOrInput;
			if($typeOrText != -1) {
				$this->text = $typeOrText;
			}
		} elseif($typeOrInput instanceof Token) {
			$this->text = $typeOrInput->text;
			$this->type = $typeOrInput->type;
			$this->line = $typeOrInput->line;
			$this->index = $typeOrInput->index;
			$this->charPositionInLine = $typeOrInput->charPositionInLine;
		} else {
			$this->input = $typeOrInput;
			$this->type = $typeOrText;
			$this->start = $start;
			$this->stop = $stop;
		}
	}
	
	public function getText() {
		if($this->text != null) return $this->text;
		if($this->input == null) return null;
		$this->text = $this->input->substring($this->start, $this->stop);
		return $this->text;
	}
	
	public function setText($text) {
		$this->text = $text;
	}
	
	public function __toString() {
		$text = $this->getText();
		if($text != null) {
			$text = str_replace(array("\n", "\r", "\t"), array("\\\\n", "\\\\r", "\\\\t"), $text);
		}
		return sprintf('[@%d,%d:%d="%s",<%d>,%d:%d]', $this->tokenIndex, $this->start, $this->stop, $text,
						$this->type, $this->line, $this->charPositionInLine);
	}
	
	/**
	 * Utility method for generating an interface using the given id parameter and the
	 * suffix "Tokens" as well as a $id."_translate_tokens"-Function that can
	 * translate the type of a token to its real name.
	 *
	 * Typical usage is:
	 * <pre><code>eval(Token::build_tokens('lisp_', 'ID LPAREN RPAREN'));</code></pre>
	 * This would create the interface lisp_Tokens with constants ID, LPAREN and RPAREN
	 * as well as lisp__translate_tokens.
	 *
	 * Your Lexer and Parser should implement the interface (thereby gaining the
	 * constants) so you can create more robust and cleaner code.
	 * The function is used for error recognition and emiting human readable
	 * and consumable error messages.
	 */
	public static function build_tokens($id, $tokens) {
		$tokens = explode(' ', $tokens);
		$limit = count($tokens);
		for($i = 0; $i < $limit; $i++) $tokens[$i] = trim(strtoupper($tokens[$i]));
		// build the interface
		$return = 'interface '.$id."Tokens {\n";
		$i = 4;
		foreach($tokens as $t) {
			$return .= "\tconst ".$t.' = '.$i.";\n";
			$i++;
		}
		$return .= "}\n\n";
		
		// build the function
		$return .= 'function '.$id.'_translate_tokens($id) {'."\n";
		$return .= '	switch($id) {'."\n";
		$return .= '		case -1: return "EOF";'."\n";
		$return .= '		case 0: return "INVALID";'."\n";
		$i = 4;
		foreach($tokens as $t) {
			$return .= '		case '.$i.': return "'.$t."\";\n";
			$i++; 
		}
		$return .= '		default: return "UNKNOWN";'."\n";
		$return .= "\t}\n";
		$return .= "}\n";
		
		return $return;
	}
}