<?php
class CrudComponent extends Component {
	
	/**	This component contains the relevant logic of Cakeclient's applicational scaffolding (not the same as the cake scaffolding). 
	*	It is packed into a component to have it available in other controllers as well. 
	*/
	
	
	
	
	
	public $defaultRedirect = '/';
	
	public $referer = null;
	
	
	protected $table = null;				// ! the table name retrieved from request params
	
	protected $onCrud = false;				// indicate wether the special behavior is active or not
	
	protected $virtualController = null;	// the controller name derived from table name - for variable & path names
	
	protected $modelName = null;			// the derived model - overridable by config
	
	
	
	
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->settings = Hash::merge($this->_defaults(), $settings);
		foreach($this->settings as $key => $value)
			$this->{$key} = $value;
	}
	
	
	private function _defaults() {
		return array(
			'menuModelName' => 'CcConfigMenu',
			'tableModelName' => 'CcConfigTable',
			'actionModelName' => 'CcConfigAction'
		);
	}
	
	
	public function getModel($modelName = null) {
		if(empty($modelName)) return null;
		if(!isset($this->controller->{$modelName}))
			$this->controller->loadModel($modelName);
		return $this->controller->{$modelName};
	}
	
	
	function initialize(Controller $controller) {
		$this->controller = $controller;
		$this->table = $this->controller->request->params['controller'];
		$this->virtualController = $controller->name;
		
		// we're on a special route (#ToDo: could that be checked against cakeclient.route?)
		if(!empty($this->controller->request->params['table'])) {
			// set the table name, as it is passed via the router array
			$this->table = $this->controller->request->params['table'];
			$this->virtualController = Inflector::camelize($this->table);
			$this->modelName = Inflector::singularize($this->virtualController);
			
			// if we're using a plugin model and the model has an app-level override, use it
			$this->controller->modelClass = $this->modelName;
			// avoi loading the modelClass, as it could be located outside the plugin, thus we need a dummy model
			$virtual = $plugin = false;
			if(method_exists($this->controller->Dummy, 'getAppClass'))
				$this->modelName = $this->controller->Dummy->getAppClass($this->modelName, 'Model', $virtual, $plugin);
			
			// check if there's an override
			if($tableConfig = $this->getTableConfig($this->table))
				if(!empty($tableConfig[$this->tableModelName]['model']))
					$this->modelName = $tableConfig[$this->tableModelName]['model'];
			/*
			* Overridden the way Cake loads models in CakeclientAppController:
			* if the requested model is existant and resides inside App, put a prefix on it to explicitly
			* suppress plugin prefixing.
			* Otherwise the original loadModel() method would put a 'Cakeclient' in front of it. 
			*/
			$loadModel = $this->modelName;
			if(!$plugin AND !$virtual) $loadModel = 'App.'.$this->modelName;
			$this->controller->loadModel($loadModel);	// see override in CakeclientAppController!
			
			$this->controller->uses = array($this->modelName);
			$this->controller->modelClass = $this->modelName;
			
			// map the special crud parameters to their respective keys - requests without those won't arrive in here!
			$this->controller->request->params['controller'] = $this->table;
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
	
	
	public function getTableConfig($table = null) {
		$model = $this->getModel($this->tableModelName);
		
		// #ToDo: get the right table entry that belongs to the action that was being called!
		// acf_value, Model to get the right menu...
		
		if($tableConfig = $model->find('first', array(
			'contain' => array(),
			'conditions' => array($this->tableModelName.'.name' => $table)
		))) return $tableConfig['CcConfigTable'];
		return array();
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
	
	
	// set an option list for hasMany relations. list depends on model's displayField
	function setOptionList($modelName, $relatedModelName, $getFunction = 'getOptions') {
		if(empty($modelName))
			$modelName = $this->modelName;
		$relatedModelListName = Inflector::variable(Inflector::pluralize($relatedModelName));
		if(method_exists($this->controller->{$modelName}->{$relatedModelName}, $getFunction)) {
			// Pass the current model id. If none is set, use pass[0] instead.
			$id = $this->controller->{$modelName}->id;
			if(empty($id) AND !empty($this->controller->request->params['pass'][0])) {
				$id = $this->controller->request->params['pass'][0];
			}
			$list = $this->controller->{$modelName}->{$relatedModelName}->{$getFunction}($modelName, $id);
		}else{
			$list = $this->controller->{$modelName}->{$relatedModelName}->find('list');
		}
		$this->controller->set($relatedModelListName, $list);
		return $list;
	}
	
	
	public function getRelations($tableName = null, $modelName = null) {
		if(empty($tableName)) $tableName = $this->table;
		if(empty($modelName)) $modelName = $this->modelName;
		
		$role = 'admin'; // don't forget to enhance allowedActions checking!
		$cacheName = $role . '_relations_' . $tableName;
		if(!$relations = Cache::read($cacheName, 'cakeclient')) {
			
			$tableDef = $this->getTableConfig($tableName);
			// Yet we do not really need this
			//if($tableDef = $this->getTableConfig($tableName)) {
			if(0) {
				//$table_id = $tableModel->getTable($tableName);
				$table_id = $tableDef['id'];
				$modelName = $tableDef['model'];
				
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
				}
			}
			
			// if no stored relations were found, create a list of associations from the model
			if(empty($relations)) {
				$model = $this->getModel($modelName);
				// check if the model is virtual and examine fieldlist
				$virtual = $plugin = false;
				$dummy = $this->getModel('Dummy');
				if(method_exists($dummy, 'getAppClass'))
					$dummy->getAppClass($this->modelName, 'Model', $virtual, $plugin);
				if($virtual) {
					// detect belongsTo relations
					$columns = $model->schema();
					$i = 0;
					foreach($columns as $fieldName => $schema) {
						if(preg_match('/.+_id$/', $fieldName)) {
							$assoc = Inflector::classify(substr($fieldName, 0, -3));
							$relations['belongsTo'][] = array(
								'position' => $i+1,
								'type' => 'belongsTo',
								'label' => $assoc,
								'classname' => $assoc,
								'foreign_key' => $fieldName,
								'tablename' => Inflector::pluralize(substr($fieldName, 0, -3)),
								'visible' => true,
								'primary_key' => 'id'
							);
							$i++;
						}
					}
				}else{
					$associations = $model->associations();
					foreach($associations as $assocType) {
						$associated = $model->{$assocType};
						if(!empty($associated)) {
							$i = 0;
							foreach($associated as $assoc => $association) {
								$tablename = $model->{$assoc}->useTable;
								$primaryKey = $model->{$assoc}->primaryKey;
								$relations[$assocType][] = array(
									'position' => $i+1,
									'type' => $assocType,
									'label' => $assoc,
									'classname' => $association['className'],
									'foreign_key' => $association['foreignKey'],
									'tablename' => $tablename,
									'visible' => true,
									'primary_key' => $primaryKey,
									// add the ID of the related table in Cakeclient table config table, for easy subsequent lookups
									//'cc_config_table_id' => $tableDef['id']
								);
								$i++;
							}
						}
					}
				}
			}
			
			Cache::write($cacheName, $relations, 'cakeclient');
		}
		return $relations;
	}
	public function setRelations($tableName = null, $modelName = null) {
		$relations = $this->getRelations($tableName, $modelName);
		$this->controller->set('crudRelations', $relations);
		return $relations;
	}
	
	public function getFieldlist($modelName = null, $action = null, $table = null) {
		if(empty($modelName)) 	$modelName = $this->modelName;
		if(empty($table)) 		$tableName = $this->table;
		if(empty($action)) 		$action = $this->controller->request->params['action'];
		
		$role = 'admin';	// add the role level to the fieldlist configuration...
		$cacheName = $role . '_fieldlist_' . $modelName . '_' . $action;
		if(!$fieldlist = Cache::read($cacheName, 'cakeclient')) {
			
			$has_form = false;
			if(in_array($action, array('add', 'edit'))) $has_form = true;
			
			$tableModel = $this->getModel($this->tableModelName);
			
			// #ToDo: get ACO!
			$currentAction = $tableModel->find('first', array(
				'contain' => array('CcConfigAction' => array('conditions' => array('CcConfigAction.name' => $action))),
				'conditions' => array('CcConfigTable.name' => $tableName)
			));
			if(isset($currentAction['CcConfigAction'][0]['has_form'])) {
				$has_form = $currentAction['CcConfigAction'][0]['has_form'];
			}
			
			$configList = false;
			//$tableConfig = Configure::read('Cakeclient.tables');
			$tableConfig = $this->getTableConfig($tableName);
			// #ToDo: get the configuration of related tables
			if(0) {	
			//if(!empty($tableConfig['fieldlists'][$action])) {
				$fieldlist = $tableConfig['fieldlists'][$action];
				// do not alter a manually edited fieldlist...
				$configList = true;
				
			}else{
				// no fieldlist was specified - create one from the table description
				$model = $this->getModel($modelName);
				
				$sortable = false;
				if($model->Behaviors->loaded('Sortable')) {
					$sortable = $model->Behaviors->Sortable->settings[$modelName];
				}
				
				$columns = $model->schema();
				$fieldlist = array();
				foreach($columns as $fieldName => $schema) {
					// don't add the timestamp fields to forms
					if(in_array($fieldName, array('updated','modified','created')) AND $has_form) continue;
					
					$fielddef = array(
						'fieldname' => $modelName . '.' . $fieldName,
						'label' => Inflector::camelize($fieldName)
					);
					// by default, do not display an editable id field in forms...
					if(isset($schema['key']) AND $schema['key'] == 'primary' AND $has_form) {
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
							$fielddef['form_options']['field'] = $fielddef['fieldname'];
							$fielddef['form_options']['label'] = $fielddef['label'];
							$fielddef['form_options']['type'] = 'select';
							$fielddef['form_options']['empty'] = false;
							// Sortable::getOptions(). $parentKeys will be empty, which is bad if we want to add 
							// the new entry to a particular subtree. This calls for custom coding, or an AJAX app.
							$sortableOptions = $this->controller->$modelName->getOptions($parentKeys = array());
							$fielddef['form_options']['options'] = $sortableOptions['addOptions'];
							if($sortableOptions['allowNull'] === true) $fielddef['form_options']['empty'] = 'null';
						}
					}
					$fieldlist[$modelName . '.' . $fieldName] = array_merge($fielddef, $schema);
				}
			}
			
			$this->__inspectAssociations($modelName, $fieldlist, $configList, $has_form, $tableConfig);
			debug($fieldlist);
			Cache::write($cacheName, $fieldlist, 'cakeclient');
		}
		return $fieldlist;
	}
	public function setFieldlist($modelName = null, $action = null, $tableName = null) {
		$fieldlist = $this->getFieldlist($modelName, $action, $tableName);
		$this->controller->set('crudFieldlist', $fieldlist);
		return $fieldlist;
	}
	
	
	private function __setModelRelations($modelName = null, $reset = true) {
		if(empty($modelName)) $modelName = $this->modelName;
		$model = $this->getModel($modelName);
		$belongsTo = $model->belongsTo;
		if(empty($belongsTo)) {
			// check if the model is virtual and examine fieldlist
			$virtual = $plugin = false;
			$dummy = $this->getModel('Dummy');
			if(method_exists($dummy, 'getAppClass'))
				$dummy->getAppClass($this->modelName, 'Model', $virtual, $plugin);
			if($virtual) {
				// detect belongsTo relations
				$columns = $model->schema();
				foreach($columns as $fieldName => $schema) {
					if(preg_match('/.+_id$/', $fieldName)) {
						$assoc = Inflector::classify(substr($fieldName, 0, -3));
						$belongsTo[$assoc] = array(
							'className' => $assoc,
							'foreignKey' => $fieldName,
							//'tablename' => Inflector::pluralize(substr($fieldName, 0, -3))
						);
					}
				}
				if(!empty($belongsTo))
					$model->bindModel(array('belongsTo' => $belongsTo), $reset);
			}
		}
		return $belongsTo;
	}
	
	
	// destroys the column label
	private function __inspectAssociations($modelName, &$fieldlist, $is_configList, $has_form, $tableConfig) {
		// examine related models and add fields / change definitions
		$model = $this->getModel($modelName);
		$belongsTo = $this->__setModelRelations($modelName, $reset = true);
		
		if(!empty($belongsTo)) {
			foreach($model->belongsTo as $modelAlias => $modelRelation) {
				if(!$is_configList) {
					// override the displayField naming in fieldlist
					$relatedTable = $model->{$modelAlias}->useTable;
					// relatedModelName.displayField would be even better - but is much too long!
					$foreignKey = $modelRelation['foreignKey'];
					$label = Inflector::camelize($modelRelation['foreignKey']);
					if(!empty($tableConfig[$relatedTable]['displayfield_label'])) {
						$label = $tableConfig[$relatedTable]['displayfield_label'];
					}
					// get the keys where the related values are stored
					$displayField = $model->{$modelAlias}->displayField;
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
				if($has_form) {
					$this->setOptionList($modelName, $modelAlias, 'getOptions');
				}
			}
		}
		if(!empty($this->controller->$modelName->hasAndBelongsToMany)) {
			if($has_form) {
				foreach($this->controller->$modelName->hasAndBelongsToMany as $modelAlias => $modelRelation) {
					$this->setOptionList($modelName, $modelAlias, 'getHabtmOptions');
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
			*	Check for an unique property in VirtualController to make sure we're not on a CRUD path, 
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
		
		// result filter
		if(!empty($named = $this->controller->request->params['named'])) {
			// do some sanitization, prevent SQL injection on the filter keys - Cake takes care of escaping the filter values
			$namedKeys = preg_replace('/[^a-zA-Z0-9_-]/', '', array_keys($named));
			$columns = $this->controller->{$modelName}->schema();
			foreach($namedKeys as $namedField) {
				if(!isset($named[$namedField])) continue;
				// don't pull in the pagination sort keys
				if(in_array(strtolower($namedField), array('sort','direction'))) continue;
				// if a named parameter is present, check if it is a valid fieldname
				if(isset($columns[$namedField]))
					$conditions[$modelName . '.' . $namedField] = $named[$namedField];
			}
			if(!empty($this->controller->request->params['named']['sort'])) {
				$this->controller->{$modelName}->Behaviors->disable('Sortable');
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