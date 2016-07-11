<?php
/**
 * Copyright 2014 Hendrik Schmeer on behalf of DARIAH-EU, VCC2 and DARIAH-DE,
 * Credits to Erasmus University Rotterdam, University of Cologne, PIREH / University Paris 1
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
 
 
 
 
$this->Html->css('/leaflet/leaflet.css', array('inline' => false));
$this->Html->script('/leaflet/leaflet.js', array('inline' => false));

$this->Html->css('/leaflet/MarkerCluster.css', array('inline' => false));
$this->Html->css('/leaflet/MarkerCluster.Default.css', array('inline' => false));
$this->Html->script('/leaflet/leaflet.markercluster.js', array('inline' => false));
?>

<div id="coursesMap"></div>


<?php
// rendering the initializeMap js method based on the present courses - optionally fetching from cache

// to disable element cache, the key "cache" must not be present in the options array
$options = array();
$get_locations = false;
// locations will be (bool)false if empty because of set filters
if($this->action == 'index' AND !isset($locations)) {
	$options = array('cache' => true);
	$get_locations = true;
}
$this->Html->scriptBlock(
	$this->element('courses/initialize_map', array('get_locations' => $get_locations), $options),
	array('inline' => false)
);



$this->append('onload', 'var map = initializeMap();');
?>