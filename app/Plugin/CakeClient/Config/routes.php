<?php
	// Provide route prefixes for the plugin as an array. It may also contain the empty string ''.
	// this cannot be defined in the database, as it's required to have it declared before everything else
	// this can be overridden in the application
	Configure::write('Cakeclient.prefixes', array('admin'));
	
	
	// read in app-specific configuration and override of the previous defaults
	$filename = APP . 'Config' . DS . 'Cakeclient' . DS . 'routes.php';
	if(file_exists($filename)) {
		include($filename);
	}
	
	
	/**	CRUD ROUTES
	*	Create a set of routes for each routing prefix. 
	*	This allows for differenciated levels of access for different kind of users (eg. admin, editor, author).
	*	Also check for Cake's routing prefixes, which eg. enable "admin_routing". 
	*	These prefixed function calls have to be routed to the CRUD actions instead, 
	*	if the corresponding Cakeclient route is set. 
	*/
	$RoutingPrefixes = Configure::read('Routing.prefixes');
	$CakeclientPrefix = Configure::read('Cakeclient.prefixes');
	if(is_array($CakeclientPrefix)) {
		foreach($CakeclientPrefix as $prefix) {
			setRoutes($prefix, $RoutingPrefixes);
		}
	}
	elseif(is_string($CakeclientPrefix)) {
		// string & empty values...
		setRoutes($CakeclientPrefix, $RoutingPrefixes);
	}
	
	
	/**	
	*	Routes are set like this:
	
	// short index URLs
	Router::connect(
		'/cakeclient/:table',
		array(
			'plugin' => 'cakeclient',
			'controller' => 'virtual',
			'action' => 'index',
			'cakeclient.route' => 'cakeclient',
			'crud' => 'index'
		)
	);
	// long URLs - only match the CRUD actions
	Router::connect(
		'/cakeclient/:table/:crud/*',
		array(
			'plugin' => 'cakeclient',
			'controller' => 'virtual',
			'cakeclient.route' => 'cakeclient'
			// one would expect the parameter 'crud' here as well, but this is set by the router itself!
		),
		array(
			'crud' => 'add|view|index|edit|delete|reset_order'
		)
	);
	Router::connect('/cakeclient/:controller/:action/*', array(
		'cakeclient' => 1,
		'cakeclient.route' => 'cakeclient'
	));
	
	// Cakeclient exceptions
	Router::connect('/cakeclient/cc_config_configurations/add/*', array(
		'plugin' => 'cakeclient',
		'controller' => 'cc_config_configurations',
		'action' => 'add',
		'cakeclient.route' => 'cakeclient',
		'cakeclient' => 1
	));
	Router::connect('/cakeclient/cc_config_actions/edit/*', array(
		'plugin' => 'cakeclient',
		'controller' => 'cc_config_actions',
		'action' => 'edit',
		'cakeclient.route' => 'cakeclient',
		'cakeclient' => 1
	));
	*/
	
	
	
	
	
	function setRoutes($prefix, $RoutingPrefixes) {
		// take care for the empty prefix
		$prefix_empty = false;
		$short_url_array = array(
			'plugin' => 'cakeclient',
			'controller' => 'virtual',
			'action' => 'index',
			$prefix => 1,
			'cakeclient.route' => $prefix,
			'crud' => 'index'
		);
		$long_url_array = array(
			'plugin' => 'cakeclient',
			'controller' => 'virtual',
			$prefix => 1,
			'cakeclient.route' => $prefix
			// one would expect the parameter 'crud' here as well, but this is set by the router itself!
		);
		// Cakeclient exceptions
		$cc_config_configurations_add = array(
			'plugin' => 'cakeclient',
			'controller' => 'cc_config_configurations',
			'action' => 'add',
			'cakeclient.route' => $prefix,
			$prefix => 1
		);
		$cc_config_actions_edit = array(
			'plugin' => 'cakeclient',
			'controller' => 'cc_config_actions',
			'action' => 'edit',
			'cakeclient.route' => $prefix,
			$prefix => 1
		);
		
		if($prefix !== '' AND is_string($prefix)) {
			$url_prefix = '/' . $prefix;
			// create routes for existing admin routes!
			if(!empty($RoutingPrefixes) AND is_array($RoutingPrefixes) AND in_array($prefix, $RoutingPrefixes)) {
				unset($short_url_array[$prefix]);
				unset($long_url_array[$prefix]);
				// exceptions
				unset($cc_config_configurations_add[$prefix]);
				unset($cc_config_actions_edit[$prefix]);
			}
			
		}else{
			// make the CRUD actions accessible without any route
			$url_prefix = '';
			$prefix_empty = true;
			unset($short_url_array[$prefix]);
			unset($long_url_array[$prefix]);
			// exceptions
			unset($cc_config_configurations_add[$prefix]);
			unset($cc_config_actions_edit[$prefix]);
		}
		
		// Cakeclient exceptions from the CRUD schema have to be defined before the CRUD routes are defined,
		// so do the internal exceptions
		Router::connect(
			$url_prefix . '/cc_config_configurations/add/*', 
			$cc_config_configurations_add
		);
		Router::connect(
			$url_prefix . '/cc_config_actions/edit/*', 
			$cc_config_actions_edit
		);
		
		
		// finally the "real" CRUD routes :)
		// short index URLs
		Router::connect(
			$url_prefix . '/:table',
			$short_url_array
		);
		// long URLs - only match the CRUD actions
		Router::connect(
			$url_prefix . '/:table/:crud/*',
			$long_url_array,
			array(
				'crud' => 'add|view|index|edit|delete|reset_order'
			)
		);
		if(!$prefix_empty) {
			// Route everything that's not CRUD to controllers outside the plugin - but stay within the prefix route to have a unified backend. 
			// Do not make a prefix routing like "prefix_action()", to have the same method available in several routes. Allow access based on prefix and user accesslevel. 
			//$authPlugin = Configure::read('Cakeclient.auth_plugin');
			Router::connect('/' . $prefix . '/users/login', array(
				'controller' => 'users',
				'action' => 'login',
				//'plugin' => $authPlugin,
				'cakeclient.route' => $prefix
			));
			Router::connect('/' . $prefix . '/:controller/:action/*', array(
				$prefix => 1,
				'cakeclient.route' => $prefix
			));
		}
	}
	
	
	
	
?>