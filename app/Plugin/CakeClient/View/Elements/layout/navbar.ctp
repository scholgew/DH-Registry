

<nav id="cakeclient_navbar" class="navbar navbar-default navbar-fixed-top navbar-inverse">
	<div class="container-fluid">
    
	</div>
</nav>
	
	
	<?php
	foreach($menus as $menuName) {
		if(!empty($$menuName)) {
			$menu = $$menuName;
			echo $menu['label'];
			echo $this->element('layout/menu', array('menu' => $menu['list']), array('plugin' => 'Cakeclient'));
		}
	}
	?>
