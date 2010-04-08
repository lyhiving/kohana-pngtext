<?php defined('SYSPATH') or die('No direct script access.');

Route::set('pngtext', 'pngtext')
	->defaults(array(
		'controller' => 'pngtext',
		'action'     => 'index',
	));

Route::set('pngtext.js', 'pngtext/pngtext.js')
	->defaults(array(
		'controller' => 'pngtext',
		'action'     => 'js',
	));