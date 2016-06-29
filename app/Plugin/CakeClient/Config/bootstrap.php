<?php
	
	// make the plugin controllers & models available to the application
	App::build(array('Controller' => App::path('Controller', 'CakeClient')));
	App::uses('CakeClientAppController', 'Controller');
	App::build(array('Model' => App::path('Model', 'CakeClient')));
	App::uses('CakeClientAppModel', 'Model');
	
	
	Cache::config('cakeclient', array(
		'engine' => 'File',
		'duration' => '+999 days',
		'prefix' => 'cakeclient_'
	));
	
	
?>