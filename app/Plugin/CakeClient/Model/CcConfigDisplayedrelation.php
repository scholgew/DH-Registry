<?php
// check for an override
if(file_exists(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME))) {
	require_once(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME));
	return;
}

class CcConfigDisplayedrelation extends CakeClientAppModel {
	
	var $actsAs = array(
		'Utils.Sortable' => array(
			'parentId' => 'cc_config_table_id'
		)
	);
	
	var $belongsTo = array(
		'CcConfigTable' => array(
			'className' => 'CcConfigTable',
			'foreignKey' => 'cc_config_table_id'
		)
	);
	
	
	
	/**
	* Input: the relations array of the model being updated
	*/
	function store($model_relations = array()) {
		if(!empty($model_relations)) {
			foreach($model_relations as $key => $set) {
				if(!isset($set['CcConfigDisplayedrelation'])) {
					$set = array('CcConfigDisplayedrelation' => $set);
				}
				$count = $this->find('count', array(
					'conditions' => array(
						'cc_config_table_id' => $set['CcConfigDisplayedrelation']['cc_config_table_id'],
						'classname' => $set['CcConfigDisplayedrelation']['classname'],
						'type' => $set['CcConfigDisplayedrelation']['type'],
						'tablename' => $set['CcConfigDisplayedrelation']['tablename'],
						'foreign_key' => $set['CcConfigDisplayedrelation']['foreign_key']
					)
				));
				if(!$count) {
					$this->create();
					$this->save($set, false);
				}
			}
		}
	}
	
	function tidy($model_relations = array(), $table = null) {
		$table_id = $this->CcConfigTable->getTable($table);
		if(!empty($model_relations) AND $table_id) {
			$stored = $this->find('first', array(
				'conditions' => array('cc_config_table_id' => $table_id)
			));
			// check for all relations found in the table, if there's a corresponding entry in the model
			foreach($model_relations as $key => $set) {
				if(isset($set['CcConfigDisplayedrelation'])) {
					$set = $set['CcConfigDisplayedrelation'];
				}
				foreach($stored as $key => $record) {
					if(isset($record['CcConfigDisplayedrelation'])) {
						$record = $record['CcConfigDisplayedrelation'];
					}
					if(	$set['classname'] == $record['classname']
					AND	$set['type'] == $record['type']
					AND	$set['tablename'] == $record['tablename']
					AND	$set['foreign_key'] == $record['foreign_key']
					) {
						unset($stored[$key]);
						break;
					}
				}
			}
			if(!empty($stored)) {
				// delete those that are still on the stored array
				foreach($stored as $key => $record) {
					if(isset($record['CcConfigDisplayedrelation'])) {
						$record = $record['CcConfigDisplayedrelation'];
					}
					$this->delete($record['id']);
				}
			}
		}elseif($table_id) {
			// associations found in model: none - remove all entries
			$this->deleteAll(array('cc_config_table_id' => $table_id));
		}
	}
	
}
?>