<?php
class CrudComponent extends Component {
	
	/**	This component contains the relevant logic of Cakeclient's applicational scaffolding (not the same as the cake scaffolding). 
	*	It is packed into a component to have it available in other controllers as well. 
	*/
	
	
	
	/**	Default setting, which CRUD actions shall be linked from which CRUD view. 
	*	(no view for delete)
	*/
	public $indexActions = array(
		'add', 'view', 'edit', 'delete'
	);
	public $addActions = array(
		'index'
	);
	public $editActions = array(
		'index', 'view', 'delete'
	);
	public $viewActions = array(
		'index', 'edit', 'delete'
	);
	
	
	public $defaultRedirect = '/';
	
	public $referer = null;
	
	
	protected $onCrud = false;	// indicate wether the special behavior is active or not
	
	protected $modelName = null;
	
	
	function initialize(Controller $controller) {
		$this->controller = $controller;
		$this->virtualController = $this->controller->name;
		
		// we're on a special route (#ToDo: could that be checked against cakeclient.route?)
		if(!empty($this->controller->request->params['table'])) {
			// set the table name, as it is passed via the router array
			$this->virtualController = Inflector::camelize($this->controller->request->params['table']);
			$this->modelName = Inflector::singularize($this->virtualController);
			
			// #ToDo: read from CcConfigTable instead!
			
			//$tableConfigModelClass = Configure::read('Cakeclient.tables.' . $this->controller->request->params['table'] . '.modelclass');
			if(!empty($tableConfigModelClass)) {
				$this->modelName = $tableConfigModelClass;
			}
			$this->controller->uses = array($this->modelName);
			$this->controller->modelClass = $this->modelName;	// this does the trick!
			
			// map the special crud parameters to their respective keys - requests without those won't arrive in here!
			$this->controller->request->params['controller'] = $this->controller->request->params['table'];
			if(!empty($this->controller->request->params['crud'])) {
				$this->controller->request->params['action'] = $this->controller->request->params['crud'];
			}
			
			// work out the route we're at - if Cake's routing prefixes are in use, 
			// the plugin routes will not set $prefix => true in router array (params), 
			// to disable prefix routing inside the plugin, 
			// so the current route is set via a special router key: "cakeclient.route"
			$prefix = null;
			$routingPrefixes = Configure::read('Routing.prefixes');
			if(!empty($this->controller->request->params['cakeclient.route'])) {
				if(empty($routingPrefixes) OR !empty($this->controller->request->params['crud'])) {
					$prefix = $this->controller->request->params['cakeclient.route'];
				}
			}
			// make the current prefix alias available - used in the CRUD views
			Configure::write('Cakeclient.prefix', $prefix);
			$this->controller->request->params['plugin'] = $prefix;
			
			// restore the request - prerequisite for AclMenuComponent to work properly when on CRUD-route
			unset($this->controller->request->params['table']);
			unset($this->controller->request->params['crud']);
			unset($this->controller->request->params['cakeclient.route']);
			
			$this->onCrud = true;
		}
	}
	
	
	public function loadAclMenu() {
		if(!isset($this->controller->AclMenu)) {
			$this->controller->AclMenu = $this->controller->Components->load('Cakeclient.AclMenu');
			// if not loaded before beforeFilter, we need to initialize manually
			$this->controller->AclMenu->initialize($this->controller);
		}
	}
	
	
	function setCRUDenv() {
		if($this->onCrud) {
			// to make Crud views available outside the plugin, it requires setting an absolute path
			// one also needs to mention the file extension ".ctp"!
			$this->viewPath = APP . 'Plugin' . DS . 'Cakeclient' . DS . 'View' . DS . 'Crud' . DS;
			if(Configure::read('Cakeclient.layout')) $this->controller->layout = Configure::read('Cakeclient.layout');
			$this->defaultRedirect = array(
				'action' => 'index',
				'plugin' => Configure::read('Cakeclient.prefix')
			);
		}
	}
	
	
	
	function setCRUDviewVars() {
		$this->setCRUDenv();
		
		// recieve model and table names, as set in initialize
		$modelName = $this->controller->modelClass;
		$controllerName = $this->virtualController;
		$actionName = $this->controller->params['action'];
		$primaryKeyName = $this->controller->$modelName->primaryKey;
		
		$this->controller->set(compact('modelName', 'controllerName', 'primaryKeyName', 'actionName'));
		
		// set the view for CRUD actions automatically
		$this->setView();
		$this->setFieldlist();
		$this->setRelations();
		$this->controller->AclMenu->setMenu();
		$this->controller->AclMenu->setActions();
		
		// the page name
		$page_title = Configure::read('Cakeclient.page_title');
		if(empty($page_title)) {
			// the domainname (+ subdomain)
			$page_title = $this->controller->base;
		}
		// ... and a dynamic title about the context
		$title_for_layout = $this->virtualController;
		// configuration might contain a translated label for that model
		$label = Configure::read('Cakeclient.tables.' . $this->controller->request->params['controller'] . '.label');
		if(!empty($label)) {
			$title_for_layout = $label;
		}
		if($this->controller->request->params['action'] != 'index') {
			$title_for_layout = ucfirst($this->controller->request->params['action']) . ' ' . Inflector::singularize($title_for_layout);
		}
		$this->controller->set(compact('page_title', 'title_for_layout'));
	}
	
	
	
