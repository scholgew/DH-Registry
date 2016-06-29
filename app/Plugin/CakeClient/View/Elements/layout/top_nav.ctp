<?php
$menus = Configure::read('Cakeclient.topNav');
?>
<div id="cakeclient_top_nav">
	<?php
	foreach($menus as $menuName) {
		//echo $$menuName['label'];
		//echo $this->element('layout/menu', array('menu' => $$menuName['list']), array('plugin' => 'Cakeclient'));
	}
	?>
</div>