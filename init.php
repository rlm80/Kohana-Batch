<?php

/**
 * Add batch processing routes.
 */
Route::set('batch', 'batch/<action>(/<id>)', array(
		'action' => '(read|process|post|test)',
	))
	->defaults(array(
			'controller' => 'batch',
	));

Route::set('batch_media', 'batch/media/<file>', array(
		'file' => '.+',
	))
	->defaults(array(
		'controller' => 'batch',
		'action'     => 'media',
	));