<?php
class AclMenuComponent extends Component {
	
	public $menuModel, $menuModelName, $tableModelName, $acf = null;
	public $actionModelName, $controller, $request, $aro_model = null;
	
	// acf - the access controling field ;)
	public $acf_adminValue = 1;
	
	public $settings, $defaultMenus = array();
	
	public $aro_id = null;
	
	
	
	
	
	
	public function getModel($modelName = null) {
		if(!isset($this->controller->{$modelName}))
			$this->controller->loadModel($modelName);
		return $this->controller->{$modelName};
	}
	
	
	
	
	// require Auth
	// optional DefaultAuth (admin check)
	
	
	
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->settings = $settings;
	}
	
	
	private function _defaults() {
		$defaults =  array(
			'acoModelName' => 'CcConfigAco',
			'menuModelName' => 'CcConfigMenu',
			'tableModelName' => 'CcConfigTable',
			'actionModelName' => 'CcConfigAction',
			'acf' => 'user_role_id',
			'acf_adminValue' => 1,
			'aro_model' => 'UserRole',
			'aro_id' => null,
			'defaultMenus' => array(
				array(
					'name' => 'Config',
					'prefix' => 'cc_config_',
					'dataSource' => 'default',
					// #ToDo: implement this (default false):
					'classPrefix' => false
					// what about class prefixes for different sources?
					// what about prefixing during ACO generation?
				),
				array(
					'name' => 'Tables',
					'dataSource' => 'default'
					// no prefix - gather all tables without prefix
				)
				// extend with further prefixed (plugin) table groups
			)
		);
		if(isset($this->controller->DefaultAuth)) {
			$defaults['acf'] = $this->controller->DefaultAuth->userRoleField;
			$defaults['acf_adminValue'] = $this->controller->DefaultAuth->userRoleAdminValue;
		}
		return $defaults;
	}
	
	
	public function isAdmin() {
		if(	(isset($this->controller->DefaultAuth)
			AND $this->controller->DefaultAuth->isAdmin())
		OR	(isset($this->controller->Auth)
			AND $this->controller->Auth->user($this->acf) == $this->acf_adminValue)
		) return true;
		return false;
	}
	
	
	public function getAcl($aro_id = null) {
		$model = $this->getModel($this->acoModelName);
		return $model->find('all', array(
			'contain' => array(
				$this->tableModelName => array(
					'conditions' => array('name' => $this->request->params['controller']),
					$this->actionModelName
				)
			),
			'conditions' => array(
				'foreign_key' => $aro_id,
				'model' => $this->aro_model
			)
		));
	}
	
	
	/*
	* don't do anything about authorisation at this stage, 
	* the component might be used to generate a menu alone
	*/
	public function initialize(Controller $controller) {
		$this->controller = $controller;
		
		$this->settings = Hash::merge($this->_defaults(), $this->settings);
		foreach($this->settings as $key => $value)
			$this->{$key} = $value;
		
		$this->request = $controller->request;
		
		if(isset($this->controller->Auth))
			if(!$this->aro_id = $this->controller->Auth->user($this->acf))
			// just to make sure the value is not (bool)false, as NULL is a valid aro_id for the public user
			$this->aro_id = null;	
	}
	
	
	/*
	* requires: DefaAuthComponent (includes AuthComponent)
	* doing ACL authorisation in this method
	*/
	public function check($aro_id = null, $aro_model = null) {
		if(empty($aro_id)) $aro_id = $this->_aro_id;
		$params = $this->controller->request->params;
		if(!empty($params['pass'])) foreach($params['pass'] as $arg) {
			$params[] = $arg;
		}
		unset($params['pass']);
		$checkPath = str_replace(
			$this->request->base, '',
			Router::url($params)
		);
		// give way for the admin
		if($this->isAdmin()) {
			return true;
		}else{
			
			// #ToDo: make a quicker check, that doesn't iterate over the entire tree - use joins
			
			// now authorize against the list! (if any)
			$acl = $this->getAcl($aro_id);
			
			if(!empty($acl)) {
				foreach($acl as $i => $menu) {
					foreach($menu[$this->tableModelName] as $t => $table) {
						if($table['name'] == $this->request->params['controller']) {
							if(!empty($table['allow_all'])) return true;
							
							if(!empty($table[$this->actionModelName])) {
								foreach($table[$this->actionModelName] as $a => $action) {
									if($action['name'] == $this->request->params['action']) {
										if(!empty($action['url'])) {
											/* Can't imagine a situation where 
											* additional parameters are allowed, 
											* but not the action without parameter...
											* AS LONG THE ACTION IS MENTIONED IN THE PATH!!!
											* But we might have a prefixed URL, like /admin routing
											*/
											if(strpos($checkPath, $action['url']) === 0) return true;
										}else{
											return true;
										}
									}
								}
								break;
							}
						}
					}
				}
			}
		}
		
		return false;
	}
	
	
	public function setMenu() {
		$cakeclientMenu = $this->getMenu();
		
		if(!$this->request->is('requested') AND Configure::read('Cakeclient.navbar')) {
			// load the AssetHelper which appends the top_nav Menu to whichever layout
			if(	!in_array('Cakeclient.Asset', $this->controller->helpers)
			AND	!isset($this->controller->helpers['Cakeclient.Asset']))
				$this->controller->helpers[] = 'Cakeclient.Asset';
		}
		
		$this->controller->set(compact('cakeclientMenu'));
	}
	
	
	public function getMenu($aro_id = null, $aro_model = null, $dataSource = null, $default = false) {
		//$menuName = $this->acf.'_'.$aro_id.'_menu';
		//$menu = Cache::read($menuName, 'cakeclient');
		if(empty($menu)) {
			
			// try reading from the cc_config_tables tables
			//$menu = $this->getAcl($aro_id);
			//$menu = $this->getAcl(2);
			
			// only if demanded or admin: get defaults if no menu available
			if($default OR (empty($menu) AND $this->isAdmin())) {
				$menu = $this->getDefaultMenu($aro_id, $aro_model, $dataSource);
			}	
			
			//Cache::write($menuName, $menu, 'cakeclient');
		}
		
		return $menu;
	}
	
	
	public function getDefaultMenu($aro_id = null, $aro_model = null, $dataSource = null, $groups = array()) {
		if(empty($aro_id)) 		$aro_id 	= $this->aro_id;
		if(empty($aro_model)) 	$aro_model 	= $this->aro_model;
		$menuModel = $this->getModel($this->menuModelName);
		$menuModel->acf_value = $this->acf_adminValue;
		$menuModel->aro_model = $this->aro_model;
		if(empty($groups)) $groups = $this->defaultMenus;
		
		return $menuModel->getDefaultMenuTree($aro_id, $aro_model, $dataSource, $groups);
	}
	
	
	
	public function getActions($action = null, $table = null, $controlled = true) {
		if(empty($action))
			$action = $this->controller->request->params['action'];
		if(empty($table))
			$table = $this->controller->request->params['controller'];
		
		$prefix = Configure::read('Cakeclient.prefix');
		if(!$prefix) $prefix = false;
		
		$modelName = $this->controller->modelClass;
		
		
		
		$model = $this->getModel($this->tableModelName);
		$currentAction = $model->find('first', array(
			'contain' => array(
				$this->actionModelName => array(
					'conditions' => array($this->actionModelName.'.name' => $action),
					'CcConfigActionsView' => array(
						'order' => 'CcConfigActionsView.position'
					)
				)
			),
			'conditions' => array($this->tableModelName.'.name' => $table)
		));
		/** The menu / context-menu templates filter for the "contextual" property.
		*	"index" views display many records and thus must check if an action belongs into a records context. 
		*	Actions that are contextual (edit, view) don't have to check for the menu-action's context, 
		*	as the context is already set by the record's ID.
		*/
		$current_action_must_check_context = true;
		if($action != 'index') $current_action_must_check_context = false;
		if(isset($currentAction[$this->actionModelName][0]['contextual']))
			$current_action_must_check_context = !$currentAction[$this->actionModelName][0]['contextual'];
		
		// check for the actions linked to the current view first, then all actions except the current one, then default list
		if(!empty($currentAction[$this->actionModelName][0]['CcConfigActionsViewsAction'])) {
			$actions = $currentAction[$this->actionModelName][0]['CcConfigActionsViewsAction'];
		}else{
			$actions = $this->controller->{$this->tableModelName}->find('first', array(
				'contain' => array(
					$this->actionModelName => array(
						'conditions' => array($this->actionModelName.'.name !=' => $action)
					)
				),
				'conditions' => array($this->tableModelName.'.name' => $table)
			));
			if(!empty($actions[$this->actionModelName])) {
				$actions = $actions[$this->actionModelName];
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
					if(isset($action['show']) AND !$action['show']) continue;
					if(!empty($action['label'])) {
						$label = $action['label'];
					}else{
						$label = Inflector::humanize(Inflector::underscore($action['name']));
					}
					$actionName = $action['name'];
					if(!empty($action['id'])) $action_id = $action['id'];
				}else{
					// mangling the default lists
					$label = Inflector::humanize(Inflector::underscore($action));
					switch($action) {
						case 'add': $label .= ' '.$modelName; break;
						case 'index': $label = 'List '.$this->virtualController; break;
					}
					$actionName = $action;
				}
				// set the route prefix to be the plugin element of the url, as this will appear in front of it all, and not named "plugin"
				$_action = array(
					'label' => $label,
					'action_id' => $action_id,
					'url' => array(
						'action' => $actionName,
						'plugin' => $prefix
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
}
?>