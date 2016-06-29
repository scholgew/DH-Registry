<?php
if(isset($crudRelations) AND !empty($crudRelations['hasMany'])) {
	echo '<div class="associations">';
	echo '<p>Child Models</p>';
	echo '<ul>';
	$parent_id = false;
	if(empty($primaryKeyName)) {
		$primaryKeyName = 'id';
	}
	if(isset($modelName) AND isset($record[$modelName]) AND isset($record[$modelName][$primaryKeyName])) {
		$parent_id = $record[$modelName][$primaryKeyName];
	}
	foreach($crudRelations['hasMany'] as $assoc) {
		$link_url = array(
			'action' => 'index',
			'controller' => $assoc['tablename'],
			'plugin' => Configure::read('CakeClient.prefix'),
			$assoc['foreign_key'] =>  $parent_id
		);
		if(!$parent_id) unset($link_url[$assoc['foreign_key']]);
		echo '<li>';
		echo $this->Html->link($assoc['label'], $link_url);
		echo '</li>';
	}
	echo '</ul>';
	echo '</div>';
}
?>