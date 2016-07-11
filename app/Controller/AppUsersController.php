<?php
/**
 * Copyright 2014 Hendrik Schmeer on behalf of DARIAH-EU, VCC2 and DARIAH-DE,
 * Credits to Erasmus University Rotterdam, University of Cologne, PIREH / University Paris 1
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

App::uses('UsersController', 'Users.Controller');

/**	Extending the plugin's UsersController.
*	This is not neccessary to override plugin views on app-level, but if you want to extend the plugin views. 
*	
*	The Users plugin is a reinforced/refactored version of the CakeDC Users plugin.
*	documentation: https://github.com/CakeDC/users/blob/master/Docs/Documentation/Extending-the-Plugin.md
*/

class AppUsersController extends UsersController {
    
	public $name = 'AppUsers';
	
	// if using the plugin's model:
	//public $modelClass = 'Users.User';
	//public $uses = array('Users.User');
	
	public $modelClass = 'AppUser';
	
	public $uses = array('AppUser');
	
	
	public function beforeFilter() {
		parent::beforeFilter();
		
		if($this->Auth->user('user_role_id') < 3) $this->Auth->allow(array('invite'));
		$this->Auth->allow(array('approve'));
		
		$this->set('title_for_layout', 'User Management');
	}
	
	
	
	
	
