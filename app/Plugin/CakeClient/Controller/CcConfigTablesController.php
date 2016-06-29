<?php
class CcConfigTablesController extends CakeclientAppController {
	
	
	
	function store_actions($for_table = null) {
		$this->CcConfigTable->CcConfigAction->store($for_table);
		$this->redirect('index');
	}
	
	function tidy_actions($for_table = null) {
		$this->CcConfigTable->CcConfigAction->tidy($for_table);
		$this->redirect('index');
	}
	
	
	/**
	* Var $table can either be a string table name (using current config_id),
	* or the table identifier itself.
	*/
	function __format_relations(&$relations = array()) {
		$model_relations = array();
		if(!empty($relations)) {
			foreach($relations as $type => $assocs) {
				foreach($assocs as $assoc) {
					$model_relations[] = $assoc;
				}
			}
		}
		return $relations = $model_relations;
	}
	
	function store_relations($table = null) {
		$relations = $this->Crud->getRelations($table, $from_model = true);
		$this->__format_relations($relations);
		$this->CcConfigTable->CcConfigDisplayedrelation->store($relations);
		$this->redirect('index');
	}
	
	function tidy_relations($table = null) {
		$relations = $this->Crud->getRelations($table, $from_model = true);
		$this->__format_relations($relations);
		$this->CcConfigTable->CcConfigDisplayedrelation->tidy($relations, $table);
		$this->redirect('index');
	}
}
?>