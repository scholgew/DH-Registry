<?php
if(isset($crudRelations) AND !empty($crudRelations['belongsTo'])) {
	foreach($crudRelations['belongsTo'] as $assoc) {
		$primaryKeyName = 'id';
		if(!empty($assoc['primary_key'])) {
			$primaryKeyName = $assoc['primary_key'];
		}
		if(!empty($record[$modelName][$assoc['foreign_key']]) AND ctype_digit($record[$modelName][$assoc['foreign_key']])) {
			$parent_id = $record[$assoc['alias']][$primaryKeyName];
			$link_url = array(
				'action' => 'view',
				'controller' => $assoc['tablename'],
				'plugin' => Configure::read('Cakeclient.prefix')
			);
			$link_url[0] = $record[$modelName][$assoc['foreign_key']];
			
			echo $this->Html->link('[>] ' . $assoc['label'], $link_url);
		}
	}
}
?>