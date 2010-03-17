<?php
require_once 'src/autoload.php';
$code = <<<'EOF'
<?php
namespace pantr\file;

use pantr\pantr;
/**
 * Echos Hello World
 */
task('hello', function() {
	pantr::writeln('Hello World');
});
EOF;

$tokens = new TokenStream(token_get_all($code));

$table = array();
while(!$tokens(1, TokenStream::EOF)) {
	$tokens->skipWS();
	if($tokens(1, T_DOC_COMMENT)) {
		$t = $tokens(1);
		$doc = $t[1];
		$tokens->consume();
		// skip whitespace
		$tokens->skipWS();
		$next = $tokens(1);
		if($next[1] == 'task') {
			$tokens->consume();
			$tokens->skipWS();
			if($tokens(1) == '(') {
				$tokens->consume();
				$tokens->skipWS();
				$task = $tokens(1);
				if($tokens(1, T_CONSTANT_ENCAPSED_STRING)) {
					$taskName = $task[1];
				}
			}
		}
	}
	$tokens->consume();
}

class TokenStream {
	const EOF = -1;
	private $tokens;
	private $tokensSize;
	private $p;
	
	public function TokenStream($tokens) {
		$this->tokens = $tokens;
		$this->tokensSize = count($tokens);
		$this->p = 0;
	}
	
	public function LT($k) {
		if($k == 0) return null;
		if(($this->p+$k-1) >= $this->tokensSize) {
			return self::EOF;
		}
		return $this->tokens[$this->p+$k-1];
	}
	
	public function LA($k) {
		$token = $this->LT($k);
		return $token != self::EOF ? $token[0] : self::EOF;
	}
	
	public function consume() {
		$this->p++;
	}
	
	public function skipWS() {
		while($this->LA(1) == T_WHITESPACE) $this->consume();
	}
	
	public function __invoke() {
		$args = func_get_args();
		if(count($args) == 2) {
			return $this->LA($args[0]) == $args[1];
		}
		return $this->LT($args[0]);
	}
}