	function getTable(&$table = null) {
		if(empty($table)) {
			// get the table name to use
			$table = $this->controller->request->params['controller'];
		}
		return $table;
	}
	
	
	
	
	// set an option list for hasMany relations. list depends on model's displayField
	function setOptionList($modelName, $relatedModelName, $getRelationTypeOptions = 'getHasManyOptions') {
		$relatedModelListName = Inflector::variable(Inflector::pluralize($relatedModelName));
		if(method_exists($this->controller->$modelName->$relatedModelName, $getRelationTypeOptions)) {
			// Pass the current model id. If none is set, use pass[0] instead.
			$id = $this->controller->$modelName->id;
			if(empty($id) AND !empty($this->controller->request->params['pass'][0])) {
				$id = $this->controller->request->params['pass'][0];
			}
			$list = $this->controller->$modelName->$relatedModelName->{$getRelationTypeOptions}($modelName, $id);
		}else{
			$list = $this->controller->$modelName->$relatedModelName->find('list');
		}
		$this->controller->set($relatedModelListName, $list);
		return $list;
	}
	
	
	
	// get list of actions to display
	/*
	function getActions($action = null, $table = null, $controlled = true) {
		if(empty($action)) {
			$action = $this->controller->request->params['action'];
		}
		$table = $this->getTable($table);
		$prefix = !empty($this->controller->request->params['cakeclient.route'])
			? $this->controller->request->params['cakeclient.route']
			: false;
		$modelName = $this->controller->modelClass;
		
		$this->controller->loadModel('CcConfigTable');
		$currentAction = $this->controller->CcConfigTable->find('first', array(
			'contain' => array(
				'CcConfigAction' => array(
					'conditions' => array('CcConfigAction.name' => $action),
					'CcConfigActionsViewsAction' => array(
						'order' => 'CcConfigActionsViewsAction.position'
					)
				)
			),
			'conditions' => array('CcConfigTable.name' => $table)
		));
		/** The menu / context-menu templates filter for the "contextual" property.
		*	"index" views display many records and thus must check if an action belongs into a records context. 
		*	Actions that are contextual (edit, view) don't have to check for the menu-action's context, 
		*	as the context is already set by the record's ID.
		*
		$current_action_must_check_context = true;
		if($action != 'index') $current_action_must_check_context = false;
		if(isset($currentAction['CcConfigAction'][0]['contextual']))
			$current_action_must_check_context = !$currentAction['CcConfigAction'][0]['contextual'];
		
		// check for the actions linked to the current view first, then all actions except the current one, then default list
		if(!empty($currentAction['CcConfigAction'][0]['CcConfigActionsViewsAction'])) {
			$actions = $currentAction['CcConfigAction'][0]['CcConfigActionsViewsAction'];
		}else{
			$actions = $this->controller->CcConfigTable->find('first', array(
				'contain' => array(
					'CcConfigAction' => array(
						'conditions' => array('CcConfigAction.name !=' => $action),
						'order' => 'position'
					)
				),
				'conditions' => array('CcConfigTable.name' => $table)
			));
			if(!empty($actions['CcConfigAction'])) {
				$actions = $actions['CcConfigAction'];
			}else{
				$actions = array();
				// get the default list
				$actionsName = $action . 'Actions';
				if(isset($this->$actionsName)) {
					$actions = $this->$actionsName;
				}
				if(strtolower($action) == 'index') { 
					$tableModel = Inflector::classify($table);
					$$tableModel = ClassRegistry::init($tableModel);
					// access the model's behaviors and add a special method if Sortable is loaded
					if($$tableModel->Behaviors->loaded('Sortable')) {
						$actions[] = 'reset_order';
					}
				}
			}
		}
		
		$returnActions = array();
		if(!empty($actions)) {
			foreach($actions as $k => $action) {
				$action_id = null;
				if(is_array($action)) {
					if(!$action['show']) continue;
					if(!empty($action['label'])) {
						$title = $action['label'];
					}else{
						$title = Inflector::humanize(Inflector::underscore($action['name']));
					}
					$actionName = $action['name'];
					if(!empty($action['id'])) $action_id = $action['id'];
				}else{
					// mangling the default lists
					$title = Inflector::humanize(Inflector::underscore($action));
					switch($action) {
						case 'add': $title .= ' '.$this->modelName; break;
						case 'index': $title = 'List '.$this->virtualController; break;
					}
					$actionName = $action;
				}
				// set the route prefix to be the plugin element of the url, as this will appear in front of it all, and not named "plugin"
				$_action = array(
					'title' => $title,
					'action_id' => $action_id,
					'url' => array(
						'action' => $actionName,
						'plugin' => Configure::read('Cakeclient.prefix')
					)
				);
				if(is_array($action) AND !empty($action['controller'])) {
					$_action['url']['controller'] = $action['controller'];
				}else{
					$_action['url']['controller'] = $table;
				}
				
				// handle appending record id's or appearance in index tables
				$_action['contextual'] = $_action['append_id'] = false;
				if(!in_array($actionName, array('add', 'index', 'reset_order'))) {
					$_action['contextual'] = $_action['append_id'] = true;
				}
				if(is_array($action)) {
					$_action['contextual'] = $_action['append_id'] = (bool)$action['contextual'];
					$_action['bulk_processing'] = (bool)$action['bulk_processing'];
				}
				// if currently not in an index view, put all actions in the top menu - set contextual to false.
				if(!$current_action_must_check_context) {
					$_action['contextual'] = false;
					// note: we're still appending the id, if the action previously was contextual
				}
				
				// check wether we're on a prefix route (consider it as some kind of access control)
				$routes = Configure::read('Routing.prefixes');
				$add = true;
				$prefixed = false;
				if(!empty($routes) AND is_array($routes)) {
					foreach($routes as $route) {
						$add = true;
						$prefixed = false;
						if(strpos($actionName, $route . '_') === 0) {
							if($route !== $prefix) {
								// we're not on the route of the prefix the action has
								$add = false;
							}else{
								// remove the prefix, as this will be added via the URL prefix again
								$_action['url']['action'] = substr($actionName, strlen($route) + 1);
								$prefixed = true;
							}
						}
					}
				}
				
				// we do not read the table's controller - for simplicity, go for the AppController only
				// best would be, to set a list of accessible actions dynamically per user/group from AppController or AuthComponent as some kind of ACL
				// this is what AclMenuComponent in plugin UtilClasses does!
				$allowed = array();
				if($controlled AND !empty($this->controller->allowedActions)) {
					$allowed = $this->controller->allowedActions;
				}
				if(!empty($allowed)) {
					// get the action's cake-path - like it is done in AclMenuComponent
					$normalizedPath = $this->controller->_normalizePath($_action['url']);
					if(!isset($allowed[$normalizedPath])) {
						$add = false;
					}else{
						unset($_action['url']['base']);
					}
				}
				
				if(!empty($routes) AND !$prefixed AND !in_array($actionName, array('index', 'view', 'edit', 'add', 'delete'))) {
					// we're leaving a prefix route here, otherwise cake would not find a non-prefixed method outside the plugin - the downside of prefix routing!
					$_action['url']['plugin'] = null;
				}
				
				if($add) {
					$returnActions[$k] = $_action;
				}
			}
		}
		
		return $returnActions;
	}
	function setActions($action = null, $table = null, $controlled = true) {
		$actions = $this->getActions($action, $table, $controlled);
		$this->controller->set('crudActions', $actions);
		return $actions;
	}*/
	
	
	
	
	function getRelations($table = null, $from_model = false) {
		// params-controller will contain the virtual controller name - which in turn is the table we are looking at!
		if(empty($table)) {
			$table = $this->controller->request->params['controller'];
		}
		
		$role = 'admin'; // don't forget to enhance allowedActions checking!
		$cacheName = $role . '_relations_' . $table;
		if($from_model) $cacheName .= '_model';
		
		if(!$relations = Cache::read($cacheName, 'cakeclient')) {
			if(!isset($this->controller->CcConfigTable)) $this->controller->loadModel('CcConfigTable');
			if($table_id = $this->controller->CcConfigTable->getTable($table)) {
				$tables = Configure::read('Cakeclient.tables');
				$tableDef = false;
				$modelClass = Inflector::classify($table);
				if(!empty($tables[$table])) {
					$tableDef = $tables[$table];
					$modelClass = $tableDef['modelclass'];
				}
				if((!empty($tableDef['show_associations']) AND $tableDef['show_associations'] !== '0') OR $from_model) {
					if(!$from_model) {
						// check if there are stored relations on the db
						if(!isset($this->controller->CcConfigDisplayedrelation)) $this->controller->loadModel('CcConfigDisplayedrelation');
						$records = $this->controller->CcConfigDisplayedrelation->find('all', array(
							'conditions' => array(
								'cc_config_table_id' => $table_id,
								'visible' => true
							),
							'recursive' => -1
						));
						if(!empty($records)) {
							foreach($records as $record) {
								if(isset($record['CcConfigDisplayedrelation'])) $record = $record['CcConfigDisplayedrelation'];
								$relations[$record['type']][] = $record;
							}
						}
					}
					// if no stored relations were found, create a list of associations from the model
					if(empty($relations) OR $from_model) {
						if(!isset($this->controller->{$modelClass})) $this->controller->loadModel($modelClass);
						foreach($this->controller->{$modelClass}->associations() as $assocType) {
							$associated = $this->controller->{$modelClass}->{$assocType};
							if(!empty($associated)) {
								foreach($associated as $assoc => $association) {
									$tablename = $this->controller->{$modelClass}->{$assoc}->useTable;
									$primaryKey = $this->controller->{$modelClass}->{$assoc}->primaryKey;
									$relations[$assocType][] = array(
										'type' => $assocType,
										'label' => $assoc,
										'alias' => $assoc,
										'classname' => $association['className'],
										'foreign_key' => $association['foreignKey'],
										'tablename' => $tablename,
										'visible' => true,
										'primary_key' => $primaryKey,
										// add the ID of the related table in Cakeclient table config table, for easy subsequent lookups
										'cc_config_table_id' => $tables[$tablename]['id']
									);
								}
							}
						}
					}
				}
			}
			Cache::write($cacheName, $relations, 'cakeclient');
		}
		return $relations;
	}
	function setRelations($table = null, $from_model = false) {
		$relations = $this->getRelations($table, $from_model);
		$this->controller->set('crudRelations', $relations);
		return $relations;
	}
	
