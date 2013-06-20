<!DOCTYPE html><?php 
include 'config.php';
include 'fct/fct_valid.php';
include 'fct/fct_xsl.php';
include 'fct/fct_rss.php';

error_reporting(0);

global $DATA_DIR, $CACHE_DIR_NAME, $ARCHIVE_DIR_NAME;

// Autoredirect on boot.php
$indexFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'index.html');

if(!checkInstall() && !is_file($indexFile)){
	header('Location: boot.php');
	return;
}


header('Content-Type: text/html; charset=utf-8');
if(isset($_GET['date'])){
	$rssFilePath = sprintf('%s/%s/rss_%s.xml', $DATA_DIR, $ARCHIVE_DIR_NAME, $_GET['date']);
	$rssFile = file_get_contents($rssFilePath);
	if(is_file($rssFilePath)) {
		$index = parseXsl('xsl/index.xsl', $rssFile);
		$index = sanitize_output($index);
		echo $index;
	}
}else{
	readfile($indexFile);
}
