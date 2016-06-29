<?php
class CcConfigConfigurationsController extends CakeClientAppController {
	
	
	// see the special route in routes.php!
	function add($clone = null) {
		$this->CcConfigConfiguration->add($clone);
		$this->redirect('index');
	}
	
	
	/**
	* A proxy for tidy & store
	*/
	function update_tables($config_id = null) {
		$this->CcConfigConfiguration->CcConfigTable->update($config_id);
		$this->redirect('index');
	}
	
	function tidy_tables($config_id = null) {
		$this->CcConfigConfiguration->CcConfigTable->tidy($config_id);
		$this->redirect('index');
	}
	
}
?>