	function getFieldlist($modelName = null, $action = null) {
		if(empty($modelName)) {
			$modelName = $this->controller->modelClass;
		}
		$tableName = Inflector::tableize($modelName);
		if(empty($action)) {
			$action = $this->controller->request->params['action'];
		}
		$role = 'admin';	// add the role level to the fieldlist configuration...
		$cacheName = $role . '_fieldlist_' . $modelName . '_' . $action;
		
		if(!$fieldlist = Cache::read($cacheName, 'cakeclient')) {
			$currentAction_contains_form = false;
			if(in_array($action, array('add', 'edit'))) $currentAction_contains_form = true;
			
			$this->controller->loadModel('CcConfigTable');
			$currentAction = $this->controller->CcConfigTable->find('first', array(
				'contain' => array('CcConfigAction' => array('conditions' => array('CcConfigAction.name' => $action))),
				'conditions' => array('CcConfigTable.name' => $tableName)
			));
			if(isset($currentAction['CcConfigAction'][0]['has_form'])) {
				$currentAction_contains_form = $currentAction['CcConfigAction'][0]['has_form'];
			}
			
			$tableConfig = Configure::read('Cakeclient.tables');
			$configList = false;
			
			if(!empty($tableConfig[$tableName]['fieldlists'][$action])) {
				$fieldlist = $tableConfig[$tableName]['fieldlists'][$action];
				// do not alter a manually edited fieldlist...
				$configList = true;
				
			}else{
				// no fieldlist was specified - create one from the table description
				$columns = $this->controller->$modelName->schema();
				
				$sortable = false;
				if($this->controller->$modelName->Behaviors->loaded('Sortable')) {
					$sortable = $this->controller->$modelName->Behaviors->Sortable->settings[$modelName];
				}
				
				$fieldlist = array();
				
				foreach($columns as $fieldName => $schema) {
					// don't add the timestamp fields to forms
					if(in_array($fieldName, array('updated','modified','created')) AND $currentAction_contains_form) continue;
					
					$fielddef = array(
						'fieldname' => $modelName . '.' . $fieldName,
						'label' => Inflector::camelize($fieldName)
					);
					// by default, do not display an editable id field in forms...
					if(isset($schema['key']) AND $schema['key'] == 'primary' AND $currentAction_contains_form) {
						$fielddef['formoptions']['readonly'] = 'readonly';
					}
					if(isset($schema['comment'])) {
						$fielddef['title'] = $schema['comment'];
					}
					if($sortable AND !empty($sortable['orderBy']) AND $fieldName == $sortable['orderBy']) {
						$fielddef['display']['method'] = 'inlineForm';
					}
					if($sortable AND $sortable['orderBy'] == $fieldName) {
						if($action == 'add') {
							// Sortable::getOptions(). $parentKeys will be empty, which is bad if we want to add the new entry to a particular subtree. This calls for custom coding, or an AJAX app.
							$sortableOptions = $this->controller->$modelName->getOptions($parentKeys = array());
							$fielddef['form_options']['type'] = 'select';
							$fielddef['form_options']['options'] = $sortableOptions['addOptions'];
							$fielddef['form_options']['empty'] = false;
							if($sortableOptions['allowNull'] === true) $fielddef['form_options']['empty'] = 'null';
						}
					}
					$fieldlist[$modelName . '.' . $fieldName] = array_merge($fielddef, $schema);
				}
			}
			
			$this->__checkForeignKeys($modelName, $fieldlist, $configList, $currentAction_contains_form, $tableConfig);
			
			// normalize the fieldlist
			foreach($fieldlist as $k => $value) {
				$label = $fieldname = $k;
				$display = $displayField = null;
				$form_options = array();
				if(!empty($value['label'])) {
					$label = $value['label'];
				}
				if(!empty($value['field'])) {
					$fieldname = $value['field'];
				}
				if(!empty($value['displayField'])) {
					$displayField = $value['displayField'];
				}
				if(!empty($value['display'])) {
					$display = $value['display'];
				}
				$expl = explode('.', $fieldname);
				if(!isset($expl[1])) {
					$fieldname = $modelName . '.' . $fieldname;
				}
				if(!empty($displayField)) {
					$expl = explode('.', $displayField);
					if(!isset($expl[1])) {
						$displayField = $modelName . '.' . $displayField;
					}
				}else{
					$displayField = $fieldname;
				}
				// the fieldlist might be a one-dimensional list of "modelName.fieldname" entries, so this might have been assigned to the label 
				$expl = explode('.', $label);
				if(isset($expl[1])) {
					$label = $expl[1];
				}
				$_fieldlist[$fieldname] = array_merge($value, array(
					'field' => $fieldname,
					'displayField' => $displayField,
					'label' => $label,
					'display' => $display
				));
				if(empty($value['form_options']) OR !is_array($value['form_options'])) {
					$form_options['label'] = $_fieldlist[$fieldname]['label'];
				}else{
					$form_options = $value['form_options'];
					if(empty($form_options['label'])) {
						$form_options['label'] = $label;
					}
					if(empty($form_options['field'])) {
						$form_options['field'] = $fieldname;
					}
				}
				$_fieldlist[$fieldname]['form_options'] = $form_options;
			}
			$fieldlist = $_fieldlist;
			Cache::write($cacheName, $fieldlist, 'cakeclient');
		}
		return $fieldlist;
	}
	function setFieldlist($modelName = null, $action = null) {
		$fieldlist = $this->getFieldlist($modelName, $action);
		$this->controller->set('crudFieldlist', $fieldlist);
		return $fieldlist;
	}
	
