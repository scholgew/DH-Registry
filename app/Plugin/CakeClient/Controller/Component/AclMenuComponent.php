<?php
class AclMenuComponent extends Component {
	
	public $menuModel = null;
	
	public $menuModelName = 'CcConfigMenu';
	public $tableModelName = 'CcConfigTable';
	public $actionModelName = 'CcConfigAction';
	
	public $controller = null;
	
	public $request = null;
	
	// the Access Controling Field
	public $acf = 'user_role_id';
	
	public $aro_model = 'UserRole';
	
	public $configPrefix = 'cc_config_';
	
	
	protected $_aro_id = null;
	
	
	
	
	
	/*
	* don't do anything about authorisation at this stage, 
	* the component might be used to generate a menu alone
	*/
	public function initialize(Controller $controller) {
		$this->controller = $controller;
		if(!isset($controller->{$this->menuModelName}))
			$controller->loadModel($this->menuModelName);
		
		$this->menuModel = $controller->{$this->menuModelName};
		$this->request = $controller->request;
		
		if(isset($this->controller->Auth))
			$this->_aro_id = $this->controller->Auth->user($this->acf);
	}
	
	
	/*
	* requires: DefaAuthComponent (includes AuthComponent)
	* doing ACL authorisation in this method
	*/
	public function check($aro_id = null, $aro_model = null) {
		if(empty($aro_id)) $aro_id = $this->_aro_id;
		
		// give way for the admin
		if(	isset($this->controller->DefaultAuth)
		AND	$this->controller->DefaultAuth->isAdmin()
		) {
			return true;
		}else{
			// now authorize against the list! (if any)
			$acl = $this->menuModel->find('first', array(
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
			if(!empty($acl)) {
				foreach($acl[$this->tableModelName] as $t => $table) {
					if($table['name'] == $this->request->params['controller']) {
						if(!empty($table['allow_all'])) return true;
						
						if(!empty($entry[$this->actionModelName])) {
							foreach($entry[$this->actionModelName] as $a => $action)
								if($action['name'] == $this->request->params['action'])
									return true;
							break;
						}
					}
				}
			}
		}
		
		return false;
	}
	
	
	function getMenu($controlled = true, $dataSource = null, $cc_config = false) {
		if(empty($dataSource)) $dataSource = 'default';
		
		$menuName = $this->acf.'_'.$this->_aro_id.'_menu';
		$menu = Cache::read($menuName, 'cakeclient');
		if(empty($menu)) {
			
			// #ToDo: try reading from the cc_config_tables table before
			
			if(empty($tables)) {
				// get all table names - that would do for linking all index pages
				App::uses('ConnectionManager', 'Model');
				$db = ConnectionManager::getDataSource($dataSource);
				$tables = $db->listSources();
			}
			
			// enhance with index actionlist and countercheck with access lists
			$_menu = array();
			foreach($tables as $k => $item) {
				$cc_config = false;
				$tablename = $item;
				if(is_array($tablename)) {
					$tablename = $item['name'];
				}
				// check for the plugin's internal configuration tables
				if(	(strpos($tablename, $this->configPrefix) !== false AND !$cc_config)
				OR	(strpos($tablename, $this->configPrefix) === false AND $cc_config)	
				) {
					continue;
				}
				
				$title = $tablename;
				if($cc_config) $title = str_replace($this->configPrefix, '', $title);
				$title = Inflector::humanize($tittle);
				if(is_array($item) AND !empty($item['label'])) $title = $item['label'];
				
				// get the allowed actions
				$allowed = array();
				if($controlled AND !empty($this->controller->Auth->allowedActions)) {
					$allowed = $this->controller->Auth->allowedActions;
				}
				
				// get the table's index view's actions - skip all record related and forbidden actions
				$actions = array();
				$actions = $this->getActions('index', $tablename, $controlled);
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
				
				$menuEntry = array(
					'title' => $title,
					'url' => array(
						'action' => 'index',
						'controller' => $tablename,
						'plugin' => Configure::read('Cakeclient.prefix')
					),
					'submenu' => $actions
				);
				// url base is for the router only to create the correct path - has to be unset afterwards
				$menuEntry['url']['base'] = false;
				if(method_exists($this->controller, '_normalizePath'))
					$normalizedPath = $this->controller->_normalizePath($menuEntry['url']);
				if(!empty($allowed) AND !isset($allowed[$normalizedPath])) {
					unset($menuEntry['url']);
				}else{
					unset($menuEntry['url']['base']);
				}
				if(isset($menuEntry['url']) OR !empty($actions)) {
					$_menu[] = $menuEntry;
				}
			}
			$menu['list'] = $_menu;
			$menu['label'] = ($cc_config)? 'Configuration' : 'Tables';
			Cache::write($menuName, $menu, 'cakeclient');
		}
		return $menu;
	}
	
	
	function setMenu($role_id = null) {
		$cakeclientMenu = $this->getMenu($controlled = true, null, $cc_config = false);
		$cakeclientConfigMenu = $this->getMenu($controlled = true, null, $cc_config = true);
		
		$this->controller->set(compact('cakeclientMenu', 'cakeclientConfigMenu'));
		if(	!in_array('Cakeclient.Asset', $this->controller->helpers)
		AND	!isset($this->controller->helpers['Cakeclient.Asset']))
			$this->controller->helpers[] = 'Cakeclient.Asset';
	}
	
	
	function getActions($action = null, $table = null, $controlled = true) {
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
						case 'add': $title .= ' '.$modelName; break;
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
	}
}
?>