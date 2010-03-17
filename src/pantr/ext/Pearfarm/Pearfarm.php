<?php
namespace pantr\ext\Pearfarm;

use pantr\pantr;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'load.php';

class Push {
	public static function push($tgzPath) {
		$push = new \Pearfarm_Task_Push();
		try {
			$push->run(array('push', $tgzPath));
		} catch(\Pearfarm_TaskArgumentException $ex) {
			pantr::writeln($ex->getMessage(), pantr::ERROR);
			pantr::log()->error($ex);
		}
	}
}