	function __checkForeignKeys($modelName, &$fieldlist, $is_configList, $currentAction_contains_form, $tableConfig) {
		// examine related models and add fields / change definitions
		$foreignKeys = array();
		if(!empty($this->controller->$modelName->belongsTo)) {
			foreach($this->controller->$modelName->belongsTo as $modelAlias => $modelRelation) {
				if(!$is_configList) {
					// override the displayField naming in fieldlist
					$relatedTable = $this->controller->$modelName->$modelAlias->useTable;
					// relatedModelName.displayField would be even better - but is much too long!
					$label = $foreignKey = $modelRelation['foreignKey'];
					if(!empty($tableConfig[$relatedTable]['displayfield_label'])) {
						$label = $tableConfig[$relatedTable]['displayfield_label'];
					}
					// get the keys where the related values are stored
					$displayField = $this->controller->$modelName->$modelAlias->displayField;
					if(!empty($tableConfig[$relatedTable]['displayfield'])) {
						$displayField = $tableConfig[$relatedTable]['displayfield'];
					}
					// update fieldlist - do not override form_options label
					if(!is_array($fieldlist[$modelName . '.' . $foreignKey])) {
						$fieldlist[$modelName . '.' . $foreignKey] = array();
					}
					$fieldlist[$modelName . '.' . $foreignKey]['displayField'] = $modelAlias . '.' . $displayField;
					$fieldlist[$modelName . '.' . $foreignKey]['label'] = $label;
					$fieldlist[$modelName . '.' . $foreignKey]['display'] = array(
						'method' => 'link',
						'label' => $label,
						// it's important to specify the correct controller name right here, as the classname might differ from the alias found in the data array
						'url' => array(
							'controller' => $relatedTable,
							'plugin' => Configure::read('Cakeclient.prefix')
							// action & ID have to be specified somewhere else, if required - this will link to index-action by default
						)
					);
				}
				// set the option list
				if($currentAction_contains_form) {
					$this->setOptionList($modelName, $modelAlias, 'getHasManyOptions');
				}
			}
		}
		if(!empty($this->controller->$modelName->hasAndBelongsToMany)) {
			if($currentAction_contains_form) {
				foreach($this->controller->$modelName->hasAndBelongsToMany as $modelAlias => $modelRelation) {
					$this->setOptionList($modelName, $modelAlias, 'gethasAndBelongsToManyOptions');
				}
			}
		}
	}
	
