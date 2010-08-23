// Batch states :
	var BATCH_RUNNING	= 1;
	var BATCH_SUCCESS	= 2;
	var BATCH_ERROR		= 3;
	var _batch_state;

// Recursively calls the process url until batch error or batch finished
	function _batch_process() {
		$.ajax({
			"async": true,
			"url": batch_process_url,
			"cache": false,
			"dataType": 'text',
			"timeout": 1000000000,
			"success":
				function(text){
					switch($.trim(text)) {
						case '1' : // More requests are required to complete the batch.
							_batch_process(); // Reschedule a call to process url
							break;
						case '0' : // The batch has completed and no more requests are needed.
							_batch_state = BATCH_SUCCESS;
							batch_read(_batch_messages, false); // Synchronous read to get the last messages from server before redirecting
							_batch_success();
							setTimeout("window.location = '" + batch_redirect_url + "'", batch_redirect_delay);
							break;
						default : // Should not happen...
							_batch_state = BATCH_ERROR;
							batch_read(_batch_messages, false); // Synchronous read to get the last messages from server
							_batch_error(text);
					}
				},
			"error":
				function (XMLHttpRequest) {
					_batch_state = BATCH_ERROR;
					batch_read(_batch_messages, false); // Synchronous read to get the last messages from server before redirecting
					_batch_error(XMLHttpRequest.responseText);
				}
		});
	}

// Recursively reads messages on server until batch is over :
	function _batch_read_loop(items) {
		if (_batch_state === BATCH_RUNNING)
			setTimeout("batch_read(_batch_read_loop)", batch_read_delay);
		_batch_messages(items);
	}

// Loop on messages and dispatches them one by one to _batch_message :
	function _batch_messages(items) {
		if ($.isArray(items) && items.length > 0) {
			$.each(items, function(i, item) {
				_batch_message(item);
			});
		}
	}

// Reads messages on server and calls given callback (asynchronously or synchronously)
// with results when they are available.
	function batch_read(callback, async) {
		if (async === undefined) async = true;
		$.ajax({
			"async": async,
			"url": batch_read_url,
			"cache": false,
			"dataType": 'json',
			"success": callback
		});
	}

// Post a message to server.
	function batch_post(str, async) {
		if (async === undefined) async = true;
		$.ajax({
			"async": async,
			"url": batch_post_url,
			"cache": false,
			"type": 'POST',
			"data": {"message": str}
		});
	}

$(document).ready(function(){
	_batch_state = BATCH_RUNNING;
	_batch_process();												// Init process loop
	_batch_begun();													// Signal batch has begun
	setTimeout("batch_read(_batch_read_loop)", batch_read_delay);	// Init read loop
});	