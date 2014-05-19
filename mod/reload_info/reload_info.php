<?php 
/**
 * Mod : tags_info
 * Add refresh info on admin.php
 * @author : DMeloni
 * no support : at your peril
 */
include 'config.php';

/*
 * Mod filter
*/
$modActivedOnPages = array('admin.php');
$path = $_SERVER['PHP_SELF'];
$file = basename ($path);
if(!in_array($file, $modActivedOnPages)){
	return ;
}
global $MOD, $DATA_DIR, $ARCHIVE_DIR_NAME;

$date = date('Ymd');
$currentRss = sprintf('%s/%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME, 'rss_'.date('Ymd'). '.xml');
if(is_file($currentRss)){
	$mtimeLastReload = date('d/m/Y \à H\h i\m s\s', filemtime($currentRss));
}else{
	$mtimeLastReload = str_replace(array('rss_','.xml'), '', readMyDir($DATA_DIR.DIRECTORY_SEPARATOR.$ARCHIVE_DIR_NAME));
	$mtimeLastReload = preg_replace_callback(
			"|([0-9]{4})([0-9]{2})([0-9]{2})|",
			function ($matches) {return $matches[3].'/'.$matches[2].'/'.$matches[1];},
			$mtimeLastReload
	);
}

$MOD['admin.php_top'] .= sprintf('<div class="article shaarli-youm-org">
				<h2 class="article-title ">
				<a title="Go to original place" href="">Info sur le dernier rafraîchissement</a>
				</h2>
				<div class="article-content">
					<span>Dernière mise à jour le : %s</span>
					<br/>
					<a href="refresh.php?oneshoot=true">Forcer un rafraîchissement</a>		
				</div>	
			</div>', $mtimeLastReload);
