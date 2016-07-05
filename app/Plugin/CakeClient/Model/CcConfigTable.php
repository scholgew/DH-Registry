<?php
// check for an override
if(file_exists(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME))) {
	require_once(APP . 'Model' . DS . pathinfo(__FILE__, PATHINFO_BASENAME));
	return;
}

class CcConfigTable extends CakeclientAppModel {
	
	
	
	/*
	var $actsAs = array(
		'Utils.Sortable' => array(
			'parentId' => 'cc_config_configuration_id'
		)
	);
	*/
	
	var $belongsTo = array(
		'CcConfigMenu' => array(
			'className' => 'CcConfigMenu',
			'foreignKey' => 'cc_config_menu_id',
		),
		'CcConfigAco' => array(
			'className' => 'CcConfigAco',
			'foreignKey' => 'cc_config_aco_id',
		)
	);
	
	var $hasMany = array(
		'CcConfigAction' => array(
			'className' => 'CcConfigAction',
			'foreignKey' => 'cc_config_table_id'
		),
		'CcConfigFielddefinition' => array(
			'className' => 'CcConfigFielddefinition',
			'foreignKey' => 'cc_config_table_id'
		),
		'CcConfigDisplayedrelation' => array(
			'className' => 'CcConfigDisplayedrelation',
			'foreignKey' => 'cc_config_table_id'
		)
	);
	
	
	public function getTables($source = null) {
		if(empty($source)) $source = 'default';
		App::uses('ConnectionManager', 'Model');
		$db = ConnectionManager::getDataSource($source);
		return $db->listSources();
	}
	
	
	public function getDefaultTable($tableName, $i = 0, $prefix = null) {
		$tableLabel = $this->makeTableLabel($tableName, $prefix);
		return array(
			//'id' => '1',
			//'cc_config_menu_id' => 1,
			'position' => $i+1,
			'name' => $tableName,
			'allow_all' => false,	// admin is allowed anyway
			'label' => $tableLabel,
			'model' => Inflector::classify($tableName),
			'controller' => $tableName,
			'displayfield' => null,
			'displayfield_label' => null,
			'show_associations' => true
		);
	}
	
	
	public function getGroupTables($source = null, $group = array(), $prefixes = array()) {
		$prefix = (!empty($group['prefix'])) ? $group['prefix'] : null;
		$_tables = $this->getTables($source);
		$tables = array();
		if(!empty($_tables)) foreach($_tables as $i => $tableName) {
			$hit = false;
			if(empty($prefix)) {
				// get only those tables that don't match any prefix
				foreach($prefixes as $pr) {
					if(strpos($tableName, $pr) === 0) {
						$hit = true;
						break;
					}
				}
				if($hit) continue;
			}else{
				if(strpos($tableName, $prefix) === false) continue;
			}
			$tables[] = $this->getDefaultTable($tableName, $i, $prefix);
		}
		return $tables;
	}
	
	
	public function getDefaultAcoTableTree($sources = array()) {
		$tables = array();
		foreach($sources as $source)
			$tables = array_merge($tables, $this->getGroupTables($source));
		if(!empty($tables)) foreach($tables as $i => &$table) {
			$table['CcConfigAction'] = $this->CcConfigAction->getDefaultActions($table['name'], null);
		}
		return $tables;
	}
	
	
	public function getDefaultMenuTableTree($source = null, $group = array(), $prefixes = array()) {
		$tables = $this->getGroupTables($source, $group, $prefixes);
		$prefix = (!empty($group['prefix'])) ? $group['prefix'] : null;
		if(!empty($tables)) foreach($tables as $i => &$table) {
			$table['CcConfigMenuEntry'] = array();
			$actions = $this->CcConfigAction->getDefaultActions($table['name'], 'menu', $prefix);
			if(!empty($actions)) foreach($actions as $k => $action) {
				$table['CcConfigMenuEntry'][] = array(
					//'id',
					//'cc_config_table_id',
					//'cc_config_action_id'
					'position' => $k+1,
					'CcConfigAction' => $action
				);
			}
		}
		return $tables;
	}
	
	
	
	
	
	
	/**
	* Takes either a table ID or a table name as argument, 
	* returns the table ID, sets the table name by reference.
	*/
	function getTable(&$table) {
		$table_id = false;
		if(ctype_digit($table) AND $table > 0) {
			$table_id = $table;
			$stored = $this->find('first', array(
				'conditions' => array(
					'id' => $table_id
				),
				'recursive' => -1
			));
			$table = $stored['CcConfigTable']['name'];
			
		}elseif(!empty($table) AND is_string($table)) {
			$tableDef = Configure::read('Cakeclient.tables.' . $table);
			if($tableDef) {
				$table_id = $tableDef['id'];
			}else{
				$stored = $this->find('first', array(
					'conditions' => array(
						'name' => $table,
						// #ToDo: 'cc_config_menu_id'
					),
					'recursive' => -1
				));
				if($stored) $table_id = $stored['CcConfigTable']['id'];
			}
		}
		return $table_id;
	}
	
	
	function update($config_id = null) {
		$this->tidy($config_id);
		$this->store($config_id);
	}
	
