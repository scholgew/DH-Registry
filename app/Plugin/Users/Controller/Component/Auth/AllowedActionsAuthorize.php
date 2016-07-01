<?php
App::uses('BaseAuthorize', 'Controller/Component/Auth');
// authorize based on a AuthComponent class variable,
// resembling the AuthComponent::_isAllowed method
class AllowedActionsAuthorize extends BaseAuthorize {
    
	
	public function authorize($user, CakeRequest $request) {
        $allowedActions = $this->_Controller->Auth->allowedActions;
		$action = strtolower($request->params['action']);
		if(in_array($action, array_map('strtolower', $allowedActions))) {
            return true;
        }
        return false;
    }
	
	
	/*
	* When testing against an anonymous user, one needs to pass at least anything
	* to isAuthorized() in order not let it fail, even if Auth::allow() has been called.
	* This is of interest for development on the local machine:
	* if(strpos(APP, 'xampp') !== false AND Configure::read('debug') > 0) {
	* 	$this->Auth->allow();
	* 	debug($this->Auth->isAuthorized($user = 'foo'));
	* }
	* 
	*/
}
?>