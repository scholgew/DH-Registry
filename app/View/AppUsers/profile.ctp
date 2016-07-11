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
?>
<div class="users_form">
	<h2>Edit Profile</h2>
	<?php
	echo $this->Form->create($modelName);
	
	echo '<fieldset>';
	echo $this->Form->input('id', array('disabled' => true, 'type' => 'text'));
	echo $this->Form->input('last_login', array('disabled' => true, 'type' => 'text'));
	echo $this->Form->input('email', array('disabled' => true, 'required' => false));
	echo '<div class="input text">';
		echo $this->Html->link('Change E-mail', array(
			'controller' => 'users',
			'action' => 'request_email_verification'),
			array('class' => 'label'));
		echo $this->Html->link('Change Password', array(
			'controller' => 'users',
			'action' => 'request_new_password'),
			array('class' => 'label'));
	echo '</div>';
	$modSettingOptions = array('disabled' => true, 'type' => 'text');
	if(!empty($auth_user['is_admin'])) {
		echo $this->Form->input('is_admin');
		echo '<p>As UserAdmin, the user will recieve emails that are not being catched by the national moderators.</p>';
		echo $this->Form->input('user_admin');	// get the emails not catched by the national mods
		if($auth_user['id'] != $this->request->data[$modelName]['id']) {
			echo '<p>If not active, the user is banned and cannot log in!</p>';
			echo $this->Form->input('active');
		}
		$modSettingOptions = array();
	}
	echo '</fieldset>';
	
	if(	!empty($auth_user['is_admin'])
	OR (!empty($auth_user['user_role_id']) AND $auth_user['user_role_id'] == 2)) {
		echo '<fieldset>';
		echo '<p>As a moderator, you must be assigned to a country from the list.</p>';
		echo '<p>';
			echo 'If your country doesn\'t exist, please go to "'
				.$this->Html->link('Add Country', '/moderator/countries/add').'".';
		echo'</p>';
		echo $this->Form->input('user_role_id', $modSettingOptions);
		echo $this->Form->input('country_id', array(
			'required' => ($auth_user['user_role_id'] == 2) ? 'required' : false,
			'div' => array('class' => ($auth_user['user_role_id'] == 2) ? 'input select required' : 'input select')
		));
		$this->append('script_bottom');
		?>
		$('#AppUserUserRoleId').on('change', function() {
			console.log('foo');
			if($('#AppUserUserRoleId').value == 2) {
				$('#AppUserCountryId').attr('required', 'required');
				$('#AppUserCountryId').parent().addClass('required');
			}else{
				$('#AppUserCountryId').attr('required', '');
				$('#AppUserCountryId').parent().removeClass('required');
			}
		});
		<?php
		$this->end();
		echo '</fieldset>';
	}
	
	echo '<fieldset>';
	echo $this->Form->input('institution_id', array(
		'label' => 'Institution',
		'empty' => '-- choose institution --'
	));
	echo $this->Form->input('academic_title');
	echo $this->Form->input('first_name');
	echo $this->Form->input('last_name');
	echo $this->Form->input('telephone', array(
		'type' => 'text'
	));
	echo $this->Form->input('about', array(
		'type' => 'textarea',
		'label' => 'About me',
		'required' => false
	));
	echo '</fieldset>';
	echo $this->Form->end('Submit');
	?>
	
</div>