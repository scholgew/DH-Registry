<?php
// check for an override
if(file_exists(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME))) {
	require_once(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME));
	return;
}

class CcConfigAco extends CakeclientAppModel {
	
	// there's an UNIQUE key on the combination (model, foreign_key)
	
	/* Virtually - yes. 
	public $belongsTo = array(
		'UserRole' => array(
			'className' => 'UserRole'
		)
	);
	*/
	
	public $hasMany = array(
		'CcConfigTable' => array(
			'className' => 'CcConfigTable'
		)
	);
	
	public $aro_model = 'UserRole';
	public $aro_value = 1;
	
	public $sources = array('default');
	
	
	
	public function createAcoTree($sources = array()) {
		if(empty($sources)) $sources = $this->sources;
		
		$data['CcConfigAco'] = $this->getDefaultAco();
		$data['CcConfigTable'] = $this->CcConfigTable->getDefaultAcoTableTree($sources);
		
		$result = $this->saveAll($data, array('validate' => false, 'deep' => true));
	}
	
	
	public function getDefaultAco($name = null) {
		if(empty($name)) $name = $this->aro_model.' '.$this->aro_value;
		return array(
			//'id',
			'name' => $name,
			'foreign_key' => $this->aro_value,
			'model' => $this->aro_model
		);
	}
	
	
}
?>