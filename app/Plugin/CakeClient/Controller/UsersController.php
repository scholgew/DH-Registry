<?php
class UsersController extends CakeclientAppController {

	function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allowedActions = array(
			'logout',
			'hash'
		);
	}
	
	
	function login() {
		if($this->request->is('post')) {
			if($this->Auth->login()) {
				$this->redirect($this->Auth->redirect());
			}else{
				$this->Session->setFlash(__('Invalid username or password, try again'));
			}
		}
		$this->set('title_for_layout', 'User Login');
	}
	
	function logout() {
		$this->redirect($this->Auth->logout());
	}
	
	function hash() {
		if(Configure::read('debug') > 0) {
			if(!empty($this->data['User']['password'])) {
				$this->set('hash', $this->Auth->password($this->data['User']['password']));
				unset($this->request->data['User']['password']);
			}
			$this->set('title_for_layout', 'Hash Passwords');
		}else{
			$this->redirect('/');
		}
	}
	
}
?>