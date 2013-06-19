<?php 
error_reporting(0);
header('Content-Type: application/rss+xml; charset=utf-8');

global $DATA_DIR, $CACHE_DIR_NAME, $ARCHIVE_DIR_NAME;
$indexFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'rss.xml');

$rssFile = 'rss.xml';
if(is_file($rssFile)){
	echo file_get_contents($rssFile);
}

