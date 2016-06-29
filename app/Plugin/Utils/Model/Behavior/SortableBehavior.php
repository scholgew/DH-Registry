<?php
class SortableBehavior extends ModelBehavior {
	
	/**	Global settings for Sortable Behavior
	*	'orderBy'		: this is the fieldname, the list is ordered by - must be of type INTEGER in the db
	*	'allowNull'		: Allow orderBy-field to be null on validate (if validate) AND in beforeSave, fill nulls automatically or not.
	*				 true - NULL allowed
	*				 false - NULL disallowed by validation, will fail in beforeSave
	*				 append/prepend - NULL is allowed, but will be filled in automatically with either the highest or lowest value on save/fixOrder
	*	'listNull'		: Whether NULL positioned entries are listed and where (false, 'prepend', true/'append')
	*	'startZero'		: whether the list starts at zero
	*	'parentId'		: if liststyle is NOT flat, specify this fieldname to only change the affected subtree on reorder operations
	*				  defaults to 'parent_id'. 
	*				 Also allowed are concatenated keys of many fields - specify them in one string separated by '.'.
	*				 Array format is allowed as well. 
	*	'validate'		: Set to "merge" or "override" for adjusting the model validator, set to false to do nothing. 
	*				  If "override", the method will not respect any model-rules for the observed field.
	*	'messageZero'	: validation message for zero allowed
	*	'messageNoZero'	: validation message for zero not allowed
	*	
	*	All settings are set once per model!
	*/
	function __defaults($additional = null) {
		$defaults = array(
			'orderBy' => 'position',
			'allowNull' => true,
			'listNull' => 'append',	// this does not work well with pagination, but is less irritation than letting them vanish at all
			'startZero' => false,
			'parentId' => false,	// when things do not work - check the field's name is 'parent_id'!!!
			'validate' => 'override',
			'messageZero' => __('Please enter a positive integer (zero allowed).'),
			'messageNoZero' => __('Please enter a positive integer greater than zero.')
		);
		//-- defaults may be manipulated dynamically, this is used by the unitTest
		if(!empty($additional) AND is_array($additional)) {
			$defaults = array_merge($defaults, $additional);
		}
		
		return $defaults;
	}
	
	
	/**	Private variables to synchronize updating of the list-counter cache 
	*	(highest position) between beforeSave and afterSave. 
	*/
	private $parentKeys = array();
	
	private $previousParentKeys = array();
	
	private $parentHasChanged = false;
	
	// keeping a copy of the find query for afterFind
	private $query = array();
	
	// RAM caching
	private $cacheName = null;
	private $RAMcache = null;
	
	
	
	
	
