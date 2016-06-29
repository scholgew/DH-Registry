<?php
// check for an override
if(file_exists(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME))) {
	require_once(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME));
	return;
}

class User extends CakeclientAppModel {
	
	var $belongsTo = array(
		'UserRole' => array(
			'className' => 'UserRole'
		)
	);
	
	
}
?>