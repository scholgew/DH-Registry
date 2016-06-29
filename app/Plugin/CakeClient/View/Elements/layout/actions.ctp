<?php
	if(!empty($crudActions)) {
		$menu_actions = array();
		// check wether a "add"  action is specified, as this one will be displayed outside the index table (no iteration)
		foreach($crudActions as $k => $action) {
			$contextual = false;
			// action will be an array
			if(isset($action['contextual'])) {
				$contextual = (bool)$action['contextual'];
			}
			unset($action['contextual']);
			if(!$contextual) {
				$menu_actions[] = $action;
				unset($crudActions[$k]);
			}
		}
		$this->set('crudActions', $crudActions);
		
		if(empty($primaryKeyName)) $primaryKeyName = 'id';
		
		if(!empty($menu_actions)) {
			echo '<div class="actions">';
				echo '<ul>';
					foreach($menu_actions as $action) {
						if((bool)$action['append_id']) {
							if(!isset($record_id)) {
								if(!empty($record[$modelName][$primaryKeyName])) {
									$record_id = $record[$modelName][$primaryKeyName];
								}elseif(!empty($this->data[$modelName][$primaryKeyName])) {
									$record_id = $this->data[$modelName][$primaryKeyName];
								}
							}
							if(!empty($record_id)) {
								$action['url'][] = $record_id;
							}
						}
						$options = array('class' => strtolower($action['title']));
						if(strtolower($action['title']) == 'delete') {
							$options['confirm']  = 'Are you sure to delete ' . $modelName . ' with ID ' . $action['url'][0] . '?';
						}
						echo '<li>';
							echo $this->Html->link($action['title'], $action['url'], $options);
						echo '</li>';
					}
				echo '</ul>';
			echo '</div>';
		}
	}
?>