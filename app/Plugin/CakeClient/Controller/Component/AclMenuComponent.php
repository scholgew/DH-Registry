<?php
class AclMenuComponent extends Component {
	
	var $model = null;
	
	var $modelName = 'CcConfigMenu';
	
	var $controller = null;
	
	// NULL is the "public" accessLevel
	var $userLevel = null;
	
	// the Access Controling Field
	var $acf = 'user_role_id';
	
	public $aro_model = 'UserRole';
	
	var $contextPath = null;
	
	// the current request - /controller will be normalized to /controller/index, the base url is removed, if any
	var $normalizedPath = null;
	
	
	/*
	* don't do anything about authorisation at this stage, 
	* the component might be used to generate a menu alone
	*/
	public function initialize(Controller $controller) {
		$this->controller = $controller;
		if(!isset($controller->{$this->modelName}))
			$controller->loadModel($this->modelName);
		$this->model = $controller->{$this->modelName};
	}
	
	
	/*
	* requires: DefaAuthComponent (includes AuthComponent)
	* doing ACL authorisation in this method
	*/
	public function check($aro_id = null, $aro_model = null) {
		if(empty($aro_id) AND isset($this->controller->Auth))
			$aro_id = $this->controller->Auth->user($this->acf);
		
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
						'CcConfigAction'
					)
				),
				'conditions' => array(
					'foreign_key' => $aro_id,
					'model' => $this->aro_model
				)
			));
			if(!empty($acl)) {
				
			}
		}
		
		return false;
	}
	
	/*
	function initialize(&$controller) {
		$this->controller = $controller;
		foreach($this->settings as $key => $value) {
			$this->$key = $value;
		}
		$this->model = $this->controller->{$this->modelName};
		
		if(isset($this->controller->Auth)) {
			$user = $this->controller->Auth->user();
			if(is_array($user)) {
				if(isset($user[$this->acf])) {
					$this->userLevel = $user[$this->acf];
				}
			}
		}
		
		// fill in the allowedActions array
		$records = $this->model->find('all', array(
			'conditions' => array(
				'role_id' => $this->userLevel
			)
		));
		
		$path = $this->setNormalizedPath();
		
		foreach($records as $i => $record) {
			$record = $record['Menu'];
			$this->controller->allowedActions[$record['path']] = array(
				'title' => $record['title'],
				'url' => $record['path']
			);
			// set the contextPath
			if(strpos($path, $record['path']) === 0) {
				if(!empty($this->contextPath)) {
					// get the longest path
					if(strlen($record['path']) > strlen($this->contextPath)) {
						$this->contextPath = $record['path'];
					}
				}else{
					$this->contextPath = $record['path'];
				}
			}
		}
	}
	*/
	
	function authorizePath($path = null) {
		if(empty($path)) {
			$path = $this->normalizedPath;
		}
		if(empty($this->controller->allowedActions) OR isset($this->controller->allowedActions[$path])) {
			return true;
		}
		
		if($this->controller->request->url === false) {
			// make sure this URL '/' is routed correctly!
			return true;
		}
		
		// allow all Auth->allowedActions on controller level - additionally allow Auth login and logoff actions
		// remeber to allow all public actions in UsersController!
		if(isset($this->controller->Auth)) {
			$allowed = $this->controller->Auth->allowedActions;
			if(	$allowed == array('*') OR $allowed == '*'
			OR	in_array($this->controller->params['action'], $allowed)
			) {
				return true;
			}
			// allow the login action, as it most probably won't appear in the Auth->allowedActions array
			$loginAction = $this->controller->Auth->loginAction;
			if(	$this->controller->request->params['action'] == $loginAction['action']
			AND	$this->controller->request->params['controller'] == $loginAction['controller']
			) {
				return true;
			}
			if(	$this->controller->request->params['action'] == 'logout'
			AND	$this->controller->request->params['controller'] == $loginAction['controller']
			) {
				return true;
			}
		}
		
		// there's not even a basic match - deny
		if(empty($this->contextPath)) {
			return false;
		}
		// match as soon there are parameters extending the allowed path defined in the menu
		$context = Router::parse($this->contextPath);
		$path = Router::parse($path);
		foreach($path as $key => $value) {
			switch($key) {
				case 'pass':
				case 'named':
					continue;
					break;
				case 'controller':
				case 'action':
					if(empty($context[$key]) OR $context[$key] != $value) {
						return false;
					}
					break;
				case 'plugin':
					if($value === null AND empty($context[$key])) {
						continue;
					}
					elseif(empty($context[$key]) OR $context[$key] != $value) {
						return false;
					}
			}
		}

		return true;
	}
	
	// convenience method for use in controllers
	function isAuthorized($user = null) {
		// removes the base path, if any and adds the leading '/'
		$path = $this->normalizedPath;
		
		if(!empty($user) and is_array($user)) {
			switch($user[$this->acf]) {
				case 1:
					$this->controller->admin = true;
					$this->userLevel = 1;
					// allow all actions - important for context menus
					$this->controller->allowedActions = array();
					return true;
				default: 
					$this->userLevel = $user[$this->acf];
			}
		}
		if($this->authorizePath($path)) {
			return true;
		}
		
		return false;
	}
	
	// check if admin and set properties (we need to do this before AuthComponent comes in on Component::startup)
	function authorizeAdmin($user) {
		// allow users on admin route to go anywhere - in debug mode only!
		if(Configure::read('debug') > 0 AND !empty($this->controller->request->params['admin'])) {
			if(!is_array($user)) {
				$user = array();
			}
			$user[$this->acf] = $this->controller->user[$this->acf] = 1;
		}
		if(!empty($user) and is_array($user)) {
			switch($user[$this->acf]) {
				case 1:
					$this->controller->admin = true;
					$this->userLevel = 1;
					// allow all actions - important for context menus
					$this->controller->allowedActions = array();
					if(isset($this->controller->Auth)) {
						// make all actions public if we're in debug mode and debugging admin while not logged in
						$this->controller->Auth->allowedActions = array();
					}
					return true;
			}
		}
	}
	
	function getContext($path = null) {
		$context = null;
		if(empty($path)) {
			$path = $this->getNormalizedPath();
		}
		foreach($this->controller->allowedActions as $template => $content) {
			if(strpos($path, $template) === 0) {
				if(!empty($context)) {
					// get the longest path
					if(strlen($template) > strlen($context)) {
						$context = $template;
					}
				}else{
					$context = $template;
				}
			}
		}
		
		return $context;
	}
	
	function setContext() {
		return $this->contextPath = $this->getContext(Router::normalize($this->controller->request->url));
	}
	
	function getNormalizedPath() {
		return Router::normalize($this->controller->request->url);
	}
	
	function setNormalizedPath() {
		return $this->normalizedPath = $this->getNormalizedPath();
	}
	
	function getMenu($role_id = null) {
		if(empty($role_id)) {
			$role_id = $this->userLevel;
		}
		$records = $this->model->find('all', array(
			'contain' => '',
			'conditions' => array(
				'role_id' => $role_id,
				'parent_id' => null,
				'menu_type_id' => 1	// main menu
			),
			'order' => array(
				'lft' => 'ASC'
			)
		));
		
		$menu = array();
		foreach($records as $i => $record) {
			$record = $record['Menu'];
			
			$submenu = array();
			if(isset($this->controller->Crud)) {
				$parsed = Router::parse($record['path']);
				$controller = null;
				if(!empty($parsed['controller'])) {
					$controller = $parsed['controller'];
				}
				if(!empty($parsed['table'])) {
					$controller = $parsed['table'];
				}
				if(!empty($controller)) {
					//$submenu = $this->controller->Crud->getActions($parsed['action'], $controller);
				}
			}
			
			$menu[$record['path']] = array(
				'title' => $record['title'],
				'url' => $record['path'],
				'submenu' => $submenu
			);
		}
		return $menu;
	}
	
	function setMenu($role_id = null) {
		if(empty($role_id)) {
			$role_id = $this->userLevel;
		}
		$menu = $this->getMenu($role_id);
		$this->controller->set(compact('menu'));
		return $menu;
	}
}
?>