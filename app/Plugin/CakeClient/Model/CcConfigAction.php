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
	
	
	
	
	
	public function getDefaultAction($method = null, $tableName = null, $viewName = null, $tablePrefix = null, $urlPrefix = null, $i = 0) {
		if(empty($method) OR empty($tableName)) return array();
		
		if(empty($urlPrefix) AND $urlPrefix !== false)
			$urlPrefix = Configure::read('Cakeclient.prefix');
		$labelPrefix = null;
		if(!empty($urlPrefix)) {
			$urlPrefix = '/'.$urlPrefix;
			$labelPrefix = Inflector::classify($urlPrefix).'-';
		}else{
			$urlPrefix = null;
		}
		
		$tableLabel = $this->makeTableLabel($tableName, $tablePrefix);
		
		$contextual = 1;
		if(in_array($method, array('add','index','reset_order')))
			$contextual = 0;
		$has_form = 0;
		if(in_array($method, array('add','edit')))
			$has_form = 1;
		$has_view = 0;
		if(in_array($method, array('add','index','edit','view')))
			$has_view = 1;
		$bulk = 0;
		if(in_array($method, array('delete')))
			$bulk = 1;
		
		$label = $labelPrefix.Inflector::humanize($method);
		if($method == 'index') $label = 'List';
		if(empty($viewName) OR !in_array($viewName, array('index','menu'))) {
			switch($method) {
			case 'index': $label = $labelPrefix.'List '.$tableLabel; break;
			case 'add': case 'edit': case 'view': case 'delete':
				$label = $labelPrefix.Inflector::humanize($method).' '.Inflector::singularize($tableLabel);
				break;
			}
		}
		
		return array(
			//'id',
			//'cc_config_table_id',
			'url' => $urlPrefix.'/'.$tableName.'/'.$method,
			'name' => $method,
			'label' => $label,
			'contextual' => $contextual,
			'has_form' => $has_form,
			'has_view' => $has_view,
			'bulk_processing' => $bulk,
			'position' => $i+1,	// default positioning
			'controller_name' => null,
			'plugin_name' => null,
			'plugin_app_override' => null
		);
	}
	
	
	public function getDefaultActions($tableName = null, $viewName = null, $tablePrefix = null, $urlPrefix = null) {
		$actions = array();
		// default CRUD actions
		$methods = array('add','index','view','edit','delete');
		// access the model's behaviors, if it uses Sortable, add the method "reset_order"
		$modelName = Inflector::classify($tableName);
		$$modelName = ClassRegistry::init($modelName);
		if($$modelName->Behaviors->loaded('Sortable')) {
			$methods[] = 'reset_order';
		}
		// get the existant controller functions
		$union = $this->getMethods($tableName, $methods);
		// we have an array-format conversion here...
		
		foreach($union as $method => $method_data) {
			$action = $this->getDefaultAction($method, $tableName, $viewName, $tablePrefix, $urlPrefix);
			// special handling for the contextual property
			if(	!in_array($method, array('add','index','reset_order'))
			AND isset($method_data['contextual']))
				$action['contextual'] = $method_data['contextual'];
			unset($method_data['contextual']);
			// apply all method metadata from getMethods
			foreach($method_data as $key => $value) $action[$key] = $value;
			
			// filter out some actions for special purposes
			$add = true;
			if(!empty($viewName)) switch($viewName) {
			case 'menu':	if($action['contextual']) 						$add = false; break;
			case 'add':		if($action['contextual'] OR $method == 'add') 	$add = false; break;
			case 'view':	if($method == 'reset_order') 					$add = false; break;
			case 'edit':	if($method == 'reset_order') 					$add = false; break;
			}
			if($add) $actions[] = $action;
		}
		
		return $actions;
	}
	
	
	function getMethods($tableName = null, $defaultMethods = array()) {
		$plugin = $pluginAppOverride = false;
		$controllerMethods = array();
		$controllerName = Inflector::camelize($tableName).'Controller';
		// plugins need to extend the App::paths() array in order to be detected
		// App::build(array('Controller' => App::path('Controller', 'Plugin')));
		App::uses($controllerName, 'Controller');
		
		// determine wether it's a plugin controller
		if(class_exists($controllerName, true)) {
			$reflector = new ReflectionClass($controllerName);
			$dir = dirname($reflector->getFileName());
			$pluginName = null;
			unset($reflector);
			if(strpos($dir, 'Plugin')) {
				$plugin = true;
				$expl = explode(DS, $dir);
				foreach($expl as $k => $d) if($d == 'Plugin') $pluginName = $expl[$k+1];
				// test for an app-level override
				$_controllerName = Inflector::camelize('app_'.$tableName).'Controller';
				App::uses($_controllerName, 'Controller');
				if(class_exists($_controllerName, true)) {
					$pluginAppOverride = true;
					$controllerName = $_controllerName;
				}
			}
			
			$excludes = array('reset_order',);
			if($appExcludes = Configure::read('AclMenu.excludes'))
				$excludes = array_unique(array_merge($excludes, $appExcludes));
			Configure::write('AclMenu.excludes', $excludes);
			
			if($plugin) {
				if($pluginAppOverride) {
					$pluginController = get_parent_class($controllerName);
					$pluginAppController = get_parent_class($pluginController);
				}else{
					$pluginAppController = get_parent_class($controllerName);
				}
				$appController = get_parent_class($pluginAppController);
			}else{
				$appController = get_parent_class($controllerName);
			}
			$coreController = get_parent_class($appController);
			
			// we don't want the methods defined in Cake's core controller
			$coreControllerMethods = get_class_methods($coreController);
			$controllerMethods = get_class_methods($controllerName);
			foreach($controllerMethods as $i => $method) {
				if(	strpos($method, '_') === 0
				||	in_array($method, $excludes)
				||	in_array($method, $defaultMethods)		// cleaning against the default list
				||	(!empty($coreControllerMethods) AND in_array($method, $coreControllerMethods)) 
				) {
					unset($controllerMethods[$i]);
				}else{
					$reflector = new ReflectionMethod($controllerName, $method);
					if(!$reflector->isPublic()) unset($controllerMethods[$i]);
					unset($reflector);
				}
			}
		}
		
		// format conversion!!!
		$union = $defaultMethods + $controllerMethods;
		$out = array();
		foreach($union as $i => $method) {
			$position = $i+1;
			if(!method_exists($controllerName, $method)) {
				$out[$method] = array(
					'position' => $position,
					'controller_name' => null,
					'plugin_name' => 'Cakeclient',
					'plugin_app_override' => null
				);
			}else{
				$reflector = new ReflectionMethod($controllerName, $method);
				$params = $reflector->getParameters();
				$contextual = 0;
				foreach($params as $param) {
					$name = $param->getName();
					if(preg_match('/^id$|.+_id$/i', $name))
						$contextual = 1;
					break;	// most likely we're only interested into the first parameter
				}
				unset($reflector);
				$override = ($pluginAppOverride) ? 'yes' : 'no'; 
				if(!$plugin) $override = null;
				$out[$method] = array(
					'position' => $position,
					'controller_name' => $controllerName,
					'plugin_name' => $pluginName,
					'plugin_app_override' => $override,
					'contextual' => $contextual
				);
			}
		}
		
		return $out;
	}
	
	
	function store($tableName = null, $prefix = null) {
		if(empty($tableName)) return false;
		
		$table_id = $this->CcConfigTable->getTable($tableName);
		
		if(!empty($table_id)) {
			// $tableName needs to be string now
			$methods = $this->getDefaultActions($tableName, null, $prefix);
			if(!empty($methods)) {
				$stored = $this->find('all', array(
					'conditions' => array(
						'cc_config_table_id' => $table_id
					),
					'recursive' => -1
				));
				
				foreach($methods as $i => $method) {
					$existant = false;
					foreach($stored as $k => $record) {
						if($record['CcConfigAction']['name'] == $method['name']) {
							$existant = true;
							break;
						}
					}
					if(!$existant) {
						$method['cc_config_table_id'] = $table_id;
						$this->create();
						$this->save($method, false);
					}
				}
			}
		}
	}
	
	
	/*
	* Remove actions that have disappeared from the controller.
	*/
	function tidy($tableName = null) {
		if(empty($tableName)) return false;
		$table_id = $this->CcConfigTable->getTable($tableName);
		if(!empty($table_id)) {
			$methods = $this->getMethods($tableName);
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