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

$this->extend('/Layouts/base');

$DODH = false;
if(stristr($_SERVER['HTTP_HOST'], 'dh-projectregistry.org') !== false) $DODH = true;

$this->start('header');
?>
<div id="header">
	<?php
	$logo = array(
		'file' => 'DARIAH-EURGB-Klein.png',
		'alt' => 'DARIAH-EU',
		'url' => 'http://dariah.eu/',
		'width' => 202,
		'height' => 61
	);
	if($this->request->params['controller'] == 'projects' OR $DODH) 
		$logo = array(
			'file' => 'eHumanities_small.png',
			'alt' => 'The eHumanities Group',
			'url' => 'https://www.knaw.nl/en/institutes/e-humanities-group',
			'width' => 236,
			'height' => 46
		);	
	echo $this->Html->image('/img/logos/' . $logo['file'], array(
		'alt' => $logo['alt'],
		'class' => 'left',
		'url' => $logo['url'],
		'width' => $logo['width'],
		'height' => $logo['height']
	));
	?>
	<div>
		<h1>
			<?php
			echo $this->Html->link('Digital Humanities Registry', '/');
			$title = $this->fetch('title');
			if(!empty($title)) echo ' - ' . $title;
			?>
		</h1>
		<?php
		if($this->request->params['controller'] != 'projects') {
			?>
			<p>
				Courseregistry <strong>BETA</strong> |
				<?php echo $this->Html->link('About', '/pages/about'); ?>
			</p>
			<?php
		}else{
			?>
			<p>
				Projectregistry <strong>BETA</strong> |
				<?php echo $this->Html->link('About', '/pages/projectregistry'); ?>
			</p>
			<?php
			if(!$DODH) {
				?>
				<p>
					<strong>This Project-Registry is a copy of</strong>
					<?php echo $this->Html->link('DODH', 'http://dh-projectregistry.org'); ?> (Dutch Overview Digital Humanities)
				</p>
				<?php
			}
		}
		?>
	</div>
</div>
<?php
$this->end();

// pass content to parent view
echo $this->fetch('content');
?>