	/**	A Hook for Sortable Behavior. 
	*	Manipulates the fieldlist by inspecting the data for Sortable settings. 
	*	Used by CrudComponent::edit.
	*	Pass $this->controller->viewVars['crudFieldlist'] as an argument to manipulate the fieldlist directly by reference. 
	*/
	function sortableFieldlist(&$fieldlist = array(), $data = array(), $modelName = null, $action = null) {
		if(empty($modelName)) $modelName = $this->controller->modelClass;
		
		$sortable = false;
		if($this->controller->$modelName->Behaviors->loaded('Sortable'))
			$sortable = $this->controller->$modelName->Behaviors->Sortable->settings[$modelName];
		
		if(empty($data) AND !empty($this->controller->request->data[$modelName])) 
			$data = $this->controller->request->data[$modelName];
		
		if(empty($fieldlist) AND !empty($this->controller->viewVars['crudFieldlist']))
			$fieldlist = $this->controller->viewVars['crudFieldlist'];
		if(empty($fieldlist)) $fieldlist = $this->getFieldlist($modelName, $action);
		
		if(empty($data)) return $fieldlist;
		
		if($sortable AND !empty($data['SortableOptions'])) {
			if(empty($fieldlist[$modelName . '.' . $sortable['orderBy']]['form_options']['type']))
				$fieldlist[$modelName . '.' . $sortable['orderBy']]['form_options']['type'] = 'select';
			if(empty($fieldlist[$modelName . '.' . $sortable['orderBy']]['form_options']['options']))
				$fieldlist[$modelName . '.' . $sortable['orderBy']]['form_options']['options'] = $data['SortableOptions']['editOptions'];
			if(!array_key_exists('empty', $fieldlist[$modelName . '.' . $sortable['orderBy']]['form_options'])) {
				$fieldlist[$modelName . '.' . $sortable['orderBy']]['form_options']['empty'] = false;
				if($data['SortableOptions']['allowNull'] === true) {
					$fieldlist[$modelName . '.' . $sortable['orderBy']]['form_options']['empty'] = 'null';
				}
			}
		}
		
		return $fieldlist;
	}
	
	
	
