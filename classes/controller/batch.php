<?php

class Controller_Batch extends Controller {
/*	public function action_test() {
		$batch = new Batch_Test();
//		$batch->title			= ...; // title of the batch as it will appear in the view
//		$batch->redirect_delay	= ...; // how many time to wait before redirecting after the batch is finished
//		$batch->redirect_url	= ...; // where to redirect after the batch is finished
//		$batch->read_delay		= ...; // how often to poll the server for new messages
		$this->request->response  = html::script('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js');
		$this->request->response .= $batch;
	}*/
	
	/*
	 * Process as many steps of the batch as possible during current request,
	 * then returns 0 if batch is finished, 1 otherwise.
	 */
	public function action_process($id) {
		if (Request::$is_ajax && Batch::exists($id)) {
			try {
				// Retrieve batch from DB in the state we last left it :
					$batch = Batch::retrieve($id);

				// Process as many steps as possible during current request :
					if($batch->process() === Batch::FINISHED) { //
						// Batch finished. Delete batch from db.
							$batch->delete();
							$this->request->response = '0';
					}
					else {
						// Batch not finished. Store current state of batch in DB so that next time we restart execution from here.
							$batch->store();
							$this->request->response = '1';
					}
			}
			catch (Exception $e) {
				if (isset($batch)) $batch->delete();
				throw $e;
			}
		}
		else
			$this->request->status = 403;
	}

	/*
	 * Returns to client a json encoded array of server messages.
	 */
	public function action_read($id) {
		if (Request::$is_ajax) { // do NOT check if batch exists here...batch may have been deleted and some messages still wait to be read by client
			$this->request->response = json_encode(Batch::client_read($id));
			$this->request->headers['Content-type'] = 'application/json';
		}
		else
			$this->request->status = 403;
	}

	/*
	 * Stores a client message in db for given batch.
	 */
	public function action_post($id) {
		if (Request::$is_ajax) {
			if (isset($_POST['message']) && Batch::exists($id))
				Batch::client_post($id, $_POST['message']);
		}
		else
			$this->request->status = 403;
	}

	/*
	 * Media reader copied from Kodoc module. Allows batch module media files to be accessed
	 * even if they are in a htaccess-protected folder, and to be overriden using
	 * Kohana's cascade.
	 */
	public function action_media($file) {
		// Find the file extension
		$ext = pathinfo($file, PATHINFO_EXTENSION);

		// Remove the extension from the filename
		$file = substr($file, 0, -(strlen($ext) + 1));

		if ($file = Kohana::find_file('media', $file, $ext))
		{
			// Send the file content as the response
			$this->request->response = file_get_contents($file);
		}
		else
		{
			// Return a 404 status
			$this->request->status = 404;
		}

		// Set the content type for this extension
		$this->request->headers['Content-Type'] = File::mime_by_ext($ext);
	}
}