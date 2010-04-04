<?php
namespace pantr\core\Task;

use pantr\core\TaskRepository;

class DocParser {
	private $taskRepository;
	
	public function __construct(TaskRepository $taskRepository) {
		$this->taskRepository = $taskRepository;
	}
	
	public function parseDocComment($taskName, $doc) {
		$task = $this->taskRepository[$taskName];
		$lines = explode("\n", $doc);
		// remove the "*" from the lines
		$lines = array_map(function($l) {
			return ltrim($l, "\t\0\r\n\x0B */");
		}, $lines);
		foreach($lines as $line) {
			if(!empty($line)) {
				if($line[0] == '@') {
					$this->parseAnnotation($task, substr($line, 1));
				} else {
					if($task->getDescription() == '') {
						$task->setDescription($line);
					} else {
						$task->appendDetails($line);
					}
				}
			}
		}
	}
	
	public function parseAnnotation($task, $line) {
		$info = preg_split("`\\s+`", $line, 2);
		if(count($info) == 2) {
			$line = trim($info[1]);
		}
		switch($info[0]) {
			case 'global': $task->isGlobal(true); break;
			case 'dependsOn':
				$deps = preg_split("`\\s*,\\s*`", $line);
				foreach($deps as $dep) $task->dependsOn(trim($dep));
				break;
			case 'usage': $task->usage($line); break;
			case 'needsRequest': $task->needsRequest(); break;
			case 'expectNumArgs': $task->expectNumArgs(intval($line)); break;
			case 'before': $task->before($this->taskRepository[$line]); break;
			case 'hidden': $task->isHidden(true); break;
			case 'option':
				// @option f:foo Some sample
				// @option bar Only long name
				// @option !required ...
				$required = false;
				if($line[0] == '!') {
					$required = true;
					$line = substr($line, 1);
				}
				$parts = preg_split("`\\s+`", $line, 2);
				$names = explode(':', $parts[0]);
				if(count($names) == 2) {
					$opt = $task->option($names[1])
						->shorthand($names[0])
						->desc($parts[1]);
					if($required) $opt->required();
					$task->registerOption($opt);
				} else {
					$opt = $task->option($names[0])
						->desc(isset($parts[1]) ? $parts[1] : 'n/a');
					if($required) $opt->required();
					$task->registerOption($opt);
				}
		}
	}
	
	public function parse($code) {
		if(file_exists($code)) {
			$code = file_get_contents($code);
		}
		$tokens = new TokenStream(token_get_all($code));

		while(!$tokens(1, TokenStream::EOF)) {
			$tokens->skipWS();
			if($tokens(1, T_DOC_COMMENT)) {
				$t = $tokens(1);
				// FOUND doc comment
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
							// CONFIRMED it is an invocation of the
							// task method and retrieved the
							// task name
							// strip ''
							$taskName = substr($task[1], 1, -1);
							$this->parseDocComment($taskName, $doc);
						}
					}
				}
			}
			$tokens->consume();
		}
	}
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