	function setView($action = null) {
		if(empty($action)) {
			$action = $this->controller->params['action'];
		}
		$view = null;
		switch($action) {
			case 'add':
			case 'edit':
				$view = 'form';
				break;
			case 'index':
			case 'view':
				$view = $action;
				break;
		}
		// don't set a view if non-CRUD
		if(!empty($view) AND !empty($this->controller->request->params['controller'])) {
			// check for an override view
			if(	is_file(APP . 'View' . DS . $this->virtualController . DS . $view . '.ctp')
			OR	is_file(APP . 'View' . DS . $this->virtualController . DS . $action . '.ctp')
			) {
				$this->controller->view = APP . 'View' . DS . $this->virtualController . DS . $view . '.ctp';
				// use the action-named view over a view called "form"
				if($view != $action AND is_file(APP . 'View' . DS . $this->virtualController . DS . $action . '.ctp')) {
					$this->controller->view = APP . 'View' . DS . $this->virtualController . DS . $action . '.ctp';
				}
			}else{
				$this->controller->view = $this->viewPath . $view . '.ctp';
			}
		}
	}
	
	
	
	
	function setLogin() {
		$config = Configure::read('Cakeclient.login');
		if(isset($config['simple']) AND $config['simple'] === 'true' AND isset($this->controller->Auth)) {
			$controller = $this->controller->Auth->settings['loginAction']['controller'];
			$action = $this->controller->Auth->settings['loginAction']['action'];
			if($this->controller->Auth->loggedIn()) {
				$action = 'logout';
			}
			$login_info = array(
				'controller' => $controller,
				'action' => $action,
				'title' => ucfirst($action)
			);
			if(!isset($config['hide']) OR $config['hide'] === 'false') {
				$this->controller->set(compact('login_info'));
			}
			elseif($this->controller->Auth->loggedIn()) {
				$this->controller->set(compact('login_info'));
			}
		}
		elseif(isset($config['element'])) {
			// deliver the element as well as the hide status
			$login_info = $config;
			$this->controller->set(compact('login_info'));
		}
	}



