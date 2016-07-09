<?php

class CcConfigAcosController extends CakeclientAppController {
	
	public $components = array(
		'Cakeclient.AclMenu'
	);
	
	
	
	
	public function updateTree($aro_model = null, $aro_id = null) {
		
		// #ToDo: set up a form or any thelike to set aro_model, id & name
		
		
		if(!empty($aro_model)) $this->CcConfigAco->aro_model = $aro_model;
		if(!empty($aro_id)) $this->CcConfigAco->aro_id = $aro_id;
		$this->CcConfigAco->updateTree();
		
		$this->redirect('index');
	}
	
	
}
?>