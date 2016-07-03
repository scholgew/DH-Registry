Hello Admin, 

a new user has registered and is waiting for your approval of the new account!

<?php
if(!empty($user[$model])) {
	echo "Details: \n\n";
	foreach($user[$model] as $fieldname => $value) {
		echo str_pad($fieldname . ':', 24, " ") . "			" . $value . "\n";
	}
	echo "\n\n";
	echo "Click here for instant approval: \n";
	
	echo Router::url(array(
		'admin' => false,
		'plugin' => 'users',
		'controller' => 'users',
		'action' => 'approve',
		$user[$model]['approval_token']
	), $full = true);
}
?>
