<?php
	echo $this->element('relations/parent_models', array(), array('plugin' => 'Cakeclient'));
	echo $this->element('relations/child_models', array(), array('plugin' => 'Cakeclient'));
	echo $this->element('relations/habtm_models', array(), array('plugin' => 'Cakeclient'));
	
	echo $this->element('index/active_filters', array(), array('plugin' => 'Cakeclient'));
	
	echo $this->element('index/bulkprocessor', array(), array('plugin' => 'Cakeclient'));
	echo $this->element('index/pager', array(), array('plugin' => 'Cakeclient'));

	// the actual listing
	echo $this->element('crud/index', array(), array('plugin' => 'Cakeclient'));
	
	echo $this->element('index/bulkprocessor', array(), array('plugin' => 'Cakeclient'));
	echo $this->element('index/pager', array(), array('plugin' => 'Cakeclient'));
?>