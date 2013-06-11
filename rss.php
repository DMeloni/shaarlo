<?php 
error_reporting(0);
header('Content-Type: application/rss+xml; charset=utf-8');
$rssFile = 'rss.xml';
if(is_file($rssFile)){
	echo file_get_contents($rssFile);
}
