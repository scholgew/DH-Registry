<?php
if(isset($crudRelations) AND !empty($crudRelations['hasAndBelongsToMany'])) {
	$parent_id = false;
	if(empty($primaryKeyName)) {
		$primaryKeyName = 'id';
	}
	if(isset($modelName) AND isset($record[$modelName]) AND isset($record[$modelName][$primaryKeyName])) {
		$parent_id = $record[$modelName][$primaryKeyName];
	}
	foreach($crudRelations['hasAndBelongsToMany'] as $assoc) {
		if(!empty($record[$assoc['alias']])) {
			$link_url = array(
				'action' => 'index',
				'controller' => $assoc['tablename'],
				'plugin' => Configure::read('CakeClient.prefix'),
				$modelName . '.' . $assoc['primary_key'] => $parent_id
			);
			if(!$parent_id) unset($link_url[$assoc['primary_key']]);
			
			echo $this->Html->link('[><] ' . $assoc['label'], $link_url);
		}
	}
}
?>