	function setup(Model $model, $settings = array()) {
		if(!isset($this->settings[$model->alias])) {
			$this->settings[$model->alias] = $this->__defaults();
		}
		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array)$settings);
		
		if($this->settings[$model->alias]['parentId'] === true) {
			$this->settings[$model->alias]['parentId'] = array('parent_id');
		}
		elseif(is_string($this->settings[$model->alias]['parentId']) AND strpos($this->settings[$model->alias]['parentId'], '.')) {
			$this->settings[$model->alias]['parentId'] = explode('.', $this->settings[$model->alias]['parentId']);
			foreach($this->settings[$model->alias]['parentId'] as $k => $v) {
				if(empty($v)) unset($this->settings[$model->alias]['parentId'][$k]);
			}
		}
		elseif(is_string($this->settings[$model->alias]['parentId'])) {
			$this->settings[$model->alias]['parentId'] = array($this->settings[$model->alias]['parentId']);
		}
	}
	
	
	/**	Parent keys should be in the format array('Model.fieldname' => 'fieldvalue')
	*	to recieve the highest position for the parentId's chapter, regardless if the passed fieldnames are parent IDs as of the settings. 
	*/
	function getHighestPosition(&$model, $update = false, $parentKeys = array()) {
		$options = array(
			'recursive' => -1,
			'order' => array($model->alias . '.' . $this->settings[$model->alias]['orderBy'] => 'DESC')
		);
		$cacheName = 'sortable_' . $model->alias . '_highestPosition';
		if(empty($parentKeys)) {
			$keys = $this->settings[$model->alias]['parentId'];
			foreach($keys as $key => $value) {
				$parentKeys[$value] = null;
			}
		}
		if(!empty($parentKeys)) {
			$conditions = array();
			foreach($parentKeys as $key => $value) {
				$fieldname = $cachekey = $key;
				if(strpos($key, $model->alias . '.') === false) {
					$fieldname = $model->alias . '.' . $key;
					$cachekey = $model->alias . '_' . $key;
				}
				$conditions[$fieldname] = $value;
				$cacheName .= '_' . $cachekey . '_' . $value;
			}
			$options['conditions'] = $conditions;
		}
		
		if(!$highest = Cache::read($cacheName) OR $update) {
			$highest = 0;
			$model->Behaviors->disable('Sortable');
			$row = $model->find('first', $options);
			$model->Behaviors->enable('Sortable');
			if($row) $highest = $row[$model->alias][$this->settings[$model->alias]['orderBy']];
			Cache::write($cacheName, $highest);
		}
		return $highest;
	}
	
	
	function getOptions(&$model, $parentKeys = array()) {
		$cacheName = 'sortable_' . $model->alias . '_options';
		if(empty($parentKeys)) {
			$keys = $this->settings[$model->alias]['parentId'];
			foreach($keys as $key => $value) $parentKeys[$value] = null;
		}
		if(!empty($parentKeys)) {
			foreach($parentKeys as $key => $value) {
				if(strpos($key, $model->alias . '.') === false) $key = $model->alias . '_' . $key;
				$cacheName .= '_' . $key . '_' . $value;
			}
		}
		if($this->cacheName !== $cacheName OR empty($this->RAMcache)) {
			$highest = $this->getHighestPosition($model, $update = false, $parentKeys);
			$allowNull = $this->settings[$model->alias]['allowNull'];
			$start = 0;
			if(!$this->settings[$model->alias]['startZero']) {
				$start = 1;
			}
			$range = range($start, $highest);
			$editOptions = array_combine($range, $range);
			$range = range($start, $highest + 1);
			$addOptions = array_combine($range, $range);
			// we cannot add an "empty value" to the array if allowNull is TRUE - thus pass the variable among the others for construction in the form
			$this->RAMcache = compact('highest', 'editOptions', 'addOptions', 'allowNull');
			$this->cacheName = $cacheName;
		}
		return $this->RAMcache;
	}
	
	
	// a very basic validation
	function beforeValidate(Model $model, $options = array()) {
		if($this->settings[$model->alias]['validate']) {
			$msg = $this->settings[$model->alias]['messageZero'];
			$validate = array(
				'create' => array(
					//-- do allow a leading zero
					'rule' => '/^([1-9][0-9]*|0)$/',
					'allowEmpty' => true,
					'on' => 'create',
					'message' => $msg
				),
				'update' => array(
					'rule' => '/^([1-9][0-9]*|0)$/',
					'allowEmpty' => true,
					'on' => 'update',
					'message' => $msg
				)
			);
			
			if(!$this->settings[$model->alias]['startZero']) {
				$rule = '/^([1-9][0-9]*)$/';
				$msg = $this->settings[$model->alias]['messageNoZero'];
				
				$validate['create']['rule'] = $rule;
				$validate['create']['message'] = $msg;
				
				$validate['update']['rule'] = $rule;
				$validate['update']['message'] = $msg;
			}
			if(!$this->settings[$model->alias]['allowNull']) {
				$validate['create']['allowEmpty'] = false;
				$validate['create']['required'] = true;
				
				$validate['update']['allowEmpty'] = false;
			}
			
			$modelValidator = array();
			if(isset($model->validate[$this->settings[$model->alias]['orderBy']])) {
				$modelValidator = $model->validate[$this->settings[$model->alias]['orderBy']];
				if(is_string($modelValidator)) {
					$modelValidator = array($modelValidator => $modelValidator);
				}
			}
			switch($this->settings[$model->alias]['validate']) {
				case 'merge':
					foreach($validate as $rulename => $rulearray) {
						if(isset($model->validate[$this->settings[$model->alias]['orderBy']][$rulename])) {
							foreach($rulearray as $item => $definition) {
								if(!isset($model->validate[$this->settings[$model->alias]['orderBy']][$rulename][$item])) {
									$model->validate[$this->settings[$model->alias]['orderBy']][$rulename][$item] = $definition;
								}
							}
						}else{
							$model->validate[$this->settings[$model->alias]['orderBy']][$rulename] = $rulearray;
						}
					}
					break;
				case 'override':
				default:
					$model->validate[$this->settings[$model->alias]['orderBy']] = $validate;
			}
		}
		
		return true;
	}
	
	
	function beforeSave(Model $model, $options = array()) {
		$parentId = $this->settings[$model->alias]['parentId'];
		$orderBy = $this->settings[$model->alias]['orderBy'];
		$data = $model->data;
		$newPos = $prevPos = null;
		$newPar = $prevPar = array();
		if(isset($data[$model->alias][$orderBy])) {
			$newPos = $data[$model->alias][$orderBy];
			if(empty($newPos) AND $newPos !== 0) {
				$newPos = null;
			}
		}
		
		$model->Behaviors->disable('Sortable');
		
		$orderKey_present = false;
		// if the field is NULL, isset returns false, even if the array key exists, so do additional checking
		if(array_key_exists($orderBy, $data[$model->alias])) $orderKey_present = true;
		
		// terminate, whether it's a new record or an update
		if(isset($model->id) AND !empty($model->id)) {
			// update -  get the existant record
			$model->recursive = -1;
			$record = $model->read();
			if(isset($record[$model->alias][$orderBy])) {
				$prevPos = $record[$model->alias][$orderBy];
			}
			
			$this->parentHasChanged = false;
			if(!empty($parentId)) {
				foreach($parentId as $index) {
					$parentKey_present = false;
					// get the previous parent(s)
					$newPar[$index] = $prevPar[$index] = null;
					if(array_key_exists($index, $record[$model->alias])) {
						$prevPar[$index] = $record[$model->alias][$index];
						$this->previousParentKeys[$model->alias . '.' . $index] = $record[$model->alias][$index];
					}
					if(array_key_exists($index, $data[$model->alias])) {
						$newPar[$index] = $data[$model->alias][$index];
						$this->parentKeys[$model->alias . '.' . $index] = $data[$model->alias][$index];
						if(empty($newPar[$index]) && $newPar[$index] !== 0) {
							$newPar[$index] = null;
							$this->parentKeys[$model->alias . '.' . $index] = null;
						}
						$parentKey_present = true;
					}else{
						$newPar[$index] = $prevPar[$index];
						$this->parentKeys[$model->alias . '.' . $index] = $this->previousParentKeys[$model->alias . '.' . $index];
					}
					// compare with the existent record
					if($parentKey_present AND $newPar[$index] !== $prevPar[$index]) {
						$this->parentHasChanged = true;
					}
				}
			}
			
			// data massaging
			if($orderKey_present) {
				// if NULL is not allowed, return false, otherwise the value is amended
				if(!$this->__checkPositioning($model, $data, $newPos, $newPar)) return false;
			}
			
			if($this->parentHasChanged) {
				// parent id has changed, move to new subtree
				$this->__moveOut($model, $orderBy, $prevPos, $prevPar);
				$this->__moveIn($model, $orderBy, $newPos, $newPar);
				
			}else{
				// parent id hasn't changed - check for the positioning field
				if($orderKey_present AND $newPos !== $prevPos) {
					if($newPos === null) {
						$this->__moveOut($model, $orderBy, $prevPos, $prevPar);
						
					}else{
						// newPos is not empty
						if($prevPos === 0 OR !empty($prevPos)) {
							if($prevPos > $newPos) {
								$this->__moveUp($model, $orderBy, $prevPos, $newPos, $record);
							}elseif($prevPos < $newPos) {
								$this->__moveDown($model, $orderBy, $prevPos, $newPos, $record);
							}
						}else{
							//-- position was previously an empty value != 0, moving in
							$this->__moveIn($model, $orderBy, $newPos, $newPar);
						}
					}
				}
			}
			
		}else{
			// create a new record
			if(!empty($parentId)) {
				foreach($parentId as $index) {
					$newPar[$index] = null;
					if(array_key_exists($index, $data[$model->alias])) {
						$newPar[$index] = $data[$model->alias][$index];
					}
				}
			}
			// if NULL is not allowed, return false, otherwise the value is amended
			if(!$this->__checkPositioning($model, $data, $newPos, $newPar)) return false;
			if($newPos !== null) {
				$this->__moveIn($model, $orderBy, $newPos, $newPar, $create = true);
			}
		}
		
		$model->set($data);
		$model->Behaviors->enable('Sortable');
		
		return true;
	}
	
	function __moveUp(&$model, $orderBy, $prevPos, $newPos, $record) {
		$cond = array(
			$model->alias . '.' . $orderBy . ' >=' => $newPos,
			$model->alias . '.' . $orderBy . ' <' => $prevPos,
			$model->alias . '.' . $model->primaryKey . ' !=' => $model->id,
			$model->alias . '.' . $orderBy . ' <>' => null
		);
		$this->__getParentConditions($model, $cond, $record);
		$this->__moveAffected($model, $move = 1, $cond, $orderBy);
	}
	
	function __moveDown(&$model, $orderBy, $prevPos, $newPos, $record) {
		$cond = array(
			$model->alias . '.' . $orderBy . ' >' => $prevPos,
			$model->alias . '.' . $orderBy . ' <=' => $newPos,
			$model->alias . '.' . $model->primaryKey . ' !=' => $model->id,
			$model->alias . '.' . $orderBy . ' <>' => null
		);
		$this->__getParentConditions($model, $cond, $record);
		$this->__moveAffected($model, $move = -1, $cond, $orderBy);
	}
	
	function __getParentConditions(&$model, &$cond = array(), $record = array()) {
		$parentKeys = $this->settings[$model->alias]['parentId'];
		if(!empty($parentKeys)) {
			foreach($parentKeys as $index) {
				$cond[$model->alias . '.' . $index] = $record[$model->alias][$index];
			}
		}
		return $cond;
	}
	
	function __moveOut(&$model, $orderBy, $prevPos, $prevPar) {
		$cond = array(
			$model->alias . '.' . $orderBy . ' >' => $prevPos,
			$model->alias . '.' . $model->primaryKey . ' !=' => $model->id,
			$model->alias . '.' . $orderBy . ' <>' => null
		);
		if(!empty($prevPar)) {
			foreach($prevPar as $index => $value) {
				$cond += array($model->alias . '.' . $index => $value);
			}
		}
		// close the gap - move the following records one up
		$this->__moveAffected($model, $move= -1, $cond, $orderBy);
	}
	
	function __moveIn(&$model, $orderBy, $newPos, $newPar, $create = false) {
		if($create) {
			$cond = array(
				$model->alias . '.' . $orderBy . ' >=' => $newPos,
				$model->alias . '.' . $orderBy . ' !=' => null
			);
		}else{
			$cond = array(
				$model->alias . '.' . $orderBy . ' >=' => $newPos,
				$model->alias . '.' . $model->primaryKey . ' !=' => $model->id,
				$model->alias . '.' . $orderBy . ' <>' => null
			);
		}
		if(!empty($newPar)) {
			foreach($newPar as $index => $value) {
				$cond += array($model->alias . '.' . $index => $value);
			}
		}
		// move one down
		$this->__moveAffected($model, $move = 1, $cond, $orderBy);
	}
	
	function __moveAffected(&$model, $move, $cond, $orderBy) {
		if(!empty($move)) {
			$affected = $model->find('all', array(
				'recursive' => -1,
				'conditions' => $cond,
				'order' => array(
					$model->alias . '.' . $orderBy => 'ASC',
					$model->alias . '.' . $model->primaryKey => 'ASC'
				),
				'fields' => array(
					$model->alias . '.' . $model->primaryKey,
					$model->alias . '.' . $orderBy,
					
				)
			));
			if($affected) {
				foreach($affected as &$entry) {
					$entry[$model->alias][$orderBy] = $entry[$model->alias][$orderBy] + $move;
				}
				$model->saveAll($affected, array(
					'atomic' => false,
					'validate' => false
				));
			}
		}
	}
	
	function __checkPositioning(&$model, &$data, &$newPos, $newPar) {
		if(empty($newPos)) {
			if(!$this->settings[$model->alias]['allowNull']) {
				if(!$this->settings[$model->alias]['startZero']) {
					return false;
				}elseif($this->settings[$model->alias]['startZero'] AND (int) $newPos !== 0) {
					return false;
				}
			}else{
				// automatic
				if(!$this->settings[$model->alias]['startZero'] OR (int) $newPos !== 0) {
					if(strtolower($this->settings[$model->alias]['allowNull']) == 'prepend') {
						$newPos = $data[$model->alias][$this->settings[$model->alias]['orderBy']] = 1;
					}
					elseif(strtolower($this->settings[$model->alias]['allowNull']) == 'append') {
						$parentKeys = array();
						if(!empty($newPar)) {
							foreach($newPar as $field => $value) {
								$parentKeys[$model->alias . '.' . $field] = $value;
							}
						}
						$newPos = $data[$model->alias][$this->settings[$model->alias]['orderBy']] = $this->getHighestPosition($model, $updateCache = false, $parentKeys) + 1;
					}
				}
			}
		}
		return true;
	}
	
	
	function afterSave(Model $model, $created, $options = array()) {
		// update the position cache
		if($this->parentHasChanged OR $created) {
			$this->getHighestPosition($model, $updateCache = true, $this->parentKeys);
		}
		if($this->parentHasChanged AND !$created) {
			$this->getHighestPosition($model, $updateCache = true, $this->previousParentKeys);
		}
	}
	
	
	function beforeDelete(Model $model, $cascade = true) {
		$orderBy = $this->settings[$model->alias]['orderBy'];
		$prevPos = $model->field($orderBy);
		$prevPar = $parentKeys = array();
		$parentId = $this->settings[$model->alias]['parentId'];
		
		$model->Behaviors->disable('Sortable');
		
		if(!empty($parentId)) {
			foreach($parentId as $index) {
				$parentKeys[$model->alias . '.' . $index] = $prevPar[$index] = null;
				$parent = $model->field($index);
				if(!empty($parent)) {
					$parentKeys[$model->alias . '.' . $index] = $prevPar[$index] = $parent;
				}
			}
		}
		
		$this->__moveOut($model, $orderBy, $prevPos, $prevPar);
		// update the position cache
		$this->getHighestPosition($model, $updateCache = true, $parentKeys);
		$model->Behaviors->enable('Sortable');
		
		return true;
	}
	
	
	
	/**	Set some default find-options by passing in options via the find-options array. 
	*	If the find options set the rules defined here themselves, they will not be overridden. 
	*	Rules defined in here are only replaced, if the modelAlias, the fieldname and the operator match exactly.
	*	If parentId is set, this method generates a list ordered by parentId first.
	*/
	function beforeFind(Model $model, $query) {
		$orderBy = $this->settings[$model->alias]['orderBy'];
		$parentId = $this->settings[$model->alias]['parentId'];
		$listNull = $this->settings[$model->alias]['listNull'];
		if(strtolower($model->findQueryType) != 'all') {
			$listNull = 'append';
		}
		if(!empty($parentId)) {
			foreach($parentId as $index) {
				$opts['order'][$model->alias . '.' . $index] = 'ASC';
			}
		}
		$opts['order'][$model->alias . '.' . $orderBy] = 'ASC';
		$opts['order'][$model->alias . '.' . $model->primaryKey] = 'ASC';
		if(!$listNull) {
			$cond['conditions'][$model->alias . '.' . $orderBy . ' !='] = null;
			$opts = array_merge($opts, $cond); 
		}
		$this->query = null;
		if(!empty($query)) {
			$this->query = $query;	// keep a copy for afterFind
			foreach($query as $k => $v) {
				if($k == 'order' OR $k == 'conditions') {
					if(!empty($v) AND is_array($v)) {
						foreach($v as $field => $value) {
							if($k == 'order') {
								if(is_array($value)) {
									$opts[$k] = $value;
								}elseif(strpos($value, ' ') !== false) {
									// maintain string syntax, convert to array
									$expl = explode(' ', $value);
									$opts[$k][$expl[0]] = $expl[1];
								}
							}elseif(!empty($value)) $opts[$k][$field] = $value;
							if($k == 'order' AND $value === false) $opts['order'] = array(false);
						}
					}
					unset($query[$k]);
				}else{
					$opts[$k] = $v;
				}
			}
			$opts = array_merge($opts, $query);
		}
		
		return $opts;
	}
	
	/** 
	*	If NULL-position records are to append, the results are being reorganized. 
	*	A sortableOptions array is added to every record, for construction of proper edit forms. 
	*/
	function afterFind(Model $model, $results, $primary = false) {
		$orderBy = $this->settings[$model->alias]['orderBy'];
		$parentId = $this->settings[$model->alias]['parentId'];
		$listNull = $this->settings[$model->alias]['listNull'];
		$return = $results;
		if(strtolower($model->findQueryType) === 'first' AND !empty($results[0])) {
			$parentKeyValues = $sortableOptions = array();
			if(!empty($parentId)) {
				foreach($parentId as $index) {
					if(array_key_exists($index, $results[0][$model->alias])) {
						$parentKeyValues[$model->alias . '.' . $index] = $results[0][$model->alias][$index];
					}
				}
			}
			$sortableOptions = $this->getOptions($model, $parentKeyValues);
			$return[0][$model->alias]['SortableOptions'] = $sortableOptions;
		}
		
		if(strtolower($model->findQueryType) === 'all' AND !empty($results)) {
			$return = $append = array();
			if(!empty($parentId)) {
				foreach($parentId as $index) {
					$varname = 'sortable_parent_index_' . $index;
					$$varname = null;
				}
			}
			$parentKeyValues = $sortableOptions = array();
			$k = 0;
			$newChapter = true;
			foreach($results as $k => &$record) {
				// pretend the records are ordered by parentId first, the order of the parent records is not touched!
				$parentKeyValues = array();
				if(!empty($parentId)) {
					foreach($parentId as $index) {
						$varname = 'sortable_parent_index_' . $index;
						if(array_key_exists($index, $record[$model->alias])) {
							if($$varname != $record[$model->alias][$index]) {
								$newChapter = true;
								$$varname = $record[$model->alias][$index];
							}
							$parentKeyValues[$model->alias . '.' . $index] = $record[$model->alias][$index];
						}
					}
				}
				if($newChapter) {
					// this handles fetching of the positioning form options
					$sortableOptions = $this->getOptions($model, $parentKeyValues);
					$newChapter = false;
				}
				$record[$model->alias]['SortableOptions'] = $sortableOptions;
				if(array_key_exists($orderBy, $record[$model->alias]) AND $record[$model->alias][$orderBy] === null) {
					if($listNull === 'append') {
						$append[] = $record;
					}elseif($listNull === false) {
						// we don't want to see records without position, so skip instead returning them
						continue;
					}else{
						// 'prepend', true, 'anystring'
						$return[] = $record;
					}
				}else{
					$return[] = $record;
				}
			}
			if(!empty($append)) {
				$return = array_merge($return, $append);
			}
		}
		
		return $return;
	}
	
	
	
	/**	This method tries to re-order it's numerical indices according to the order, the list is delivered. 
	*	Reintegrates records without a position (NULL) at the end of a (sub-) list, when allowNull is FALSE. 
	*	Respects the parentId of nested lists. 
	*/
	function fixOrder(&$model, $results = array(), $allowNull = null, $startZero = null) {
		$orderBy = $this->settings[$model->alias]['orderBy'];
		$parentId = $this->settings[$model->alias]['parentId'];
		// only use the default settings, when no parameter has been set
		if($startZero === null) {
			$startZero = $this->settings[$model->alias]['startZero'];
		}
		if($allowNull === null) {
			$allowNull = $this->settings[$model->alias]['allowNull'];
		}
		$return = $results;
		if(!empty($results)) {
			$return = $nulls = $save = array();
			$i = 0;
			if(!$startZero) {
				$i = 1;
			}
			if($parentId) {
				$pass = array();
				foreach($parentId as $index) {
					$varname = 'sortable_parent_index_' . $index;
					$$varname = null;
				}
				foreach($results as $record) {
					// make sure the records are ordered by parentId first!
					$newChapter = false;
					foreach($parentId as $index) {
						$varname = 'sortable_parent_index_' . $index;
						if($$varname != $record[$model->alias][$index]) {
							$newChapter = true;
						}
					}
					if($newChapter) {
						// append and clear the collection of records of the last chapter
						$return = array_merge($return, $this->__fixFlatTreeOrder($model, $pass, $allowNull, $startZero));
						// adjust and reset the counters
						foreach($parentId as $index) {
							$varname = 'sortable_parent_index_' . $index;
							$$varname = $record[$model->alias][$index];
						}
						$pass = array();
					}
					$pass[] = $record;
				}
				if(!empty($pass)) {
					$return = array_merge($return, $this->__fixFlatTreeOrder($model, $pass, $allowNull, $startZero));
				}
				
			}else{
				$return = $this->__fixFlatTreeOrder($model, $results, $allowNull, $startZero);
			}
		}
		
		return $return;
	}
	
	function __fixFlatTreeOrder(&$model, $results = array(), $allowNull = null, $startZero = null) {
		if(empty($results)) return $results;
		
		$orderBy = $this->settings[$model->alias]['orderBy'];
		$parentId = $this->settings[$model->alias]['parentId'];
		// only use the default settings, when no parameter has been set
		if($startZero === null) {
			$startZero = $this->settings[$model->alias]['startZero'];
		}
		if($allowNull === null) {
			$allowNull = $this->settings[$model->alias]['allowNull'];
		}
		$return = $nulls = $save = array();
		$i = 0;
		if(!$startZero) {
			$i = 1;
		}
		
		foreach($results as $k => $record) {
			// check for nulls
			if($record[$model->alias][$orderBy] === null) {
				if($allowNull !== true) {
					$nulls[] = $record;
				}
				continue;
			}
			// check for gaps and double-entries
			if($record[$model->alias][$orderBy] != $i) {
				$record[$model->alias][$orderBy] = $i;
				$save[] = $record;
			}
			$i++;
			$return[] = $record;
		}
		if(!empty($nulls)) {
			if($allowNull == 'append' OR $allowNull == false) {
				foreach($nulls as $record) {
					$record[$model->alias][$orderBy] = $i;
					$save[] = $record;
					$i++;
					$return[] = $record;
				}
			}else{
				// prepend
				$p = 0;
				if(!$startZero) {
					$p = 1;
				}
				$save = array();
				foreach($nulls as $record) {
					$record[$model->alias][$orderBy] = $p;
					$save[] = $record;
					$p++;
					
					$prepended[] = $record;
				}
				foreach($return as $record) {
					$record[$model->alias][$orderBy] = $p;
					$save[] = $record;
					$p++;
				}
				array_unshift($return, $prepended);
			}
		}
		
		$model->Behaviors->disable('Sortable');
		if(!empty($save)) {
			$model->saveAll($save, array(
				'atomic' => false,
				'validate' => false
			));
		}
		$model->Behaviors->enable('Sortable');
		
		return $return;
	}
	
	
}
?>