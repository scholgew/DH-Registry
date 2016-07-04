<?php
// check for an override
if(file_exists(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME))) {
	require_once(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME));
	return;
}

class CcConfigMenu extends CakeclientAppModel {
	
	public $actsAs = array(
		'Utils.Sortable' => array(
			'parentId' => 'model.foreign_key'
		)
	);
	
	/* Virtually - yes. 
	public $belongsTo = array(
		'UserRole' => array(
			'className' => 'UserRole'
		)
	);
	*/
	
	public $hasMany = array(
		'CcConfigTable' => array(
			'className' => 'CcConfigTable'
		)
	);
	
	public $aro_model = 'UserRole';
	public $aro_value = 1;
	
	
	
	public function getDefaultMenu($group = array(), $k = 0) {
		$source = (!empty($group['dataSource'])) ? $group['dataSource'] : null;
		$tables = $this->CcConfigTable->getTables($source);
		
		$prefix = (!empty($group['prefix'])) ? $group['prefix'] : null;
		$name = (!empty($group['name'])) ? $group['name'] : 'Menu '.$k+1;
		
		return array(
			//'id',
			'label' => $name,
			'position' => $k+1,
			'block' => 'cakeclient_nav',
			'foreign_key' => $this->aro_value,
			'model' => $this->aro_model
		);
	}
	
	
	public function getDefaultMenuTree($dataSource = null, $menuGroups = array()) {
		$menu = array();
		$prefixes = Hash::extract($menuGroups, '{n}.prefix');
		
		if(!empty($menuGroups)) foreach($menuGroups as $k => $group) {
			$menu[$k]['CcConfigMenu'] = $this->getDefaultMenu($group, $k);
			
			$source = (!empty($group['dataSource'])) ? $group['dataSource'] : $dataSource;
			$menu[$k]['CcConfigTable'] = $this->CcConfigTable->getDefaultTableTree($source, $group, $prefixes);
		}
		
		return $menu;
	}
	
	
	/* Copied over from the former configuration model
	*  
	function add($clone = null, $data = array()) {
		if(!Configure::read('Cakeclient.config_id') OR $clone == 'new') {
			$configuration = array(
				'default' => 1,
				'disable_cache_checking' => 1,
				'robots' => 'noindex,nofollow'
			);
			if(Configure::read('Cakeclient.config_id')) {
				$configuration['default'] = 0;
			}
			$this->create();
			if($configuration = $this->save($configuration, false)) {
				Configure::write('Cakeclient', $configuration['CcConfigConfiguration']);
			}
			
		}else{
			// $clone possible values: empty, 'current', [ID]
			$clone_id = Configure::read('Cakeclient.config_id');
			if(!empty($clone) AND ctype_digit($clone)) {
				$clone_id = $clone;
			}
			$data = $this->find('first', array(
				'conditions' => array('CcConfigConfiguration.id' => $clone_id),
				'contain' => array(
					'CcConfigTable' => array(
						'order' => array(
							'CcConfigTable.cc_config_configuration_id' => 'ASC',
							'CcConfigTable.position' => 'ASC'
						),
						'CcConfigFielddefinition' => array(
							'order' => array(
								'CcConfigFielddefinition.cc_config_table_id' => 'ASC',
								'CcConfigFielddefinition.position' => 'ASC'
							),
							'conditions' => array('CcConfigFielddefinition.cc_config_action_id' => null)
						),
						'CcConfigAction' => array(
							'order' => array(
								'CcConfigAction.cc_config_table_id' => 'ASC',
								'CcConfigAction.position' => 'ASC'
							),
							'CcConfigFielddefinition' => array(
								'order' => array(
									'CcConfigFielddefinition.cc_config_action_id' => 'ASC',
									'CcConfigFielddefinition.position' => 'ASC'
								)
							)
						)
					)
				)
			));
			$data['CcConfigConfiguration']['default'] = 0;
			unset($data['CcConfigConfiguration']['id']);
			foreach($data['CcConfigTable'] as $i => $table) {
				unset($data['CcConfigTable'][$i]['id']);
				unset($data['CcConfigTable'][$i]['cc_config_configuration_id']);
				foreach($table['CcConfigAction'] as $k => $action) {
					unset($data['CcConfigTable'][$i]['CcConfigAction'][$k]['id']);
					unset($data['CcConfigTable'][$i]['CcConfigAction'][$k]['cc_config_table_id']);
					foreach($action['CcConfigFielddefinition'] as $m => $fielddef) {
						unset($data['CcConfigTable'][$i]['CcConfigAction'][$k]['CcConfigFielddefinition'][$m]['id']);
						unset($data['CcConfigTable'][$i]['CcConfigAction'][$k]['CcConfigFielddefinition'][$m]['cc_config_table_id']);
						unset($data['CcConfigTable'][$i]['CcConfigAction'][$k]['CcConfigFielddefinition'][$m]['cc_config_action_id']);
					}
				}
			}
			
			$this->saveAll($data, array(
				'validate' => false,
				'deep' => true
			));
		}
	}
	*/
}
?>