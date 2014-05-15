<?php
// include 'auto_restrict.php';
require_once 'config.php';
require_once 'fct/fct_rss.php';
require_once 'fct/fct_cache.php';
require_once 'fct/fct_file.php';
require_once 'fct/fct_sort.php';
require_once 'fct/fct_valid.php';
require_once 'fct/fct_xsl.php';
require_once 'fct/fct_time.php';

error_reporting(0);
$cache = 'index';

$nbStep = 20;
global $REFRESH_SLEEP, $SHAARLO_URL, $DATA_DIR, $CACHE_DIR_NAME, $SHAARLIS_FILE_NAME, $POTENTIAL_SHAARLIS_FILE_NAME, $DISABLED_SHAARLIS_FILE_NAME, $NO_HTTPS_SHAARLIS_FILE_NAME, $COMMENT_SORTING, $ACTIVE_FAVICON, $FAVICON_DIR_NAME;

header('Content-Type: text/html; charset=utf-8');
for($j=0; $j < $nbStep; $j++){
	$actualDate = date('Ymd');
	$actualDateFormat = date('d/m/Y');
	$shaarloRss = $shaarloRssDiff = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	  <channel>
	    <title>Les discussions de Shaarli du '.$actualDateFormat.'</title>
	    <link>'.$SHAARLO_URL.'</link>
	    <description>Shaarli Aggregators</description>
	    <language>fr-fr</language>
	    <copyright>http://shaarli.fr/</copyright>';
		// start profiling
// 		xhprof_enable();
	/**
	 * Absolute path to Item 
	 */
	
	$rssListFile = sprintf('%s/%s', $DATA_DIR, $SHAARLIS_FILE_NAME);
	
	$potentialShaarlis = array();
	$potentialShaarlisListFile = sprintf('%s/%s', $DATA_DIR, $POTENTIAL_SHAARLIS_FILE_NAME);
	if(is_file($potentialShaarlisListFile)){
		$potentialShaarlis = json_decode(file_get_contents($potentialShaarlisListFile), true);
	}

	if(!is_file($rssListFile)){
		return;
	}
	
	$rssList = json_decode(file_get_contents($rssListFile), true);

	$disabledRssList = array();
	$disabledRssListFile = sprintf('%s/%s', $DATA_DIR, $DISABLED_SHAARLIS_FILE_NAME);
	if(is_file($disabledRssListFile)){
		$disabledRssList = json_decode(file_get_contents($disabledRssListFile), true);
	}

	$noHttpsRssList = array();
	$noHttpsRssListFile = sprintf('%s/%s', $DATA_DIR, $NO_HTTPS_SHAARLIS_FILE_NAME);
	if(is_file($noHttpsRssListFile)){
		$noHttpsRssList = json_decode(file_get_contents($noHttpsRssListFile), true);
	}
	
	
	$deletedRssList = array();
	$deletedRssListFile = sprintf('%s/%s', $DATA_DIR, $DELETED_SHAARLIS_FILE_NAME);
	if(is_file($deletedRssListFile)){
		$deletedRssList = json_decode(file_get_contents($deletedRssListFile), true);
	}

	/*
	 * Save the XML to Array
	 */
	/*
	 * Push the shaarli's id in tab
	*/
	$rssListArrayed = array();
	$assocShaarliIdUrl = array();
	foreach($rssList as $rssKey => $rssUrl){
		$content = getRss($rssUrl);
		$xmlContent = getSimpleXMLElement($content);
		if($xmlContent === false || empty($content)){
			continue;
		}
		file_put_contents(sprintf('%s/rss_%s', $DATA_DIR, $rssKey), $content);
	}
	echo 'cououc';
        if(isset($_GET['oneshoot'])){
		echo 'lol';
		header('Location: refreshdiscuss.php?oneshoot=true');
		return;
        }
	sleep($REFRESH_SLEEP);
}


