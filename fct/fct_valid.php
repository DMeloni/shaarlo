<?php 

/**
 * Eq : php_flag magic_quotes_gpc Off
 */
function unMagicQuote($var){
	if (get_magic_quotes_gpc()) {
		return stripslashes($var);
	}
	return $var;
}
