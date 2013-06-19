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

/**
 * Eq : php_flag magic_quotes_gpc Off
 */
function unRequestMagicQuote(){
	if (get_magic_quotes_gpc()) {
		$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
		while (list($key, $val) = each($process)) {
			foreach ($val as $k => $v) {
				unset($process[$key][$k]);
				if (is_array($v)) {
					$process[$key][stripslashes($k)] = $v;
					$process[] = &$process[$key][stripslashes($k)];
				} else {
					$process[$key][stripslashes($k)] = stripslashes($v);
				}
			}
		}
		unset($process);
	}
}