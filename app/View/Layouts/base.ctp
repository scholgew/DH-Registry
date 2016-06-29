<!-- 
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
-->

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php
		$page = $this->fetch('title');
		$title = 'DH Registry';
		if($page == 'Courses') $title = 'DH Course Registry';
		elseif($page == 'Projects') $title = 'DH Project Registry';
		echo $title;
		?>
	</title>
	<?php
	if(Configure::read('debug') > 0) {
		echo $this->Html->meta(array('name' => 'robots', 'content' => 'noindex'));
		echo $this->Html->meta(array('name' => 'robots', 'content' => 'nofollow'));
	}else{
		echo $this->Html->meta(array('name' => 'robots', 'content' => 'index'));
		echo $this->Html->meta(array('name' => 'robots', 'content' => 'follow'));
	}
	echo $this->Html->meta('keywords', 'digital humanities, research, programs, courses');
	echo $this->Html->meta('description', 'European platform for digital humanity related research, courses and programs.');
	echo $this->Html->meta('icon');
	
	echo $this->Html->css('styles.css');
	if(Configure::read('debug') > 0) {
		echo $this->Html->css('cake_debugging.css');
	}
	
	echo $this->fetch('meta');
	echo $this->fetch('css');
	?>
	
	
</head>


<body>
	<div id="container">
		
		<?php echo $this->fetch('header'); ?>
		
		<div class="columns">
			<div id="menu">
				<?php
				echo $this->element('Users.login_info');
				?>
				<ul>
				<?php
				echo '<li>' . $this->Html->link('Courses', array('controller' => 'courses', 'action' => 'index', 'plugin' => null)) . '</li>';
				if(stristr($_SERVER['HTTP_HOST'], 'dh-projectregistry.org') !== false)
					echo '<li>' . $this->Html->link('Projects', array('controller' => 'projects', 'action' => 'index', 'plugin' => null)) . '</li>';
				
				echo $this->fetch('menu');
				?><li><hr></li><?php
//echo '<li>' . $this->Html->link('Manual', array('controller' => 'pages', 'action' => 'manual', 'plugin' => null)) . '</li>';
				echo '<li>' . $this->Html->link('Contact us', array('controller' => 'contacts', 'action' => 'send', 'plugin' => null)) . '</li>';
				?>
				</ul>
			</div>
			
			<div id="content">
				<?php
				echo $this->Session->flash();
				echo $this->fetch('content');
				?>
			</div>
		</div>
		
		<div id="footer">
			<?php echo $this->element('footer'); ?>
		</div>
	</div>
	
	
	<script src="https://code.jquery.com/jquery-1.12.4.min.js"
		integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ="
		crossorigin="anonymous">
	</script>
	<script type="text/javascript">
		window.jQuery || document.write('<script type="text/javascript" src="<?php echo $this->Html->url('/js/jquery-1.12.4.min.js', true); ?>"><\/script>')
	</script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" 
		integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" 
		crossorigin="anonymous">
	</script>
	<script type="text/javascript">
		(typeof $().modal == 'function') || document.write('<script type="text/javascript" src="<?php echo $this->Html->url('/js/bootstrap.min.js', true); ?>"><\/script>')
	</script>
	
	<?php echo $this->fetch('script'); ?>
	<script type="text/javascript"><?php echo $this->fetch('script_bottom'); ?></script>
	<script type="text/javascript">
		window.onload = function() {
			<?php echo $this->fetch('onload'); ?>
		}
	</script>
</body>
</html>
