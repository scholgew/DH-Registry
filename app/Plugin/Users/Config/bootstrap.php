<?php
/**
* set some defaults
*/
Configure::write('Users.allowRegistration', true);
Configure::write('Users.disableDefaultAuth', false);
Configure::write('Users.emailConfig', 'default');
Configure::write('Users.securitySettings', array(
	'blackHoleCallback' => 'blackHole',
	'csrfCheck' => true
));
Configure::write('Users.userModel', 'Users.User');
// user roles are not actively being used by the plugin, 
// but optionally can be displayd on login_info.ctp
Configure::write('Users.roleModel', 'UserRole');


// extend the App::paths() array in order to make the plugin controllers 
// available to the AclMenuComponent method detection
App::build(array('Controller' => App::path('Controller', 'Users')));
App::build(array('Model' => App::path('Model', 'Users')));
// set some methods *not* to include into the defaultMenu
$usersExcludes = array('login','logout','blackHole','request_new_password',
	'reset_password','register','request_email_verification','verify_email','reset');
if($excludes = Configure::read('AclMenu.excludes'))
	$usersExcludes = array_unique(array_merge($excludes, $usersExcludes));
Configure::write('AclMenu.excludes', $usersExcludes);



/**
* not fully tested
*/
Configure::write('Users.loginName', 'email');	// alternatively: 'username'


/**
* not yet fully implemented:
*/
//Configure::write('Users.showLogin', true);
//Configure::write('Users.rememberMe', false);


/**
* Let Admins approve newly registrated users or not. 
* Set optionally in applicational configuration: the address of the person that has to confirm new registrated accounts. 
* Alternatively, use the database-field 'user_admin' to declare many responsible persons. 
*/
Configure::write('Users.adminConfirmRegistration', false);
Configure::write('Users.newUserAdminNotification', false);
//Configure::write('Users.adminEmailAddress', 'user.admin@example.com');
Configure::write('Users.adminEmailAddress', false);

/**
* This Plugin also uses the following configuration constants:
*/
Configure::write('App.EmailSubjectPrefix', '[Your Site]');


$filename = APP . 'Config' . DS . 'Users' . DS . 'bootstrap.php';
if(file_exists($filename)) {
    include($filename);
}