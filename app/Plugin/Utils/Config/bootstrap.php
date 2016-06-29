<?php
	
	function getInstallationSubDir() {
		$doc_root = explode('/', $_SERVER['DOCUMENT_ROOT']);
		$app_root = explode(DS, APP);
		foreach($app_root as $k => $v) {
			if(in_array($v, $doc_root)) unset($app_root[$k]);
			else break;
		}
		$cut = false;
		foreach($app_root as $k => $v) {
			if($v == 'app') $cut = true;
			if($cut) unset($app_root[$k]);
		}
		$subDir = '/' . implode('/', $app_root);
		return $subDir;
	}
	
	
	function fieldnameSplit($key, &$fieldname = null, &$fieldModelName = null) {
		if(!empty($key)) {
			$fieldname = $key;
			$expl = explode('.', $key);
			$fieldname = $expl[0];
			if(isset($expl[1])) {
				$fieldModelName = $expl[0];
				$fieldname = $expl[1];
			}
		}
	}
	
	
?>