	/**
	* Create table definitions in configuration from all db-tables 
	* that cannot be found in configuration.
	*/
	function store($menu_id = null) {
		if(empty($config_id)) {
			// use the current config id
			$config_id = Configure::read('Cakeclient.config_id');
		}
		
		if(!isset($this->tables) OR empty($this->tables)) {
			$this->__getDbTables();
		}
		$tables = $this->tables;
		
		$storedTables = $this->find('all', array(
			'conditions' => array(
				'cc_config_configuration_id' => $config_id
			),
			'recursive' => -1
		));
		$update = false;
		foreach($tables as $i => $tablename) {
			$stored = false;
			foreach($storedTables as $k => $storedTable) {
				if($storedTable['CcConfigTable']['name'] == $tablename) {
					$stored = true;
					break;
				}
			}
			if(!$stored) {
				$this->create();
				$this->save(array(
					'position' => $i + 1,
					'cc_config_configuration_id' => $config_id,
					'name' => $tablename,
					'label' => Inflector::humanize($tablename),
					'modelclass' => Inflector::singularize(Inflector::camelize($tablename)),
					'displayfield' => null, // stick to the cake default "id", "name" or "title"
					'displayfield_label' => Inflector::humanize(Inflector::singularize($tablename))
				), false);
				$this->CcConfigConfiguration->save(array(
					'id' => $config_id,
					'modified' => date("Y-m-d H:i:s")
				), false);
				$update = true;
			}
		}
	}
	
	function __getDbTables($dataSource = 'default') {
		App::uses('ConnectionManager', 'Model');
		$db = ConnectionManager::getDataSource($dataSource);
		return $this->tables = $db->listSources();
	}
	
	/**
	* Remove all table definitions from configuration
	* that cannot be found in the db anymore.
	*/
	function tidy($config_id = null) {
		if(empty($config_id)) {
			// use the current config id
			$config_id = Configure::read('Cakeclient.config_id');
		}
		if(!isset($this->tables) OR empty($this->tables)) {
			$this->__getDbTables();
		}
		$tables = $this->tables;
		
		$storedTables = $this->find('all', array(
			'conditions' => array(
				'cc_config_configuration_id' => $config_id
			),
			'recursive' => -1
		));
		
		$removable = array();
		foreach($storedTables as $k => $storedTable) {
			$existant = false;
			foreach($tables as $i => $tablename) {
				if($storedTable['CcConfigTable']['name'] == $tablename) {
					$existant = true;
					break;
				}
			}
			if(!$existant) {
				$removable[] = $storedTable['CcConfigTable']['id'];
			}
		}
		if(!empty($removable)) {
			$this->deleteAll(array('CcConfigTable.id' => $removable), $cascade = true);
			// saving action will refresh the configuration cache
			$this->CcConfigConfiguration->save(array(
				'id' => $config_id,
				'modified' => date("Y-m-d H:i:s")
			), false);
		}
	}
	
}
?>