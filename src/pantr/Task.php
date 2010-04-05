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
namespace pantr;

use pgs\cli\RequestContainer;

class Task implements \ArrayAccess {
    // constants to signify the result of an executed task
    const SUCCESS = 0;
    const FAILED = 1;

    private $name, $desc, $usage, $detail;
    private $dependsOn = array();
    private $options = array();
    private $expectNumArgs = 0;
    private $needsRequest = false;
    private $run;
	private $before = array(), $after = array();
	private $properties = array();
	private $isGlobal = false;
	private $hidden = false;

    public function __construct($name, $desc='') {
        $this->name = $name;
        $this->desc = $desc;
    }

    public function printHelp() {
        // DESC
        pantr::writeln($this->name, pantr::SECTION);

        if(!is_null($this->detail)) {
            pantr::writeln($this->detail)->nl();
        } else {
            pantr::writeln($this->desc)->nl();
        }

        // USAGE
        pantr::writeln('Usage:', pantr::SECTION);
        if(!is_null($this->usage)) {
            pantr::writeln('pantr '.$this->usage);
        } else {
            // try to guess
            pantr::writeln('pantr '.$this->name);
        }

        // OPTIONS
        if(count($this->options) > 0) {
            pantr::out()->nl()->writeln('Options:', 'BOLD');
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

	public function setDescription($desc) {
		$this->desc = $desc;
		return $this;
	}

    public function getUsage() {
        return $this->usage;
    }

    public function desc($desc) {
		$this->detail = $desc;
        return $this;
    }

	public function appendDetails($desc) {
		if(empty($this->detail)) {
			$this->detail = $desc;
		} else {
			$this->detail = "\n".$desc;
		}
	}
	
	public function isGlobal($flag=null) {
		if(is_null($flag)) {
			return $this->isGlobal;
		}
		$this->isGlobal = $flag;
		return $this;
	}
	
	/**
	 * A hidden task won't show up in help
	 */
	public function isHidden($flag=null) {
		if(is_null($flag)) {
			return $this->hidden;
		}
		$this->hidden = $flag;
		return $this;
	}

    public function usage($usage) {
        $this->usage = $usage;
        return $this;
    }
	
	/**
	 * @deprecated The request is passed anyway so no need
	 * 				to invoke this method - to be removed in 1.0
	 */
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

	// for fluent API and problematic names
	public function property($name, $value=null) {
		$this->properties[$name] = $value;
		return $this;
	}

	public function offsetSet($name, $value) {
		$this->properties[$name] = $value;
	}
	
	public function offsetGet($name) {
		return $this->properties[$name];
	}
	
	public function offsetExists($name) {
		return isset($this->properties[$name]);
	}
	
	public function offsetUnset($name) {
		unset($this->properties[$name]);
	}

	/** The supplied function will be invoked before
	 *  the actual task is run. The function must have
	 *  the following signature:
	 *  fn(task: Task):boolean
	 *
	 *  If it returns <code>false</code> the task will not be executed
	 *  but unlike returning Status::FAILURE the others tasks in the row
	 *  will still be executed.
	 *
	 *  This method is to be used to conditionally enable or disable
	 *  a task and add or modify its properties.
	 *
	 *  You can specify more than one function to run before a task.
	 */
	public function before($fn) {
		$this->checkInvokableArgument($fn);
		$this->before[] = $fn;
		return $this;
	}
	
	private function checkInvokableArgument($fn) {
		if(is_null($fn)) {
			throw new \InvalidArgumentException('The argument must not be null.');
		}
		if(!is_callable($fn)) {
			throw new \InvalidArgumentException('The argument must be callable. Is: '.$fn);
		}
		if($fn instanceof Task && count($fn->getDependencies()) > 0) {
			throw new \InvalidArgumentException(
				'Supplied task ('.$fn->getName().') has dependencies.'
				.' This is currently unsupported.');
		}
	}
	
	/** The supplied function will be invoked after the task was run.
	 *  It must have the following signature:
	 *  fn(task: Task, status: Status): Status
	 *
	 *  It can be used to try to re-run a failed task
	 *  or to clean up after it.
	 */
	public function after($fn) {
		$this->checkInvokableArgument($fn);
		$this->after[] = $fn;
		return $this;
	}

    public function getDependencies() {
        return $this->dependsOn;
    }

	private function runBefore($args) {
		foreach($this->before as $fn) {
			$result = $fn($args, $this);
			if($result === false) return false;
		}
		return true;
	}
	
	private function runAfter($args, $result) {
		foreach($this->after as $fn) {
			$result = $fn($args, $this, $result);
		}
		return $result;
	}

    public function __invoke(RequestContainer $args) {
        $fn = $this->run;
        $result = Task::SUCCESS;
		foreach($this->options as $opt) {
		    $opt->registerOn($args);
		}
		$args->expectNumArgs($this->expectNumArgs);
		if($args->isValid()) {
			$this->args = $args;
			if($this->runBefore($args)) {
				if(is_callable($fn)) {
					$result = $fn($args, $this);
				} else {
					pantr::log()->notice('Invoked task '.$this->name.' is not callable.');
				}
				$result = $this->runAfter($args, $result);
			}
		} else {
		    $this->printHelp();
		}
        return $result ?: Task::SUCCESS;
    }
}