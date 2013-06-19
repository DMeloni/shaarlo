<!DOCTYPE html><?php 
include 'config.php';
include 'fct/fct_valid.php';

error_reporting(0);

global $DATA_DIR, $CACHE_DIR_NAME, $ARCHIVE_DIR_NAME;
$indexFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'index.html');

// Autoredirect on boot.php
if(!checkInstall() || !is_file($indexFile)){
	header('Location: boot.php');
	return;
}

header('Content-Type: text/html; charset=utf-8');
readfile($indexFile);
