<?php
namespace pake\ext;

use pake\Pake;
use pake\Task;

class File extends Task {
	private $src, $target;
	
	public function run($fn) {
		$src = $this->src;
		$target = $this->target;
		parent::run(function() use ($src, $target, $fn) {
			if(!file_exists($target) || filemtime($target) < filemtime($src)) {
				$fn($src, $target);
			}
		});
        return $this;
    }

	public function withContent($fn) {
		return $this->run(function($src, $target) use ($fn) {
			$content = file_get_contents($src);
			$content = $fn($content);
			file_put_contents($target, $content);
		});
	}
	
	public static function task($name, $file, $spec) {
		$out = \pake\fileNameTransform($file, $spec);
		$task = new File($name, 'create file '.basename($out).' if it does not exist');
		$task->src = $file;
		$task->target = $out;
		Pake::getTaskRepository()->registerTask($task);
		return $task;
	}
}