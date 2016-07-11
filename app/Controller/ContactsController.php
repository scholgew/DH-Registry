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

class ContactsController extends AppController {
	
	
	public $uses = array(
		'Country',
		'AppUser'
	);
	
	
	
	public function beforeFilter() {
		parent::beforeFilter();
		
		$this->Auth->allow(array('send'));
		$this->set('title_for_layout', 'Contact');
	}
	
	public function send() {
		if(!empty($this->request->data['Contact'])) {
			$data = $this->request->data['Contact'];
			$admins = array();
			// try fetching the moderator in charge of the user's country, 
			if(!empty($data['country_id'])) {
				$admins = $this->AppUser->find('all', array(
					'contain' => array(),
					'conditions' => array(
						'AppUser.country_id' => $data['country_id'],
						'AppUser.user_role_id' => 2,	// moderators
						'AppUser.active' => 1
					)
				));
			}
			// then user_admin
			if(empty($country_id)) {
				$admins = $this->AppUser->find('all', array(
					'contain' => array(),
					'conditions' => array(
						'AppUser.user_admin' => 1,
						'AppUser.active' => 1
					)
				));
			}
			if($admins) {
				foreach($admins as $admin) {
					// email logic
					App::uses('CakeEmail', 'Network/Email');
					$Email = new CakeEmail();
					$Email->from($this->request->data['Contact']['email'])
					->to($admin['AppUser']['email'])
					->subject('[DH-Registry Contact-Form] New Question')
					->send($this->request->data['Contact']['message']);
				}
				$this->Session->setFlash('Your message has been sent.');
				$this->redirect('/');
			}else{
				$this->Session->setFlash('Error: No Admin could be found!');
			}
		}
		
		$mods = $this->AppUser->find('all', array(
			'contain' => array(),
			'conditions' => array('AppUser.user_role_id' => 2)
		));
		$country_ids = array();
		if($mods) foreach($mods as $mod) {
			if(!empty($mod['AppUser']['country_id']))
				$country_ids[] = $mod['AppUser']['country_id'];
		}
		$countries = $this->Country->find('list', array(
			'order' => 'Country.name ASC',
			'conditions' => array('Country.id' => $country_ids)
		));
		$this->set('countries', $countries);
	}
}
?>











