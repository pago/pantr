<?php
/* 
 * Copyright (c) 2009 Patrick Gotthardt
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
namespace pake;

/**
 * Transforms a file path according to the specified pattern.
 * Example:
 * $file = fileNameTransform('config.yml', ':filename.php');
 * would yield "config.php".
 * The available placeholders correspond to the information
 * retrieved by the pathinfo-function:
 * <dl>
 *  <dt>:filename</dt>
 *  <dd>the name of the file without its extension</dd>
 *  <dt>:basename</dt>
 *  <dd>the name of the file with extension</dd>
 *  <dt>:extension</dt>
 *  <dd>the extension of the file name</dd>
 *  <dt>:dirname</dt>
 *  <dd>the path to the file</dd>
 * </dl>
 *
 * @param string $src
 * @param string $pattern
 * @return string
 */
function fileNameTransform($src, $pattern) {
	$info = pathinfo($src);
	$searches = array_map(function($p) { return ':'.$p; }, array_keys($info));
	return str_replace($searches, array_values($info), $pattern);
}