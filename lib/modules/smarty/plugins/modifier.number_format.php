<?php
function smarty_modifier_number_format($string) {
	return(number_format($string, 0, '.', ' '));
}
?>
