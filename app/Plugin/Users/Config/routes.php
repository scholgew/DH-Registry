<?php
$filename = APP . 'Config' . DS . 'Users' . DS . 'routes.php';
if(file_exists($filename)) {
    include($filename);
}

//Router::connect('/users', array('plugin' => 'users', 'controller' => 'users'));	// yet we don't have an index method
Router::connect('/login', array('plugin' => 'users', 'controller' => 'users', 'action' => 'login'));
Router::connect('/logout', array('plugin' => 'users', 'controller' => 'users', 'action' => 'logout'));
Router::connect('/register', array('plugin' => 'users', 'controller' => 'users', 'action' => 'register'));
Router::connect('/users/add', array('plugin' => 'users', 'controller' => 'users', 'action' => 'register'));
// exclude the edit action to have id available for the CRUD plugin
//Router::connect('/users/edit/*', array('plugin' => 'users', 'controller' => 'users', 'action' => 'profile'));
Router::connect('/users/:except/*', array('plugin' => 'users', 'controller' => 'users', 'action' => ':except'),
	array('except' => '(?!edit)(?!view).*'));
Router::connect('/users/users/:except/*', array('plugin' => 'users', 'controller' => 'users', 'action' => ':except'),
	array('except' => '(?!edit)(?!view).*'));
