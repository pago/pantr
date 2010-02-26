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
namespace pake;

use pgs\cli\RequestContainer;

class Task {
    // constants to signify the result of an executed task
    const SUCCESS = 0;
    const FAILED = 1;

    private $name, $desc, $usage, $detail;
    private $dependsOn = array();
    private $options = array();
    private $expectNumArgs = 0;
    private $needsRequest = false;
    private $run;

    public function __construct($name, $desc='') {
        $this->name = $name;
        $this->desc = $desc;
    }

    public function printHelp() {
        // DESC
        Pake::writeln($this->name, Pake::SECTION);

        if(!is_null($this->detail)) {
            Pake::writeln($this->detail)->nl();
        } else {
            Pake::writeln($this->desc)->nl();
        }

        // USAGE
        Pake::writeln('Usage:', Pake::SECTION);
        if(!is_null($this->usage)) {
            Pake::writeln('pake '.$this->usage);
        } else {
            // try to guess
            Pake::writeln('pake '.$this->name);
        }

        // OPTIONS
        if(count($this->options) > 0) {
            Pake::nl()->writeln('Options:', 'BOLD');
            foreach($this->options as $opt) {
                $opt->printHelp();
            }
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->desc;
    }

    public function getUsage() {
        return $this->usage;
    }

    public function desc($desc) {
        $this->detail = $desc;
        return $this;
    }

    public function usage($usage) {
        $this->usage = $usage;
        return $this;
    }

    public function needsRequest() {
        $this->needsRequest = true;
        return $this;
    }

    public function dependsOn() {
        $this->dependsOn = array_merge($this->dependsOn, func_get_args());
        return $this;
    }

    public function option($long) {
        $opt = new Option($this, $long);
        return $opt;
    }

    public function registerOption($opt) {
        $this->options[] = $opt;
    }

    public function expectNumArgs($num) {
        $this->expectNumArgs = $num;
        return $this;
    }

    public function run($fn) {
        $this->run = $fn;
        return $this;
    }

    public function getDependencies() {
        return $this->dependsOn;
    }

    public function __invoke(RequestContainer $args) {
        $fn = $this->run;
        $result = Task::SUCCESS;
        if(count($this->options) > 0 || $this->expectNumArgs > 0 || $this->needsRequest) {
            foreach($this->options as $opt) {
                $opt->registerOn($args);
            }
            $args->expectNumArgs($this->expectNumArgs);
            if($args->isValid()) {
                $result = $fn($args);
            } else {
                $this->printHelp();
            }
        } else {
            $result = $fn();
        }
        return $result ?: Task::SUCCESS;
    }
}