	/**
	* These functions set the referer:
	* where to redirect to after any CRUD action (except for the "R").
	*/
	function setReferer($default = '/') {
		if(empty($this->referer)) {
			$this->referer = $this->getCleanReferer($default);
			$this->controller->Session->write('Cakeclient.referer', $this->referer);
		}
		return $this->referer;
	}
	
	function getReferer($default = '/') {
		if(empty($this->referer)) {
			$this->referer = $this->controller->Session->read('Cakeclient.referer');
		}
		if(empty($this->referer)) {
			$this->referer = $this->getCleanReferer($default);
		}
		$this->controller->Session->delete('Cakeclient.referer');
		return $this->referer;
	}
	
	function getCleanReferer($default = '/') {
		$referer = str_replace(
			getInstallationSubDir(), '',
			$this->controller->referer($default, $restrict_local = true)
		);
		if(empty($referer)) $referer = $default;
		return $referer;
	}
	
	
	
	
	
	function bulkProcessor($redirect = true, $method = null, $items = array()) {
		$data = (!empty($this->controller->request->data['BulkProcessor'])) ? 
			$this->controller->request->data['BulkProcessor'] : array();
		$return = true;
		if(empty($method) AND !empty($data['action'])) $method = $data['action'];
		if(empty($items) AND !empty($data['items'])) $items = json_decode($data['items']);
		if(!empty($method) AND !empty($items)) {
			// if the methods list was generated from the default CRUD action list, method will be string. Numeric if from database
			if(ctype_digit($method)) {
				$this->controller->loadModel('CcConfigAction');
				$action = $this->controller->CcConfigAction->find('first', array(
					'contain' => array(),
					'conditions' => array('CcConfigAction.id' => $method)
				));
				if(!empty($action['CcConfigAction'])) $method = $action['CcConfigAction']['name'];
			}
			/** Check if method exists in custom controller
			*	Check for an unique property in ModelsController to make sure we're not on a CRUD path, 
			*	thus not invoking the 'delete' method on the CakeclientAppController again!
			*/
			if(empty($this->controller->crudController) AND method_exists($this->controller, $method)) {
				$return = $this->controller->{$method}($items, $redirect = false);
			}elseif($method == 'delete') {
				$return = $this->delete($items, $redirect = false);
			}
		}
		if($redirect) $this->controller->redirect($this->getReferer($this->defaultRedirect));
		return $return;
	}
	
	function index() {
		$relations = false;
		$conditions = $order = array();
		$modelName = $this->controller->modelClass;
		
		if(!empty($this->controller->request->data['BulkProcessor'])) $this->bulkProcessor($redirect = false);
		
		if(!empty($this->controller->request->params['named'])) {
			$columns = $this->controller->$modelName->schema();
			foreach($this->controller->request->params['named'] as $namedField => $namedValue) {
				if(in_array(strtolower($namedField), array('sort','direction'))) continue;
				// if a named parameter is present, check if it is a valid fieldname
				if(isset($columns[$namedField])) $conditions[$modelName . '.' . $namedField] = $namedValue;
			}
			if(!empty($this->controller->request->params['named']['sort'])) {
				$this->controller->$modelName->Behaviors->disable('Sortable');
			}
		}
		// tell the model which CRUD method is working
		$this->controller->{$modelName}->crud = 'index';
		
		$this->controller->Paginator->settings = $this->controller->paginate;
		try{
			$data = $this->controller->paginate($modelName, $conditions);
		}catch(NotFoundException $e) {
			// catching out-of-paging range exceptions, ie. when on the last page, a filter is being set
			$this->controller->redirect('index');
		}
		$this->controller->set(Inflector::variable($this->virtualController) . 'List', $data);
		$this->controller->set('filter', $conditions);
	}
	
	function add() {
		$modelName = $this->controller->modelClass;
		if(!empty($this->controller->request->data[$modelName])) {
			$this->controller->{$modelName}->crud = 'add';
			if($this->controller->$modelName->save($this->controller->request->data)) {
				$this->controller->redirect($this->getReferer($this->defaultRedirect));
			}
		}else{
			$this->setReferer($this->defaultRedirect);
		}
	}
	
