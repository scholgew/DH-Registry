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
if($get_locations) $locations = $this->requestAction('courses/map');

$markers = array();
// get the markers
if(empty($locations) AND !empty($courses)) $locations = $courses;
if(!empty($locations) AND is_array($locations)) {
	$current_loc = null;
	$sorted = array();
	$i = 0;
	foreach($locations as $k => $record) {
		$current_loc = null;
		if(!empty($record['Course']['lat']) AND !empty($record['Course']['lon']))
			$current_loc = (string)$record['Course']['lat'] . ',' . (string)$record['Course']['lon'];
		if($current_loc) {
			if(!isset($sorted[$current_loc])) $sorted[$current_loc] = array();
			$sorted[$current_loc][] = $record;
		}
	}
	foreach($sorted as $loc => $list) {
		if(count($list) > 1) {
			// generate list marker
			$title = 'Multiple Courses';
			$content = '<h1>Multiple Courses</h1>';
			$content .= '<p>' . $this->Html->link('Click here to see the results for only this location in the table.', array(
				'geolocation' => $loc
			)) . '</p><ul>';
			foreach($list as $j => $record) {
				if($j >= 5) {
					$content .= '<li>... more</li>';
					break;
				}
				$content .= '<li>';
				$content .= $this->Html->link('Details', array(
					'controller' => 'courses',
					'action' => 'index',
					'id' => $record['Course']['id']
				));
				$content .= ' - ' . $record['Course']['name'] . ', ' . $record['Institution']['name'];
				$content .= '</li>';
			}
			$content .= '</ul>';
		}else{
			// a single item marker
			$record = $list[0];
			$title = $record['Course']['name'];
			$content = '<h1>' . $title . '</h1>';
			$content .= '<p>' . $record['Institution']['name'] . ',</p>';
			$content .= '<p>Department: ' . $record['Course']['department'] . '.</p>';
			$content .= '<p>' . $this->Html->link('Details', array(
				'controller' => 'courses',
				'action' => 'index',
				'id' => $record['Course']['id']
			)) . '</p>';
		}
		$marker = array();
		$marker['title'] = str_replace('"', '\\"', str_replace('\\', '\\\\', $title));
		$marker['content'] = str_replace('"', '\\"', str_replace('\\', '\\\\', $content));
		$marker['coordinates'] = array('lat' => $record['Course']['lat'], 'lon' => $record['Course']['lon']);
		$markers[] = $marker;
	}
}
?>

function initializeMap() {
	var mymap = L.map('coursesMap').setView([50.000, 10.189551], 4);
	L.tileLayer('https://api.mapbox.com/styles/v1/hashmich/ciqhed3uq001ae6niop4onov3/tiles/256/{z}/{x}/{y}?access_token=<?php echo Configure::read('App.mapApiKey'); ?>').addTo(mymap);
	
	var group = new L.MarkerClusterGroup({
		spiderfyOnMaxZoom: true,
		showCoverageOnHover: true,
		zoomToBoundsOnClick: true,
		maxClusterRadius: 30
	});
	var locations = JSON.parse('<?php echo json_encode($markers, JSON_HEX_APOS); ?>');
	
	for(var loc in locations) {
		var marker = L.marker([locations[loc].coordinates.lat, locations[loc].coordinates.lon]);
		marker.bindPopup(locations[loc].content);
		group.addLayer(marker);
	}
	mymap.addLayer(group);
	mymap.fitBounds(group.getBounds());
	mymap.zoomIn();
}




