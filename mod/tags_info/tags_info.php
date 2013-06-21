<?php 
/**
 * Mod : tags_info
 * Show best tags on admin.php
 * @author : DMeloni
 * no support : at your peril
 */
/*
 * Show best tags on admin.php
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


require_once 'fct/fct_rss.php';
require_once 'fct/fct_xsl.php';


$archiveDir = sprintf('%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME);
$rssFileList = array();
if(!defined('XPATH_RSS_ITEM')){
	define('XPATH_RSS_ITEM', '/rss/channel/item');
}

$fileList =	scandir($archiveDir, 1);
foreach ($fileList as $file ){
	if ($file != "." && $file != "..") {
		sscanf($file, 'rss_%4s%2s%2s.xml', $years, $months, $days);
		$rssFile = file_get_contents(sprintf('%s/%s', $archiveDir, $file));
		$xmlContent = getSimpleXMLElement($rssFile);
		if($xmlContent === false){
			continue;
		}
		$rssFileArrayed = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
		foreach($rssFileArrayed as $item){
			$explodedCategories = explode(',', $item['category']);
			foreach($explodedCategories as $category){
				if(!isset($categories[$category] )){
					$categories[$category] = 0 ;
				}
				$categories[$category]++;
			}		
		}
	}
}
arsort($categories);
$i = 0;
$tags = '';
$stopWords = array('<br/>', '[en]', '[fr]');
foreach($categories as $value => $frequency){
	if(strlen($value) > 3 && !in_array($value, $stopWords)){
		$tags .=  '<a href="index.php?q='.$value.'&amp;type=category">' . $value . '</a> : ' . $frequency . "<br/>";
	}

	if($i > 20){
		break;
	}
	$i++;
}

$MOD['admin.php_top'] .= sprintf('<div class="article shaarli-youm-org">
				<h2 class="article-title ">
				<a title="Go to original place" href="">Top Tags</a>
				</h2>
				<div class="article-content">%s</div>
			</div>', $tags);

