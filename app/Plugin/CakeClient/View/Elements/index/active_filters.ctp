<?php
if(!empty($filter)) {
	echo '<div class="filter">';
		echo '<p>Filters: (' . $this->Html->link('reset', 'index') . ')</p>';
		echo '<ul>';
			foreach($filter as $fieldname => $value) {
				$fieldname = explode('.', $fieldname);
				if(!empty($fieldname[1])) $fieldname = $fieldname[1];
				echo '<li><span>' . $fieldname . ': </span>' . $value . '</li>';
			}
		echo '</ul>';
	echo '</div>';
}
?>	