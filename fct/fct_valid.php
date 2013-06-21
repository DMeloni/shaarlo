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

/**
 * Check installation
 * @return boolean
 */
function checkInstall(){
	global $DATA_DIR, $CACHE_DIR_NAME, $ARCHIVE_DIR_NAME, $ARCHIVE_FILE_NAME, $SHAARLIS_FILE_NAME;
	if(!is_dir($DATA_DIR) 
	|| !is_dir(sprintf('%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME))
	|| !is_dir(sprintf('%s/%s', $DATA_DIR, $CACHE_DIR_NAME))
	){
		return false;
	}	
	
	$rssListFile = sprintf('%s/%s', $DATA_DIR, $SHAARLIS_FILE_NAME);
	if(!is_file($rssListFile)){
		return false;
	}

	if(is_file($rssListFile)){
		$rssList = json_decode(file_get_contents($rssListFile), true);
		if(empty($rssList)){
			return false;
		}
	}
	
	return true;
}