	// render the plugin views by default, if no app-view exists
	public function render($view = null, $layout = null) {
		if(is_null($view)) {
			$view = $this->action;
		}
		$viewPath = substr(get_class($this), 0, strlen(get_class($this)) - 10);
		clearstatcache();
		if(!file_exists(APP . 'View' . DS . $viewPath . DS . $view . '.ctp')) {
			$this->viewPath = $this->plugin = 'Users';
		}else{
			$this->viewPath = $viewPath;
		}
		return parent::render($view, $layout);
	}
	
	
	protected function _setOptions() {
		$institutions = $this->AppUser->Institution->find('list', array(
			'contain' => array('Country'),
			'fields' => array('Institution.id', 'Institution.name', 'Country.name'),
			'conditions' => array('Institution.can_have_course' => 1)
		));
		ksort($institutions);
		$countries = $this->AppUser->Country->find('list', array('order' => 'Country.name ASC'));
		$userRoles = $this->AppUser->UserRole->find('list');
		$this->set(compact('institutions','countries','userRoles'));
	}
	
	
	public function register() {
		parent::register();
		$this->_setOptions();
	}
	
	
	protected function _newUserAdminNotification($user = array()) {
		if(empty($user)) return false;
		$result = true;
		$admins = array();
		$mailOpts = array(
			'template' => 'Users.admin_new_user',
			'subject' => 'New Account Request',
			'data' => $user
		);
		
		// try fetching the moderator in charge of the user's country, 
		$country_id = (!empty($user[$this->modelClass]['country_id'])) 
			? $user[$this->modelClass]['country_id'] : null;
		if(empty($country_id) AND !empty($user[$this->modelClass]['institution_id'])) {
			$institution = $this->{$this->modelClass}->find('first', array(
				'contain' => array(),
				'conditions' => array(
					'Institution.id' => $user[$this->modelClass]['institution_id']
				)
			));
			if($institution AND !empty($institution['Institution']['country_id']))
				$country_id = $institution['Institution']['country_id'];
		}
		if(!empty($country_id)) {
			$admins = $this->{$this->modelClass}->find('all', array(
				'contain' => array(),
				'conditions' => array(
					$this->modelClass.'.country_id' => $country_id,
					$this->modelClass.'.user_role_id' => 2,	// moderators
					$this->modelClass . '.active' => 1
				)
			));
		}
		
		// then user_admin
		if(empty($country_id)) {
			$admins = $this->{$this->modelClass}->find('all', array(
				'contain' => array(),
				'conditions' => array(
					$this->modelClass . '.user_admin' => 1,
					$this->modelClass . '.active' => 1
				)
			));
		}
		if($admins) {
			foreach($admins as $admin) {
				$mailOpts['email'] = $admin[$this->modelClass]['email']; 
				if(!$this->_sendUserManagementMail($mailOpts)) {
					$result = false;
				}
			}
		}
		
		return $result;
	}
	
	
	public function approve($id = null) {
		$success = $proceed = false;
		if( ($this->Auth->user() AND $this->Auth->user('user_role_id') < 3)
		AND !empty($id) AND ctype_digit($id)) {
			$user = $this->{$this->modelClass}->find('first', array(
				'contain' => array(),
				'conditions' => array(
					$this->modelClass . '.id' => $id,
					$this->modelClass . '.approved' => 0
				)
			));
			if($user) {
				$proceed = true;
			}
		}else{
			// not authenticated!
			// admins retrieve a link in their notification email to approve directly
			$user = $this->{$this->modelClass}->find('first', array(
				'contain' => array(),
				'conditions' => array(
					$this->modelClass . '.approval_token' => $id,
					$this->modelClass . '.approved' => 0
				)
			));
			if($user) {
				$proceed = true;
			}
		}
		
		if($proceed) {
			if(!empty($this->request->data[$this->modelClass])) {
				// the admin submitted additional data
				$user[$this->modelClass] = array_merge($user[$this->modelClass], $this->request->data[$this->modelClass]);
			}
			
			if($user = $this->{$this->modelClass}->approve($user)) {
				$this->_sendUserManagementMail(array(
					'template' => 'Users.account_approved',
					'subject' => 'Account approved',
					'email' => $user[$this->modelClass]['email'],
					'data' => $user
				));
				$this->Session->setFlash('The account has been successfully approved.');
				$success = true;
			}else{
				$this->Session->setFlash('Error: the user data did not pass validation. Please check the details.');
			}
			
			
			if($success) {
				if($this->Auth->user()) $this->redirect(array(
					'plugin' => null,
					'controller' => 'users',
					'action' => 'dashboard'
				));
				$this->redirect('/');
			}
			
			$this->request->data = $user;
			$this->_setOptions();
		}else{
			$this->redirect('/');
		}
		// render the form...
	}
	
	
	public function profile($id = null) {
		$this->_setOptions();
		parent::profile($id);
	}
	
	
	public function dashboard($id = null) {
		if(!empty($id) AND $this->DefaultAuth-isAdmin())
			$course_user_id = $id;
		else
			$course_user_id = $this->Auth->user('id');
		
		$courses = $this->AppUser->Course->find('all', array(
			'conditions' => array(
				'Course.user_id' => $course_user_id,
				'Course.updated >' => date('Y-m-d H:i:s', time() - Configure::read('App.CourseArchivalPeriod'))
			)
		));
		$this->set(compact('courses'));
		
		if($this->DefaultAuth->isAdmin()) {
			if(empty($id)) {
				// admin dashboard
				$unapproved = $this->AppUser->find('all', array(
					'contain' => array('Institution'),
					'conditions' => array(
						$this->modelClass . '.active' => 0,
						$this->modelClass . '.approved' => 0
					)
				));
				
				$invited = $this->AppUser->find('all', array(
					'contain' => array('Institution'),
					'conditions' => array(
						'OR' => array(
							$this->modelClass . '.password IS NULL',
							$this->modelClass . '.password' => ''
						),
						$this->modelClass . '.active' => 1
					)
				));
				
				$this->set(compact('unapproved', 'invited'));
				$this->render('admin_dashboard');
				
			}else{
				$this->set('notice', 'You are viewing the dashboard of User '.$id);
				$this->render('user_dashboard');
			}
			
		}else{
			// user dashboard
			$this->render('user_dashboard');
		}
	}
	
	
	// technically, this is a admin-triggered password reset - thus the email template reads somewhat different
	public function invite($param = null) {
		$mailOpts = array(
			'template' => 'invite_user',
			'subject' => 'Join the Digital Humanities Course Registry',
			'bcc' => $this->Auth->user('email'),
			'sender' => $this->Auth->user('email'),
			'replyTo' => $this->Auth->user('email')
		);
		if(Configure::read('debug') > 0) $mailOpts['transport'] = 'Debug';
		
		if(!empty($param)) {
			if(ctype_digit($param)) {
				// invite individual user - $param == $id
				$user = $this->{$this->modelClass}->find('first', array(
					'contain' => array(),
					'conditions' => array(
						$this->modelClass . '.id' => $param,
						$this->modelClass . '.active' => 1
					)
				));
				if($user AND !empty($user[$this->modelClass]['email'])) {
					$mailOpts['email'] = $user[$this->modelClass]['email'];
					$mailOpts['data'] = $user;
					$this->_sendUserManagementMail($mailOpts);
					$this->Session->setFlash('User will receive a reminder email.');
				}
				
			}elseif($param === 'all') {
				$users = $this->{$this->modelClass}->find('all', array(
					'contain' => array(),
					'conditions' => array(
						$this->modelClass . '.active' => 1,
						$this->modelClass . '.password' => array(null, '')
					)
				));
				if(!empty($users)) {
					foreach($users as $user) {
						if($user AND !empty($user[$this->modelClass]['email'])) {
							$mailOpts['email'] = $user[$this->modelClass]['email'];
							$mailOpts['data'] = $user;
							$this->_sendUserManagementMail($mailOpts);
						}
					}
					$this->Session->setFlash('Users will receive a reminder email.');
				}
			}
			$this->redirect('/users/dashboard');
			
		}else{
			// add a new user
			if(!empty($this->request->data[$this->modelClass])) {
				if($user = $this->{$this->modelClass}->inviteRegister($this->request->data)) {
					if(!empty($user[$this->modelClass]['email'])) {
						$mailOpts['email'] = $user[$this->modelClass]['email'];
						$mailOpts['data'] = $user;
						$this->_sendUserManagementMail($mailOpts);
						$this->Session->setFlash('User successfully invited and emailed.');
					}
					$this->redirect('/users/dashboard');
				}
			}
			$this->_setOptions();
		}
	}
	
	
	
	
	
	
}
?>