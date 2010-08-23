<?php echo html::script(Route::get('batch_media')->uri(array('file' => 'js/batch.js'))) ?>

<script type="text/javascript">
	// Parameters controlling the behavior of this batch :
		var batch_process_url		= '<?php echo $process_url ?>';
		var batch_read_url			= '<?php echo $read_url ?>';
		var batch_post_url			= '<?php echo $post_url ?>';
		var batch_redirect_url		= '<?php echo $batch->redirect_url ?>';
		var batch_redirect_delay	=  <?php echo $batch->redirect_delay ?>;
		var batch_read_delay		=  <?php echo $batch->read_delay ?>;

	// Called once when batch process has begun :
		function _batch_begun() {
			show_message("<?php echo __("Batch started...") ?>");
		}

	// Called each time we recieve a new message from server :
		function _batch_message(item) {
			show_message(item.created + ' ' + item.message + ' - ' + item.percentage + '%');
		}

	// Called once when batch process successfully finished :
		function _batch_success() {
			show_message("<?php echo __("...batch finished successfully.") ?>");
		}

	// Called once when batch process finishes on error :
		function _batch_error(error) {
			show_message("<?php echo __("Error occurred, batch processing interrupted.") ?>");
			show_error(error);
		}

	// Utility functions :
		function show_message(str)	{ $("#batch_messages").append("<li>" + str + "</li>");		}
		function show_error(html)	{ $("#batch_errors").append($('<span>').text(html).html());	}
</script>

<h2><?php echo html::chars($batch->title) ?></h2>
<button id="batch_cancel" onclick="batch_post('cancel');">Cancel</button>
<ol id="batch_messages" style="list-style-type: none"></ol>
<pre id="batch_errors" style="color: red"></pre>