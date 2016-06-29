<?php
App::uses('AppHelper', 'View/Helper');
class AssetHelper extends AppHelper {
	
	var $helpers = array(
		'Html'
	);
	
	public function afterLayout($layoutFile) {
		if(!$this->request->is('requested') AND Configure::read('Cakeclient.topNav')) {
			$view = $this->_View;
			
			if(empty($view->viewVars['cakeclientCss'])) {
				$view->viewVars['cakeclientCss'][] = 'Cakeclient.top_nav.css';
			}
			
			$head = null;
			if(isset($view->viewVars['cakeclientCss']) && !empty($view->viewVars['cakeclientCss'])) {
				$head .= $this->Html->css($view->viewVars['cakeclientCss']);
			}

			$js = sprintf('window.CAKECLIENT_JQUERY_URL = "%s";', $this->webroot('/cakeclient/js/jquery.js'));
			$head .= $this->Html->scriptBlock($js);

			if(isset($view->viewVars['cakeclientJs'])) {
				foreach ($view->viewVars['cakeclientJs'] as $script) {
					if($script) {
						$head .= $this->Html->script($script);
					}
				}
			}
			if(preg_match('#</head>#', $view->output)) {
				$view->output = preg_replace('#</head>#', $head . "\n</head>", $view->output, 1);
			}
			
			$toolbar = $view->element('layout/top_nav', array(), array('plugin' => 'Cakeclient'));
			if (preg_match('#</body>#', $view->output)) {
				$view->output = preg_replace('#</body>#', $toolbar . "\n</body>", $view->output, 1);
			}
		}
	}
	
}
?>