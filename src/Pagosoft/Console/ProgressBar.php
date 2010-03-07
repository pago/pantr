<?php
namespace Pagosoft\Console;

class ProgressBar {
    private $steps = 0;
    private $step = 0;
    private $width = 70;
	private $out;

    public function __construct(Output $out, $steps=100, $width=70) {
		$this->out = $out;
        $this->steps = $steps;
        $this->step  = 0;
        $this->width = $width;
    }

	public function incProgress() {
		$this->step++;
		$this->repaint();
	}

    public function setProgress($step) {
        $this->step = $step;
        $this->repaint();
    }

    public function paint() {
        $this->out->write(' [');

        $proc = round($this->step*100/$this->steps);
        $complete = $proc.'% complete';

        $strlen = strlen(' ['.$complete.'] ');

        $max = $this->width - $strlen;

        $dash = round($max*($proc/100)+1);
        $free = $max - $dash;

        if($dash>0) echo str_repeat('#', $dash);
        if($free>0) echo str_repeat('-', $free);
        $this->out->write('] '. $complete);
    }

    public function repaint() {
        $this->rewind();
        $this->paint();
    }

    private function rewind() {
        $this->out->write("\033[1G");
    }

	public function erase() {
		$this->rewind();
		$this->out->write(str_repeat(' ', $this->width+10));
		$this->rewind();
	}
}