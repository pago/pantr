<?php
namespace pake\ext;

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

use pake\Pake;
use pake\Task;

class PHPUnit {
	public static function task($name, $desc, $files=null, $addVerboseOption=true) {
		if(is_null($files)) {
			$files = Pake::fileset()->name('*Test.php')->in('test');
		}
		$task = Pake::task($name, $desc);
		if($addVerboseOption) {
			$task->option('verbose')
				->shorthand('v')
				->desc('Output detailed test information');
		}
		return $task->run(function($req=array()) use ($files) {
				return PHPUnit::forAllTests($files)->run(isset($req['verbose']));
			});
	}
	
	public static function forAllTests($files) {
		return new PHPUnit($files);
	}
	
	public static function forTest($name) {
		return self::forAllTests(array($name));
	}
	
	private $files;
	private $arguments = array();
	public function __construct($files) {
		$this->files = $files;
	}
	
	public function config($data) {
		$this->arguments = array_merge($this->arguments, $data);
	}
	
	public function run($verbose = false) {
		$suite = $this->getTestSuite();
		
		if($verbose) {
			$result = \PHPUnit_TextUI_TestRunner::run($suite, $this->arguments);
		} else {
			// execute the test
			ob_start();
			$listener = new AssertionTestListener();
			$runner = new TestRunner($listener);
			$result = $runner->doRun($suite, $this->arguments);
			ob_end_clean();
			
			$assertions = $listener->numAssertions;
			
			
			if($result->wasSuccessful()) {
				Pake::writeAction('unit-test', $this->formatTestResult($result, $assertions));
				return Task::SUCCESS;
			} else {
				Pake::writeAction('unit-test',
					$this->formatTestResult($result, $assertions),
					Pake::WARNING
				);
				return Task::FAILED;
			}
		}
	}
	
	private function formatTestResult(\PHPUnit_Framework_TestResult $result, $assertions) {
		$tests = $result->count();
		$errors = $result->errorCount();
		$failures = $result->failureCount();
		$skipped = $result->skippedCount();
		$incomplete = $result->notImplementedCount();
		
		$msg  = $this->formatResultPart($errors, 'error', 's');
		$msg .= $this->formatResultPart($failures, 'failure', 's');
		$msg .= $this->formatResultPart($skipped, 'skipped');
		$msg .= $this->formatResultPart($incomplete, 'incomplete');
		
		return sprintf(
			'%s (%d %s, %d %s%s)',
			($result->wasSuccessful() ? 'OK' : 'FAILED'),
			$tests, ($tests != 1 ? 'tests' : 'test'),
			$assertions, ($assertions != 1 ? 'assertions' : 'assertion'),
			$msg
		);
	}
	
	private function formatResultPart($num, $label, $pluralSuffix='') {
		if(0 < $num) {
			return ', '.$num.' '.($num != 1 ? $label . $pluralSuffix : $label);
		}
		return '';
	}
	
	private function getTestSuite() {
		$suite = new \PHPUnit_Framework_TestSuite('All tests');
		// Note: This code won't work unless the backup globals option is disabled
		// see: http://www.phpunit.de/ticket/899
		$suite->setBackupGlobals(false);
		$suite->addTestFiles($this->files);
		return $suite;
	}
}

class TestRunner extends \PHPUnit_TextUI_TestRunner {
	private $assertionListener;
	public function __construct($assertionListener) {
		$this->assertionListener = $assertionListener;
	}
	
	/**
     * @return PHPUnit_Framework_TestResult
     */
    protected function createTestResult() {
        $result = new \PHPUnit_Framework_TestResult();
		$result->addListener($this->assertionListener);
		return $result;
    }
}

class AssertionTestListener implements \PHPUnit_Framework_TestListener {
	public $numAssertions = 0;
	
	public function addError(\PHPUnit_Framework_Test $test, \Exception $e, $time) { }

	public function addFailure(\PHPUnit_Framework_Test $test,
		\PHPUnit_Framework_AssertionFailedError $e, $time) { }

	public function addIncompleteTest(\PHPUnit_Framework_Test $test, \Exception $e, $time) {}

	public function addSkippedTest(\PHPUnit_Framework_Test $test, \Exception $e, $time) {}

	public function startTest(\PHPUnit_Framework_Test $test) {}

	public function endTest(\PHPUnit_Framework_Test $test, $time) {
		if ($test instanceof \PHPUnit_Framework_TestCase) {
            $this->numAssertions += $test->getNumAssertions();
        }
	}

	public function startTestSuite(\PHPUnit_Framework_TestSuite $suite) {}

	public function endTestSuite(\PHPUnit_Framework_TestSuite $suite) {}
}