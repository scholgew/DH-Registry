<?php
class CakeclientAppModel extends AppModel {
	
	var $recursive = 0;
	
	// CrudComponent sets this property to the name of the CRUD method in effect
	// to indicate that generic Crud code handles the request
	var $crud = false;
	
	var $actsAs = array(
		'Cakeclient.Configurable',
		'Containable'
	);
	
	function afterSave($created, $options = array()) {
		// move this to affected models later on.
		Cache::clear($check_expiry = false, 'cakeclient');
	}
	
}
?>