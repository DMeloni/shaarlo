<?php 
error_reporting(0);
include 'config.php';
header('Content-Type: application/rss+xml; charset=utf-8');

global $DATA_DIR, $CACHE_DIR_NAME;
$rssFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'rss.xml');
if(is_file($rssFile)){
	echo file_get_contents($rssFile);
}

