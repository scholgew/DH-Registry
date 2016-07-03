<?php
// check for an override
if(file_exists(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME))) {
	require_once(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME));
	return;
}

class CcConfigAction extends CakeclientAppModel {
	
	var $displayField = 'label';
	
	var $belongsTo = array(
		'CcConfigTable' => array(
			'className' => 'CcConfigTable',
			'foreignKey' => 'cc_config_table_id',
		)
	);
	
	var $hasMany = array(
		'CcConfigFielddefinition' => array(
			'className' => 'CcConfigFielddefinition',
			'foreignKey' => 'cc_config_action_id',
			'dependent' => true
		),
		
		// a "HasMany -hrough" HABTM Self Join
		'CcConfigActionsView' => array(
			'className' => 'CcConfigActionsView',
			'foreignKey' => 'parent_action_id',
			'dependent' => true,
			'order' => 'CcConfigActionsView.position ASC'
		)
	);
	
	/**	Do not use HABTM relations to save data, as cake does not invoke the model behaviors on the joinTable, 
	*	which is bad if the joinTable stores additional information, such as positioning.
	*	Retrieval of data is fine, however. 
	*/
	var $hasAndBelongsToMany = array(
		// the child
		'CcConfigActionsViewsAction' => array(
			'className' => 'CcConfigAction',
			'foreignKey' => 'parent_action_id',
			'associationForeignKey' => 'child_action_id',
			'joinTable' => 'cc_config_actions_views',
			'unique' => 'keepExisting'
		)
	);
	
	
	
	
	
	function getMethods($table_name = null) {
		$methods = array();
		$controllerName = Inflector::camelize($table_name) . 'Controller';
		$modelName = Inflector::classify($table_name);
		App::uses($controllerName, 'Controller');
		$$modelName = ClassRegistry::init($modelName);
		
		if(class_exists($controllerName, true)) {
			$parent = get_parent_class($controllerName);	// AppController
			$pParent = get_parent_class($parent);			// CakeCore Controller
			// we don't want the methods defined in Cake's core controller
			$pParentMethods = get_class_methods($pParent);
			$methods = get_class_methods($controllerName);
			foreach($methods as $i => $method) {
				if(strpos($method, '_') === 0) 			unset($methods[$i]);
				if(strpos($method, 'reset_order') === 0)	unset($methods[$i]);
				if(in_array($method, $pParentMethods)) 	unset($methods[$i]);
			}
		}
		
		// access the model's behaviors, if it uses Sortable, add the method "reset_order"
		if($$modelName->Behaviors->loaded('Sortable')) {
			$methods[] = 'reset_order';
		}
		
		return $methods;
	}
	
	
	function store($table_name = null) {
		if(empty($table_name)) return false;
		$table_id = $this->CcConfigTable->getTable($table_name);
		if(!empty($table_id)) {
			// $table_name needs to be string now
			$methods = $this->getMethods($table_name);
			if(!empty($methods)) {
				$stored = $this->find('all', array(
					'conditions' => array(
						'cc_config_table_id' => $table_id
					),
					'recursive' => -1
				));
				$count = count($stored) + 1;
				
				foreach($methods as $i => $method) {
					$existant = false;
					foreach($stored as $k => $record) {
						if($record['CcConfigAction']['name'] == $method) {
							$existant = true;
							break;
						}
					}
					if(!$existant) {
						// store a new record
						$context = 1;
						if(in_array($method, array('add','index'))) {
							$context = 0;
						}
						$has_form = 0;
						if(in_array($method, array('add','edit'))) {
							$has_form = 1;
						}
						$has_view = 0;
						if(in_array($method, array('add','index','edit','view'))) {
							$has_view = 1;
						}
						$bulk = 0;
						if(in_array($method, array('delete'))) {
							$bulk = 1;
						}
						$this->create();
						$this->save(array(
							'cc_config_table_id' => $table_id,
							'name' => $method,
							'label' => Inflector::humanize($method),
							'contextual' => $context,
							'has_form' => $has_form,
							'has_view' => $has_view,
							'bulk_processing' => $bulk,
							'position' => $count
						), false);
						$count ++;
					}
				}
			}
		}
	}
	
	
	function tidy($for_table = null) {
		if(empty($for_table)) return false;
		$table_id = $this->CcConfigTable->getTable($for_table);
		if(!empty($table_id)) {
			$methods = $this->getMethods($for_table);
			if(!empty($methods)) {
				$stored = $this->find('all', array(
					'conditions' => array(
						'cc_config_table_id' => $table_id
					),
					'recursive' => -1
				));
				
				foreach($stored as $k => $record) {
					$existant = false;
					foreach($methods as $i => $method) {
						if($record['CcConfigAction']['name'] == $method) {
							$existant = true;
							break;
						}
					}
					if(!$existant) {
						// remove the record
						$this->delete($record['CcConfigAction']['id'], $cascade = true);
					}
				}
			}
		}
	}
	
