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
 
 

<p>
	Copyright 2016 
	<?php echo $this->Html->link('Hendrik Schmeer', 'http://hendrikschmeer.de', array('target' => '_blank')); ?>.
</p>
<p>
	On behalf of: 
	<?php
	echo $this->Html->link('Dariah-EU', 'https://dariah.eu/', array('target' => '_blank')) . ', ';
	echo $this->Html->link('DARIAH-VCC2', 'https://dariah.eu/activities/general-vcc-meetings/2nd-general-vcc-meeting.html', array('target' => '_blank')) . ',<br>';
	echo $this->Html->link('Erasmus Studio', 'http://www.eur.nl/erasmusstudio/es/', array('target' => '_blank')) . ' &amp; ';
	echo $this->Html->link('CLARIAH', 'http://www.clariah.nl/', array('target' => '_blank')) . '.';
	?>
</p>
<p>
	<?php echo $this->Html->link('Impressum', '/pages/about#impressum'); ?>
</p>
