<?php
namespace pake\ext\Pearfarm;

use pake\Pake;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'load.php';

class Push {
	public static function push($tgzPath) {
		$push = new \Pearfarm_Task_Push();
		try {
			$push->run(array('push', $tgzPath));
		} catch(\Pearfarm_TaskArgumentException $ex) {
			Pake::writeln($ex->getMessage(), Pake::ERROR);
			Pake::log()->error($ex);
		}
	}
}