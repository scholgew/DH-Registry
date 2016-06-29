<?php
if(isset($crudRelations) AND !empty($crudRelations['hasMany'])) {
	$parent_id = false;
	if(empty($primaryKeyName)) {
		$primaryKeyName = 'id';
	}
	if(isset($modelName) AND isset($record[$modelName]) AND isset($record[$modelName][$primaryKeyName])) {
		$parent_id = $record[$modelName][$primaryKeyName];
	}
	foreach($crudRelations['hasMany'] as $assoc) {
		if(!empty($record[$assoc['alias']])) {
			$link_url = array(
				'action' => 'index',
				'controller' => $assoc['tablename'],
				'plugin' => Configure::read('CakeClient.prefix'),
				$assoc['foreign_key'] =>  $parent_id
			);
			if(!$parent_id) unset($link_url[$assoc['foreign_key']]);
			
			echo $this->Html->link('[<] ' . $assoc['label'], $link_url);
		}
	}
}
?>