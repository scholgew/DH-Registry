<?php
	if($this->fetch('onload')) {
		?>
		<script type="text/javascript">
			window.onload = function() {
				<?php echo $this->fetch('onload'); ?>
			}
		</script>
		<?php
	}
?>