<?php
function smarty_modifier_urlencode($string) {
	return urlencode(iconv("utf-8", "windows-1251", $string));
}
