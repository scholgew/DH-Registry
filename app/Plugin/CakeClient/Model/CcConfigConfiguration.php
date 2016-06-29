<?php
// check for an override
if(file_exists(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME))) {
	require_once(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME));
	return;
}

class CcConfigConfiguration extends CakeClientAppModel {
	
	var $validate = array(
		'key' => array(
			'rule1' => array(
				'rule' => 'notEmpty',
				'message' => 'Provide a configuration key'
			),
			'rule2' => array(
				'rule' => 'notEmpty',
				'message' => 'The configuration key must be unique'
			)
		)
	);
	
	
	
	public function afterSave($created, $options) {
		Cache::delete('App.config', 'default');
	}
	
	
	function afterDelete() {
		Cache::delete('App.config', 'default');
	}
	
}
?>