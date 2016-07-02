<?php
class AclMenuComponent extends Component {
	
	public $model = null;
	
	public $modelName = 'CcConfigMenu';
	
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
		if(!isset($controller->{$this->modelName}))
			$controller->loadModel($this->modelName);
		$this->model = $controller->{$this->modelName};
		$this->request = $controller->request;
		
		if(isset($this->controller->Auth))
			$this->_aro_id = $this->controller->Auth->user($this->acf);
	}
	
	
	/*
	* requires: DefaAuthComponent (includes AuthComponent)
	* doing ACL authorisation in this method
	*/
	public function check($aro_id = null, $aro_model = null) {
		if(empty($aro_id) $aro_id = $this->_aro_id;
		
		// give way for the admin
		if(	isset($this->controller->DefaultAuth)
		AND	$this->controller->DefaultAuth->isAdmin()
		) {
			return true;
		}else{
			// now authorize against the list! (if any)
			$acl = $this->model->find('first', array(
				'contain' => array(
					'CcConfigTable' => array(
						'conditions' => array('name' => $this->request->params['controller']),
						'CcConfigAction'
					)
				),
				'conditions' => array(
					'foreign_key' => $aro_id,
					'model' => $this->aro_model
				)
			));
			if(!empty($acl)) {
				foreach($acl['CcConfigTable'] as $t => $table) {
					if($table['name'] == $this->request->params['controller']) {
						if(!empty($table['allow_all'])) return true;
						
						if(!empty($entry['CcConfigAction'])) {
							foreach($entry['CcConfigAction'] as $a => $action)
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
}
?>