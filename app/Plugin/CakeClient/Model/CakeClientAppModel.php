<?php
class CakeclientAppModel extends AppModel {
	
	var $recursive = 0;
	
	// CrudComponent sets this property to the name of the CRUD method in effect
	// to indicate that generic Crud code handles the request
	var $crud = false;
	
	var $actsAs = array(
		'Cakeclient.Configurable',
		'Containable'
	);
	
	public function afterSave($created, $options = array()) {
		// move this to affected models later on.
		Cache::clear($check_expiry = false, 'cakeclient');
	}
	
	
	public function makeTableLabel($tablename = null, $prefix = null) {
		$label = $tablename;
		if($prefix) $label = str_replace($prefix, '', $label);
		return $label = Inflector::camelize($label);
	}
	
	
	/*
	* If we are examining a plugin class, get the according App-class - if any
	*/
	public function getAppClass($className = null, $classType = null, &$virtual = false, &$plugin = false, &$pluginAppOverride = false) {
		if(empty($className) OR empty($classType)) return null;
		
		App::uses($className, $classType);
		if(!class_exists($className, true)) {
			$virtual = true;
			return $className;
		}
		$reflector = new ReflectionClass($className);
		$dir = dirname($reflector->getFileName());
		$pluginName = null;
		unset($reflector);
		if(strpos($dir, 'Plugin')) {
			$plugin = true;
			$expl = explode(DS, $dir);
			foreach($expl as $k => $d) if($d == 'Plugin') $pluginName = $expl[$k+1];
			// test for an app-level override
			$_className = 'App'.$className;
			App::uses($_className, $classType);
			if(class_exists($_className, true)) {
				$pluginAppOverride = true;
				$className = $_className;
			}
		}
		
		return $className;
	}
	
	
	
	
}
?>