	/**
	* Get the action to skip for generating drop-down options for linkable actions.
	*/
	function getAction(&$action = null, $childModelName = null) {
		$action_id = null;
		if(!empty($childModelName) AND !in_array($childModelName, array($this->alias, $this->name)) AND ctype_digit($action)) {
			// the passed identifier belongs to the related model!
			$action = $this->$childModelName->find('first', array(
				'conditions' => array(
					$childModelName . '.id' => $action
				),
				'contain' => array(
					$this->alias => array('CcConfigTable')
				)
			));
			$action['CcConfigTable'] = $action[$this->alias]['CcConfigTable'];
			unset($action[$this->alias]['CcConfigTable']);
			$action_id = $action[$this->alias]['id'];
		}else{
			// the action belongs to this model
			if(ctype_digit($action)) {
				$action_id = $action;
				$this->recursive = 0;
				$action = $this->findById($action_id);
				
			}elseif(is_array($action)) {
				$action_id = $action[$this->alias]['id'];
				if(!isset($action['CcConfigTable'])) {
					$action = $this->find('first', array(
						'conditions' => array(
							$this->alias . '.id' => $action_id
						),
						'recursive' => 0
					));
				}
			}elseif(is_string($action) AND strpos($action, '.') !== false) {
				$expl = explode('.', $action);
				$table = $this->CcConfigTable->findByName($expl[0]);
				$table_id = $table['CcConfigTable']['id'];
				$action = $this->find('first', array(
					'conditions' => array(
						'CcConfigAction.cc_config_table_id' => $table_id,
						'CcConfigAction.name' => $expl[1]
					),
					'recursive' => 0
				));
				$action_id = $action['CcConfigAction']['id'];
			}
		}
		return $action_id;
	}
	
	/**
	* Overriding Crud::setOptionList().
	* Presumably called from the child model "ActionsView".
	*/
	function getHasManyOptions($childModelName = null, $action = null) {
		$action_id = $this->getAction($action, $childModelName);
		$list = array();
		if(!empty($action_id)) {
			if($childModelName == 'CcConfigActionsView') {
				$actions = $this->find('all', array(
					'recursive' => 0, // get the model's domain
					'conditions' => array(
						$this->alias . '.cc_config_table_id' => $action['CcConfigTable']['id']
					)
				));
				if(!empty($actions)) {
					foreach($actions as $k => $entry) {
						$actionLabel = $entry[$this->alias]['label'];
						$actionTableLabel = $entry['CcConfigTable']['label'];
						$list[$entry[$this->alias]['id']] = $actionTableLabel . ' ' . $actionLabel;
					}
				}
				
			}else{
				$list = $this->find('list');
			}
		}
		
		return $list;
	}
	
	
	
	/**
	* Retrieving HABTM options for hasMany-trough approach: 
	* linkable options for the action view that is being edited.
	* Called from CrudComponent during /actions/edit (self join - so child model is the model itself).
	*/
	 function getHasAndBelongsToManyOptions($childModelName = null, $action = null) {
		$apConfigViewActions = array();
		$action_id = $this->getAction($action);
		if(!empty($action_id) AND $action AND is_array($action) AND $action[$this->alias]['has_view']) {
			$apConfigViewActions = $this->find('list', array(
				'conditions' => array(
					$this->alias . '.id !=' => $action_id,
					$this->alias . '.cc_config_table_id' => $action[$this->alias]['cc_config_table_id']
				)
			));
		}
		return $apConfigViewActions;
	}
	
}
?>