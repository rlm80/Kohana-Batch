<?php
/*
 * This batch will execute 10 times the mystep method.
 */
class Batch_Test extends Batch {
	protected $total_steps = 10;

	// protected $view = 'mybatchview'; // that is what I would do if I wish my batch to use a non default view

	function start() {
		return array('mystep', array(1)); // next step is $this->mystep() with param 1
	}

	function mystep($step){
		// Check if a cancel message has been recieved :
			if(count($this->read()) > 0)
				return 'abort'; // next step is $this->abort()

		// Spend some time executing this step :
			sleep(2);
		
		// Send message to client to tell it current step is done :
			$this->message("step " . $step . " done", $step / $this->total_steps * 100);

		// If we still have steps to do, reschedule a step.
		// Otherwise return null to signal batch is finished.
			return ($step < $this->total_steps) ? array('mystep', array($step + 1)) : null;
	}

	function abort() {
		$this->message("Batch aborted by user.", 99);
		return null; // no next step
	}
}