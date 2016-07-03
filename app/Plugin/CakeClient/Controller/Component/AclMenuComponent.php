<?php
class AclMenuComponent extends Component {
	
	public $menuModel, $menuModelName, $tableModelName, $acf = null;
	public $actionModelName, $controller, $request, $aro_model = null;
	
	// acf - the access controling field ;)
	public $acf_adminValue = 1;
	
	public $settings, $defaultMenus = array();
	
	protected $_aro_id = null;
	
	
	
	// require Auth
	// optional DefaultAuth (admin check)
	
	
	
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->settings = $settings;
	}
	
	
	private function _defaults() {
		$defaults =  array(
			'menuModelName' => 'CcConfigMenu',
			'tableModelName' => 'CcConfigTable',
			'actionModelName' => 'CcConfigAction',
			'acf' => 'user_role_id',
			'aro_model' => 'UserRole',
			'defaultMenus' => array(
				array(
					'name' => 'Config',
					'prefix' => 'cc_config_',
					'dataSource' => 'default'
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
		
		// #ToDo: check if a controller method exists
		
		if(	(isset($this->controller->DefaultAuth)
			AND $this->controller->DefaultAuth->isAdmin())
		OR	(isset($this->controller->Auth)
			AND $this->controller->Auth->user($this->acf) == $this->acf_adminValue)
		) return true;
		return false;
	}
	
	
	public function getAclMenu($aro_id = null) {
		return $this->menuModel->find('all', array(
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
		
		if(!isset($controller->{$this->menuModelName}))
			$controller->loadModel($this->menuModelName);
		$this->menuModel = $controller->{$this->menuModelName};
		
		$this->request = $controller->request;
		
		if(isset($this->controller->Auth))
			if(!$this->_aro_id = $this->controller->Auth->user($this->acf))
				$this->_aro_id = null;	// just to make sure the value is not (bool)false
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
			$acl = $this->getAclMenu($aro_id);
			
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
	
	
	public function getMenu($acf_value = null, $dataSource = null, $default = false) {
		$controlled = true;
		
		$menuName = $this->acf.'_'.$acf_value.'_menu';
		$menu = Cache::read($menuName, 'cakeclient');
		if(empty($menu)) {
			
			// try reading from the cc_config_tables tables
			//$menu = $this->getAclMenu($acf_value);
			//$menu = $this->getAclMenu(2);
			
			// only if demanded or admin: get defaults if no menu available
			if($default OR (empty($menu) AND $this->isAdmin())) {
				$menu = $this->getDatabaseMenu($dataSource);
			}	
			debug($menu);
			
			// basically do some cleaning...
			$_menu = array();
			foreach($menu as $k => &$item) {
				if(empty($item[$this->menuModelName]['label'])) 
					$item[$this->menuModelName]['label'] = 'Menu '.$k+1;
				
				foreach($item[$this->tableModelName] as $i => &$table) {
					$tableName = $table['name'];
					if(!empty($item['label']))
						$table['label'] = $table->makeTableLabel($tableName);
					
					
					// #ToDo: put this into the getTableDefaults() method
					/*
					// get the table's index view's actions - skip all record related and forbidden actions
					$actions = array();
					$actions = $this->getActions('index', $tableName, $controlled);
					foreach($actions as $ak => $action) {
						$contextual = false;
						if(isset($action['contextual'])) {
							$contextual = (bool) $action['contextual'];
						}
						unset($action['contextual']);
						if($contextual) {
							unset($actions[$ak]);
						}
					}
					*/
					
					
					// #ToDo: move this to view
					/*
					$menuEntry = array(
						'label' => $label,
						'url' => array(
							'action' => 'index',
							'controller' => $tableName,
							'plugin' => Configure::read('Cakeclient.prefix')
						),
						'submenu' => $actions
					);
					*/
					
					
					// build the output list
					if(!isset($menuEntry['url']) AND empty($actions)) {
						unset($item[$this->tableModelName][$i]);
					}
				}
			}
			Cache::write($menuName, $menu, 'cakeclient');
		}
		
		return $menu;
	}
	
	
	public function setMenu() {
		$cakeclientMenu = $this->getMenu($this->_aro_id);
		
		if(!$this->request->is('requested') AND Configure::read('Cakeclient.topNav')) {
			// load the AssetHelper which appends the top_nav Menu to whichever layout
			if(	!in_array('Cakeclient.Asset', $this->controller->helpers)
			AND	!isset($this->controller->helpers['Cakeclient.Asset']))
				$this->controller->helpers[] = 'Cakeclient.Asset';
		}
		
		$this->controller->set(compact('cakeclientMenu'));
	}
	
	
	public function getDatabaseMenu($dataSource = null, $groups = array()) {
		$menu = array();
		if(empty($dataSource)) $dataSource = 'default';
		
		$menuGroups = $this->defaultMenus;
		if(!empty($groups)) $menuGroups = $groups;
		$prefixes = Hash::extract($menuGroups, '{n}.prefix');
		
		foreach($menuGroups as $k => $group) {
			$source = (!empty($group['dataSource'])) ? $group['dataSource'] : $dataSource;
			App::uses('ConnectionManager', 'Model');
			$db = ConnectionManager::getDataSource($source);
			$tables = $db->listSources();
			
			$prefix = (!empty($group['prefix'])) ? $group['prefix'] : null;
			$name = (!empty($group['name'])) ? $group['name'] : 'Menu '.$k+1;
			
			$menu[$k][$this->menuModelName] = array(
				'label' => $name,
				'position' => $k+1,
				'block' => 'cakeclient_nav',
				'foreign_key' => $this->acf_adminValue,
				'model' => $this->aro_model,
			);
			
			if(!empty($tables)) foreach($tables as $i => $item) {
				$hit = false;
				if(empty($prefix)) {
					foreach($prefixes as $pr) {
						if(strpos($item, $pr) !== false) {
							$hit = true;
							break;
						}
					}
					if($hit) continue;
				}else{
					if(strpos($item, $prefix) === false) continue;
				}
				
				$menu[$k][$this->tableModelName][$i] = $this->getTableDefaults($item, $i, $prefix);
			}
		}
		
		return $menu;
	}
	
	
	public function getTableDefaults($tablename, $i = 0, $prefix = null) {
		$label = $this->makeTableLabel($tablename, $prefix);
		return array(
			//'id' => '1',
			//'cc_config_menu_id' => 1,
			'position' => $i+1,
			'name' => $tablename,
			'allow_all' => false,	// admin is allowed anyway
			'label' => $label,
			'model' => Inflector::classify($tablename),
			'controller' => $tablename,
			'displayfield' => null,
			'displayfield_label' => null,
			'show_associations' => true,
			'CcConfigAction' => array(
				array(
					//'id' => '1',
					//'cc_config_table_id' => '1',
					'position' => '1',
					'show' => true,
					// #ToDo: maintain the Cakeclient route prefix in this URL
					'url' => '/'.$tablename.'/index',
					'name' => 'index',
					'label' => 'List '.Inflector::classify($tablename),
					'comment' => null,
					'contextual' => false,
					'has_form' => false,
					'bulk_processing' => false,
					'has_view' => true
				)
			)
		);
	}
	
	
	public function makeTableLabel($tablename = null, $prefix = null) {
		$label = $tablename;
		if($prefix) $label = str_replace($prefix, '', $label);
		return $label = Inflector::humanize($label);
	}
	
	
	public function getActions($action = null, $table = null, $controlled = true) {
		if(empty($action))
			$action = $this->controller->request->params['action'];
		if(empty($table))
			$table = $this->controller->request->params['controller'];
		
		$prefix = !empty($this->controller->request->params['cakeclient.route'])
			? $this->controller->request->params['cakeclient.route'] : false;
		
		$modelName = $this->controller->modelClass;
		
		if(!isset($this->controller->{$this->tableModelName}))
			$this->controller->loadModel($this->tableModelName);
		$currentAction = $this->controller->{$this->tableModelName}->find('first', array(
			'contain' => array(
				$this->actionModelName => array(
					'conditions' => array($this->actionModelName.'.name' => $action),
					'CcConfigActionsViewsAction' => array(
						'order' => 'CcConfigActionsViewsAction.position'
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
						'conditions' => array($this->actionModelName.'.name !=' => $action),
						'order' => 'position'
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
					if(!$action['show']) continue;
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
	}
}
?>