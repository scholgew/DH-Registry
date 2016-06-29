<?php
	// functions suggested to include into application controllers
	
	
	protected function _getMailInstance() {
		App::uses('CakeEmail', 'Network/Email');
		$emailConfig = Configure::read('Users.emailConfig');
		if($emailConfig) {
			return new CakeEmail($emailConfig);
		}else{
			return new CakeEmail('default');
		}
	}
	
	
	// to be extended - the SecurityComponent blackHole Callback on application side
	public function blackHole($type = null) {
		switch($type) {
			case 'secure':
				if($this->action != 'blackHole') {
					return $this->redirect('https://' . env('SERVER_NAME') . $this->here);
				}
			default:
				throw new BadRequestException(__d('cake_dev', 'The request has been black-holed'));
		}
	}
	
	/**
	* Some google scripts (google map scripts) need special handling when using SSL:
	* https://developers.google.com/maps/documentation/javascript/tutorial?hl=de
	* As a workaround, requireNonSecure(array('actionName')) in the beforeFilter method. 
	*/
	function requireNonSecure() {
		$requireNonSecure = array_map('strtolower', func_get_args());
		
		if($this->action == 'requireNonSecure') {
			throw new BadRequestException(__d('cake_dev', 'The request has been black-holed'));
		}

		if(in_array(strtolower($this->action), $requireNonSecure) || $requireNonSecure == array('*')) {
			if ($this->RequestHandler->isSSL()) {
				$this->redirect('http://' . rtrim(env('SERVER_NAME'), '/') . $this->here);
				return;
			}
		}
	}
	
?>