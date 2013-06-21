<?php 
include 'config.php';
include 'fct/fct_valid.php';
require_once 'fct/fct_xsl.php';
require_once 'fct/fct_rss.php';

error_reporting(0);

global $DATA_DIR, $CACHE_DIR_NAME, $ARCHIVE_DIR_NAME, $MAX_FOUND_ITEM, $MOD;

// Autoredirect on boot.php
$indexFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'index.html');

if(!checkInstall() && !is_file($indexFile)){
	header('Location: boot.php');
	return;
}

if(isset($_GET['q']) && !empty($_GET['q'])){
	$archiveDir = sprintf('%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME);
	$rssFileList = array();
	$searchTerm = $_GET['q'];
	$type = 'fulltext';
	if(isset($_GET['type']) && $_GET['type'] === 'category'){
		$type = 'category';
	}
	if(!defined('XPATH_RSS_ITEM')){
		define('XPATH_RSS_ITEM', '/rss/channel/item');
	}
	$found = array();
	$linkAlreadyFound = array();
	$nbFoundItems = 0;
	$fileList =	scandir($archiveDir, 1);
	foreach ($fileList as $file ) {
		if ($file != "." && $file != "..") {
			sscanf($file, 'rss_%4s%2s%2s.xml', $years, $months, $days);
			$rssFile = file_get_contents(sprintf('%s/%s', $archiveDir, $file));
			$xmlContent = getSimpleXMLElement($rssFile);
			if($xmlContent === false){
				continue;
			}
			$rssFileArrayed = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
			foreach($rssFileArrayed as $item){
				if((mb_stripos(strip_tags($item['description']),($searchTerm))!==false && ('fulltext' === $type || 'description' == $type))
				|| (mb_stripos($item['link'],$searchTerm)!==false && ('fulltext' === $type || 'link' == $type))
				|| (mb_stripos($item['title'],$searchTerm)!==false && ('fulltext' === $type || 'title' == $type))
				|| (mb_stripos($item['category'],$searchTerm)!==false && 'fulltext' === $type) 
				|| ((preg_match("#,$searchTerm,#", $item['category']) || preg_match("#^$searchTerm,#", $item['category'])/*Very ugly*/
						|| preg_match("#,$searchTerm$#", $item['category']))&& 'category' === $type)
				){
					if(!array_key_exists($item['link'], $linkAlreadyFound) 
					|| $linkAlreadyFound[$item['link']] < strlen($item['description'])
					){
						$linkAlreadyFound[$item['link']] = strlen($item['description']); 
						$found[$item['link']] = $item;
						$nbFoundItems++;
					}
				}
			}
			if($nbFoundItems >= $MAX_FOUND_ITEM){
				break;
			}
		}
	}
	
	// Obtient une liste de colonnes
	$dateToSort = array();
	foreach ($found as $key => $row) {
		$dateToSort[$key]  = strtotime($row['pubDate']);
	}
	
	// Trie les données par volume décroissant, edition croissant
	// Ajoute $data en tant que dernier paramètre, pour trier par la clé commune
	if(count($dateToSort) === count($found)){
		array_multisort($dateToSort, SORT_DESC, $found);
	}
	
	$shaarloRss = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	  <channel>
	    <title>Recherche des liens :  '. htmlspecialchars($searchTerm) .'</title>
	    <link>http://shaarli.fr/</link>
	    <description>Shaarli Aggregators</description>
	    <language>fr-fr</language>
	    <copyright>http://shaarli.fr/</copyright>';
	foreach($found as $item){
		$shaarloRss .= sprintf("<item>
								<title>%s</title>
								<link>%s</link>
								<pubDate>%s</pubDate>
								<description>%s</description>
								<category>%s</category>
								</item>",
				htmlspecialchars($item['title']), 
				htmlspecialchars($item['link']), 
				$item['pubDate'],
				htmlspecialchars($item['description']), 
				$item['category']
		);	
	}
	$shaarloRss .= '</channel></rss>';
	
	if(isset($_GET['do']) && $_GET['do'] === 'rss'){
		header('Content-Type: application/rss+xml; charset=utf-8');
		echo sanitize_output($shaarloRss);
	}else{
		$index = parseXsl('xsl/index.xsl', $shaarloRss, array('searchTerm' => $_GET['q'], 'mod_content_top' => $MOD[basename($_SERVER['PHP_SELF'].'_top')]));
		$index = sanitize_output($index);
		header('Content-Type: text/html; charset=utf-8');
		echo $index;
	}
}else{
	if(isset($_GET['date']) && is_file($rssFilePath = sprintf('%s/%s/rss_%s.xml', $DATA_DIR, $ARCHIVE_DIR_NAME, $_GET['date']))){
		$rssFilePath = sprintf('%s/%s/rss_%s.xml', $DATA_DIR, $ARCHIVE_DIR_NAME, $_GET['date']);
		$rssFile = file_get_contents($rssFilePath);
		if(is_file($rssFilePath)) {
			$index = parseXsl('xsl/index.xsl', $rssFile, array('mod_content_top' => $MOD[basename($_SERVER['PHP_SELF'].'_top')]));
			$index = sanitize_output($index);
			header('Content-Type: text/html; charset=utf-8');
			echo $index;
		}
	}else{
		if(is_file($indexFile)){
			header('Content-Type: text/html; charset=utf-8');
			$rssFilePath = sprintf('%s/%s/rss.xml', $DATA_DIR, $CACHE_DIR_NAME);
			$rssFile = file_get_contents($rssFilePath);
			$index = parseXsl('xsl/index.xsl', $rssFile, array('mod_content_top' => $MOD[basename($_SERVER['PHP_SELF'].'_top')]));
			$index = sanitize_output($index);
			echo $index;			
		}else{
			header('Location: refresh.php?oneshoot=true');
		}
	}
}


