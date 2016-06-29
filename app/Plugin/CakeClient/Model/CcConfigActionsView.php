<?php
// check for an override
if(file_exists(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME))) {
	require_once(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME));
	return;
}

class CcConfigActionsView extends CakeclientAppModel {
	
	var $actsAs = array(
		'Utils.Sortable' => array(
			'parentId' => 'parent_action_id'
		)
	);
	
	var $belongsTo = array(
		'ParentAction' => array(
			'className' => 'CcConfigAction',
			'foreignKey' => 'parent_action_id',
		),
		'ChildAction' => array(
			'className' => 'CcConfigAction',
			'foreignKey' => 'child_action_id',
		)
	);
	
	
	
	function afterFind($results = array(), $primary = false) {
		// classVar $crud is set to false by default in CakeclientAppModel
		// and manipulated by the CRUD methods for differentiation. 
		if($primary AND in_array($this->crud, array('index', 'view'))) {
			// we're enhancing the actions' names with the corresponding table name for better readability
			$p_id = $c_id = false;
			foreach($results as $k => $result) {
				if(!isset($result['ChildAction']) OR !isset($result['ParentAction'])) break;
				$pid = $result['ParentAction']['id'];
				$cid = $result['ChildAction']['id'];
				if($p_id != $pid) {
					$p_id = $pid;
					$parent = $this->ParentAction->find('first', array(
						'contain' => array(
							'CcConfigTable'
						),
						'conditions' => array('ParentAction.id' => $p_id)
					));
				}
				if($c_id != $cid) {
					$c_id = $cid;
					$child = $this->ChildAction->find('first', array(
						'contain' => array(
							'CcConfigTable'
						),
						'conditions' => array('ChildAction.id' => $c_id)
					));
				}
				// enhance the action name with the corresponding table
				if(!empty($parent['CcConfigTable'])) $results[$k]['ParentAction']['label'] = $parent['CcConfigTable']['label'] . ' ' . $results[$k]['ParentAction']['label'];
				if(!empty($child['CcConfigTable'])) $results[$k]['ChildAction']['label'] = $child['CcConfigTable']['label'] . ' ' . $results[$k]['ChildAction']['label'];
			}
		}
		return $results;
	}
}
?>