	function edit($id = null) {
		if(empty($id)) {
			$this->controller->redirect($this->getReferer($this->defaultRedirect));
		}
		$modelName = $this->controller->modelClass;
		if(!empty($this->controller->request->data[$modelName])) {
			$this->controller->$modelName->crud = 'edit';
			$this->controller->$modelName->id = $id;
			$this->controller->request->data[$modelName]['id'] = $id;
			if($result = $this->controller->$modelName->save($this->controller->request->data)) {
				$this->controller->redirect($this->getReferer($this->defaultRedirect));
			}
		}else{
			$this->setReferer($this->defaultRedirect);
			$this->controller->request->data = $this->controller->$modelName->read(null, $id);
		}
		
		// pass Sortable settings to the fieldlist
		$this->sortableFieldlist($this->controller->viewVars['crudFieldlist']);
	}
	
	function view($id = null) {
		if(empty($id)) $this->controller->redirect($this->getReferer($this->defaultRedirect));
		
		$modelName = $this->controller->modelClass;
		$this->controller->$modelName->crud = 'view';
		$record = $this->controller->$modelName->read(null, $id);
		
		$this->controller->set(compact('record'));
	}
	
	function delete($id = null, $redirect = true) {
		$return = true;
		if(!empty($id)) {
			$modelName = $this->controller->modelClass;
			$this->controller->$modelName->crud = 'delete';
			if(is_array($id)) {
				foreach($id as $item) {
					if(!$return = $this->controller->$modelName->delete($item, true)) break;
				}
			}else{
				$return = $this->controller->$modelName->delete($id, true);
			}
		}
		if($redirect) $this->controller->redirect($this->getReferer($this->defaultRedirect));
		return $return;
	}
	
	function reset_order($redirect = true) {
		$modelName = $this->controller->modelClass;
		if(isset($this->controller->$modelName->Behaviors->Sortable)) {
			$this->controller->$modelName->crud = 'reset_order';
			$data = $this->controller->$modelName->find('all');
			$this->controller->$modelName->fixOrder($data);
		}
		if($redirect) $this->controller->redirect($this->getReferer($this->defaultRedirect));
	}
	
	
	
	
	
	
	
	function updateFieldlistDefinitions($for_table = null, $for_context = null) {
		// only admit a per-table, per-context policy
		if(empty($for_table) OR empty($for_context)) return;
		if($tables = Configure::read('Cakeclient.tables')) {
			// get the right tabledef from config
			foreach($tables as $tablename => $tabledefinition) {
				if($tablename == $for_table) {
					$fieldlistModelClass = CAKECLIENT_FIELDLISTCONFIGURATION_MODEL;
					$this->controller->loadModel($fieldlistModelClass);
					$storedFieldlists = $this->controller->{$fieldlistModelClass}->find('all', array(
						'conditions' => array(
							Inflector::singularize(CAKECLIENT_TABLECONFIGURATION_TABLE) . '_id' => $tabledefinition['id'],
							'context' => $for_context
						),
						'recursive' => -1
					));
					// within tabledefinition & context fieldnames are unique - amend the missing ones
					if(!isset($this->controller->{$tabledefinition['modelclass']})) {
						$this->controller->loadModel($tabledefinition['modelclass']);
					}
					$schema = $this->controller->{$tabledefinition['modelclass']}->getColumnTypes();
					
					$i = 1;
					foreach($schema as $fieldName => $type) {
						// do not display an id field in forms...
						if(in_array($fieldName, array('created','modified')) AND in_array($for_context, array('index','add','edit'))) {
							continue;
						}
						if(in_array($fieldName, array('id')) AND in_array($for_context, array('add','edit'))) {
							continue;
						}
						if(in_array($fieldName, array('position')) AND in_array($for_context, array('view','index'))) {
							continue;
						}
						if(in_array($fieldName, array(
							'description','header_element','footer_element',
							'logo_left_image','logo_left_url','logo_right_image','logo_right_url'
						)) AND in_array($for_context, array('index'))) {
							continue;
						}
						$fieldlist = array(
							'name' => $tabledefinition['modelclass'] . '.' . $fieldName,
							'context' => $for_context,
							Inflector::singularize(CAKECLIENT_TABLECONFIGURATION_TABLE) . '_id' => $tabledefinition['id'],
							'position' => $i,
							'fieldname' => $fieldName,
							'label' => Inflector::humanize($fieldName),
							'display_method' => 'auto'
						);
						$this->controller->{$fieldlistModelClass}->create();
						$this->controller->{$fieldlistModelClass}->save($fieldlist, false);
						$this->controller->{$fieldlistModelClass}->{CAKECLIENT_TABLECONFIGURATION_MODEL}->{CAKECLIENT_CONFIGURATION_MODEL}->save(array(
							'id' => $tabledefinition[Inflector::singularize(CAKECLIENT_CONFIGURATION_TABLE) . '_id'],
							'modified' => date("Y-m-d H:i:s")
						), false);
						$i ++;
					}
					
					break;
				}
			}
		}
	}
}
?>