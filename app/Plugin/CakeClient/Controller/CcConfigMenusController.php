<?php

class CcConfigMenusController extends CakeclientAppController {
	
	public $components = array(
		'Cakeclient.AclMenu'
	);
	
	
	
	
	public function create($aro_model = null, $aro_id = null) {
		
		// #ToDo: set up a form or any thelike to generate menu groups
		
		$menuGroups = $this->AclMenu->defaultMenus;
		
		if(!empty($aro_model)) $this->CcConfigMenu->aro_model = $aro_model;
		if(!empty($aro_id)) $this->CcConfigMenu->aro_id = $aro_id;
		$this->CcConfigMenu->store($menuGroups, $aro_model, $aro_id);
		
		$this->redirect('index');
	}
	
	
}
?>