<?php
	// set some defaults
	// layout: [bool]false (don't override app-layout), CakeClient.default, any other
	Configure::write('CakeClient.layout', 'CakeClient.default');
	// may contain either string or array('element' => 'path/to/element','text' => 'footer string')
	Configure::write('CakeClient.footer', '&copy; 2016 <a href="http://hendrikschmeer.de" target="_blank">Hendrik Schmeer</a>');
	
	
	// make the plugin controllers & models available to the application
	App::build(array('Controller' => App::path('Controller', 'Cakeclient')));
	App::uses('CakeclientAppController', 'Controller');
	App::build(array('Model' => App::path('Model', 'Cakeclient')));
	App::uses('CakeclientAppModel', 'Model');
	
	
	Cache::config('cakeclient', array(
		'engine' => 'File',
		'duration' => '+999 days',
		'prefix' => 'cakeclient_'
	));
	
	// read in app-specific configuration and overrides
	$filename = APP . 'Config' . DS . 'Cakeclient' . DS . 'bootstrap.php';
	if(file_exists($filename)) {
		include($filename);
	}
?>