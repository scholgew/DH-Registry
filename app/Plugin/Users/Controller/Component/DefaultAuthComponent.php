<?php

App::uses('Component', 'Controller');

class DefaultAuthComponent extends Component {
	
	
	public $settings = array();
	
	// for debugging - to let isAdmin return true
	public $is_admin = false;
	
	public $components = array();
	
	public $userRoleField = null;
	
	public $userRoleAdminValue = null;
	
	private $adminField = null;
	
	private $controller = null;
	
	
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);

		$this->settings = Hash::merge($this->_defaults(), $settings);
		foreach($this->settings as $key => $value)
			$this->{$key} = $value;
	}
	
	private function _defaults() {
		return array(
			'adminField' => 'is_admin',
			'userRoleField' => 'user_role_id',
			'userRoleAdminValue' => 1,
			'components' => array(
				'Auth' => array(
					'priority' => 2,
					'loginAction' => array(
						'controller' => 'users',
						'action' => 'login',
						'plugin' => 'users',
						'admin' => false
					),
					'authError' => 'Please log in to access this location.',
					'authenticate' => array(
						'Form' => array(
							'fields' => array(
								'username' => Configure::read('Users.loginName'),
								'password' => 'password'
							),
							'userModel' => Configure::read('Users.userModel'),
							'scope' => array(
								Configure::read('Users.userModel') . '.active' => 1,
								Configure::read('Users.userModel') . '.email_verified' => 1
							)
						)
					),
					'authorize' => array(
						'Users.AllowedActions'
					),
					'loginRedirect' => array('action' => 'dashboard','controller' => 'users','plugin' => 'users'),
					'logoutRedirect' => '/'
				)
			)
		);
	}
	
	
	public function initialize(Controller $controller) {
		$this->controller = $controller;
		// load all components
		foreach($this->components as $component => $settings) {
			$controller->components[$component] = $settings;
			$controller->{$component} = $controller->Components->load($component, $settings);
			$controller->{$component}->initialize($controller);
		}
		
		$controller->set('auth_user', $controller->Auth->user());
		if(Configure::read('Users.disableDefaultAuth') === true) {
			$controller->Auth->allow();
		}
	}
	
	public function isAdmin() {
		if(method_exists($this->controller, 'isAdmin')) {
			return $this->controller->isAdmin();
		}
		if( (bool)$this->controller->Auth->user($this->adminField)
		OR	(int)$this->controller->Auth->user($this->userRoleField) === $this->userRoleAdminValue
		) return true;
		if(Configure::read('debug') > 0 AND $this->is_admin) return true;
		return false;
	}
	
}
?>