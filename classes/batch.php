<?php
abstract class Batch {
	/*
	 * Check if a batch exists in the database.
	 */
	public static function exists($id) {
		$results = DB::select('obj')->from('batches')->where('id','=',$id)->execute();
		return (count($results) > 0);
	}

	/*
	 * Retrieves a batch object previously stored.
	 */
	public static function retrieve($id) {
		$results = DB::select('obj')->from('batches')->where('id','=',$id)->execute();
		return unserialize($results[0]['obj']);
	}

	/*
	 * Returns array of messages posted by server to client and deletes them from db.
	 * Messages are returned in the same format they were posted in.
	 */
	public static function client_read($id) {
		$results	= DB::select()->from('batch_messages')->where('batch_id','=',$id)->where('source','=','server')->order_by('id')->execute();
		$messages	= $results->as_array('id','obj');
		if (count($messages) > 0)
			DB::delete('batch_messages')->where('id','IN',array_keys($messages))->execute();
		return array_map('unserialize', array_values($messages));
	}

	/*
	 * Insert new message posted by client to server into db.
	 */
	public static function client_post($id, $str) {
		DB::insert('batch_messages', array('source','obj','batch_id'))
			->values(array('client', $str, $id))
			->execute();
	}

	/******************* END OF PUBLIC STATIC INTERFACE ***********************/

	// Private properties
	private $id;
	private $op = 'start';
	private $args = array();

	// Protected : may be redefined
	protected $view = 'batch';

	// Public : accessible from view and may be redefined or set
	public $title;
	public $redirect_delay;
	public $redirect_url;
	public $read_delay;

	public function __construct(){
		// Find free id :
			$this->id = md5(uniqid(mt_rand(), true));
			while ((count(DB::select()->from('batches')->where('id','=',$this->id)->execute()) > 0))
				$this->id = md5(uniqid(mt_rand(), true));

		// Init with default values :
			$this->title			= __('Batch processing');
			$this->redirect_delay	= 2000;
			$this->read_delay		= 1000;
			$this->redirect_url		= empty(Request::$referrer) ? url::site() : Request::$referrer;
	}

	/*
	 * Executes as many batch operations as possible during the current request,
	 * then returns Batch::FINISHED if the batch is finished, Batch::UNFINISHED otherwise.
	 */
	const FINISHED		= 1;
	const UNFINISHED	= 2;
	public function process() {
		// Start timer :
			$start_time	= microtime(TRUE);

		// Execute as many batch operations as possible :
			while(isset($this->op) && $this->keepgoing(microtime(TRUE) - $start_time)) {
				// Execute current operation :
					if (isset($this->args)) {
						$method	= new ReflectionMethod(get_class($this), $this->op);
						$next	= $method->invokeArgs($this, $this->args);
					}
					else
						$next = $this->{$this->op}();

				// Set next operation :
					if (is_array($next)) {
						$this->op	= $next[0];
						$this->args	= $next[1];
					}
					else {
						$this->op	= $next;
						$this->args	= null;
					}
			}

		return isset($this->op) ? self::UNFINISHED : self::FINISHED;
	}

	/*
	 * Do we still have time to do something ?
	 */
	protected function keepgoing($elapsed) {
		return $elapsed < (ini_get("max_execution_time") / 2);
	}

	/*
	 * Posts a piece of data to the client. $data may be anything, it will
	 * be json_encoded, sent to the client, and json_decoded there.
	 */
	protected function post($data) {
		DB::insert('batch_messages', array('source', 'obj', 'batch_id'))
			->values(array('server', serialize($data), $this->id))
			->execute();
	}

	/*
	 * Posts a piece of data to the client. $data may be anything, it will
	 * be json_encoded, sent to the client, and json_decoded there.
	 */
	protected function read() {
		$results	= DB::select()->from('batch_messages')->where('batch_id','=',$this->id)->where('source','=','client')->order_by('id')->execute();
		$messages	= $results->as_array('id','obj');
		if (count($messages) > 0)
			DB::delete('batch_messages')->where('id','IN',array_keys($messages))->execute();
		return array_values($messages);
	}	

	/*
	 * Utility function to post the current time, a message and a progress
	 * percentage to the client in the form of an object.
	 */
	protected function message($message, $percentage = NULL) {
		$obj = new StdClass();
		$obj->created		= date("Y-m-d H:i:s");
		$obj->message		= $message;
		$obj->percentage	= $percentage;
		$this->post($obj);
	}

	/*
	 * Stores current state of batch.
	 */
	public function store() {
		if(count(DB::select()->from('batches')->where('id','=',$this->id)->execute()) == 0)
			DB::insert('batches', array('id', 'obj'))
				->values(array($this->id, serialize($this)))
				->execute();
		else
			DB::update('batches')
				->set(array('obj' => serialize($this)))
				->where('id','=',$this->id)
				->execute();
	}

	/*
	 * Deletes batch from db. Also deletes remaining messages from client to server
	 * since they won't ever get read anyway.
	 */
	public function delete() {
		DB::delete('batches')->where('id', '=', $this->id)->execute();
		DB::delete('batch_messages')->where('batch_id','=',$this->id)->where('source','=','client')->execute();
	}

	/*
	 * Returns the html/js to be included in the page from which the
	 * batch will execute and store current batch for processing later.
	 */
	public function render() {
		$this->store(); // Store batch in DB for the first time.
		return View::factory($this->view)
					->set('batch', $this)
					->set('process_url', url::site(Route::get('batch')->uri(array('action' => 'process', 'id' => $this->id))))
					->set('read_url', url::site(Route::get('batch')->uri(array('action' => 'read', 'id' => $this->id))))
					->set('post_url', url::site(Route::get('batch')->uri(array('action' => 'post', 'id' => $this->id))))
					->render();
	}

	public function __toString() {
		try	{
			return $this->render();
		}
		catch (Exception $e) {
			return Kohana::exception_text($e);
		}
	}

	/*
	 * The first batch operation that will be processed.
	 * You must redefine this.
	 */
	abstract protected function start();
}