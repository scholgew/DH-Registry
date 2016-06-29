<?php
	if(!empty($menu)) {
		echo '<ul>';
		foreach($menu as $item) {
			echo '<li>';
			if(!empty($item['url'])) {
				echo $this->Html->link($item['title'], $item['url']);
			}else{
				echo $item['title'];
			}
			if(!empty($item['submenu'])) {
				echo '<ul>';
				foreach($item['submenu'] as $subItem) {
					echo '<li>' . $this->Html->link($subItem['title'], $subItem['url']) . '</li>';
				}
				echo '</ul>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}
?>