<!DOCTYPE html>
<html>
<head>
	<?php
	if(Configure::read('debug') > 0) {
		header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', FALSE);
		header('Pragma: no-cache');
	}
	?>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php
		if(!empty($page_title)) echo $page_title;
		// we're using title_for_layout as a subtitle
		if(!empty($title_for_layout)) echo ' - ' . $title_for_layout;
		?>
	</title>
	<?php
	if(!Configure::read('Cakeclient.robots') OR Configure::read('debug') > 0) {
		echo $this->Html->meta(array('name' => 'robots', 'content' => 'noindex'));
	}
	
	$meta_description = Configure::read('Cakeclient.description');
	$meta_keywords = Configure::read('Cakeclient.keywords');
	if($meta_keywords) {
		echo $this->Html->meta('keywords', $meta_keywords);
	}
	if($meta_description) {
		echo $this->Html->meta('description', $meta_description);
	}
	
	echo $this->Html->meta('icon');
	
	echo $this->Html->css('Cakeclient.styles.css');
	if(Configure::read('debug') > 0) {
		echo $this->Html->css('Cakeclient.cake_debugging.css');
	}
	
	echo $this->fetch('meta');
	echo $this->fetch('css');
	echo $this->fetch('script');
	
	echo $this->element('layout/onload', array(), array('plugin' => 'Cakeclient'));
	?>
	
</head>
<body>
	<div id="container">
		
		<?php echo $this->element('layout/header'); ?>
		
		<div class="columns">
			<div id="menu">
				<?php echo $this->element('layout/menu'); ?>
			</div>
			
			<div id="content">
				<h2><?php echo $title_for_layout; ?></h2>
				<?php
				echo $this->Session->flash();
				echo $this->fetch('content');
				?>
			</div>
		</div>
		
		<div id="footer">
			<?php
			if($footer = Configure::read('Cakeclient.footer')) {
				if(is_string($footer)) {
					echo $footer;
				}
				elseif(is_array($footer)) {
					if(!empty($footer['element'])) {
						echo $this->element($footer['element']);
					}
					if(!empty($footer['text'])) {
						echo $footer['text'];
					}
				}
			}
			?>
		</div>
	</div>
</body>
</html>
