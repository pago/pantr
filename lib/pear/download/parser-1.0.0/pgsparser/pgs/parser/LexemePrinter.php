<?php
namespace pgs\parser;

/**
 * Utility class to print all tokens from a lexer.
 * Note that after calling this method you'll have to
 * reset the lexer if you want to use it for something else.
 */
class LexemePrinter {
	public static function printLexemes($lexer, $prefix) {
		$t = $lexer->nextToken();
		while($t != null && $t != EOF_TOKEN) {
			echo self::translateToken($prefix, $t->type) . "\n";
			$t = $lexer->nextToken();
		}
	}

	public static function translateToken($prefix, $id) {
		return call_user_func($prefix.'_translate_tokens', $id);
	}
}
