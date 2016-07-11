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
<h2>Invite New Course Maintainer</h2>
<p>The invited user will recieve an email to set their password and join the DH-Courseregistry.</p>
<?php
echo $this->Form->create($modelName);
echo '<fieldset>';
if(Configure::read('Users.username')) {
	echo $this->Form->input('username', array(
		'label' => 'Username'
	));
}
echo $this->Form->input('email', array(
	'autocomplete' => 'off'
));
echo $this->Form->input('message', array(
	'type' => 'textarea',
	'value' => 'Dear colleague,
	
We would like to kindly invite you to include your teaching activity to the Digital Humanities Course Registry (DHCR). 

This resource offers an overview and search environment of courses related to Digital Humanities that are offered at universities and research institutes within your home country and the rest of Europe. The initiative endorses the principle that sharing knowledge is in the best interest of students, lecturers and researchers. 

Our aim is twofold: 
1) we want to offer students the opportunity to identify courses of their interest at home or abroad 
2) we want to offer lecturers the possibility to get an overview of teaching activities elsewhere. 
We strongly encourage the use of the DHCR as a way for lecturers to grant access to their teaching resources to peers. 

To ensure that the Course Registry grows into a sustainable resource with the widest possible 
coverage we need your help. There are two possibilities to support us: 

1. If you have already provided information for DHCR in 2014/2015, please access your account by clicking the link below to set your password. 
Please contact me thereafter, so that we can link your data to your account in order for you to update it. 

2. If you are new to DHCR, we will have probably identified your teaching activity through the internet or through word of mouth. 
Please also click on this link to access your account. 
After setting your password you can access a form in which you can enter the data about your course. 

The data that you provide will be reviewed and processed by the national coordinator who has the task of monitoring and curating the DHCR in your country. The name and contact details of the coordinators can be found below. 

We sincerely hope you will contribute to our effort to expand the knowledge on how technology can support research in the humanities and social sciences.

Best wishes and thank you for your effort,

'.$auth_user['name'].'(Moderator) and the Course Registry Team.


'
));
echo '</fieldset>';
echo '<fieldset>';
?>
<p>If the new users Institution is not available, you have to create that entry in the database.</p>
<p>Adding a new institution requires the institution's city and the city's country to be pre-existant.</p>
<p>
	Start here to check whether the country exists: <?php echo $this->Html->link('Countries List', '/moderator/countries/index'); ?>,<br>
	then check if the city exists: <?php echo $this->Html->link('Cities List', '/moderator/cities/index'); ?>.
</p>
<?php
echo $this->Form->input('institution_id', array(
	'label' => 'Institution',
	'empty' => '-- choose institution --'
));
echo '</fieldset>';
echo '<fieldset>';
echo $this->Form->input('academic_title');

echo $this->Form->input('first_name');

echo $this->Form->input('last_name');

echo $this->Form->input('telephone', array('type' => 'text', 'required' => false));

echo $this->Form->input('about', array(
	'label' => 'Remarks',
	'required' => false,
	'type' => 'textarea',
	'placeholder' => 'Please provide the name of the department or any other contact details that show the users involvement towards Digital Humanities.',
));
echo '</fieldset>';
echo $this->Form->end('Invite');
?>