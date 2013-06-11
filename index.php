<?php 
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');
$rssFile = 'index';
if(is_file($rssFile)){
	echo file_get_contents($rssFile);
}
