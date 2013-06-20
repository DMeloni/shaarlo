<!DOCTYPE html><?php 
include 'config.php';
include 'fct/fct_valid.php';
include 'fct/fct_xsl.php';
include 'fct/fct_rss.php';

error_reporting(0);

global $DATA_DIR, $CACHE_DIR_NAME, $ARCHIVE_DIR_NAME, $MAX_FOUND_ITEM;

// Autoredirect on boot.php
$indexFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'index.html');

if(!checkInstall() && !is_file($indexFile)){
	header('Location: boot.php');
	return;
}


header('Content-Type: text/html; charset=utf-8');

if(isset($_GET['q'])){
	$archiveDir = sprintf('%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME);
	$rssFileList = array();
	$searchTerm = $_GET['q'];
	define('XPATH_RSS_ITEM', '/rss/channel/item');
	$found = array();
	if ($handle = opendir($archiveDir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				sscanf($file, 'rss_%4s%2s%2s.xml', $years, $months, $days);
				$rssFile = file_get_contents(sprintf('%s/%s', $archiveDir, $file));
				$xmlContent = getSimpleXMLElement($rssFile);
				if($xmlContent === false){
					continue;
				}
				$rssFileArrayed = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
				$nbFoundItems = 0;
				foreach($rssFileArrayed as $item){
					if((strpos(strtolower($item['description']),strtolower($searchTerm))!==false)
					|| (strpos(strtolower($item['link']),strtolower($searchTerm))!==false)
					|| (strpos(strtolower($item['title']),strtolower($searchTerm))!==false)
					|| (strpos(strtolower($item['category']),strtolower($searchTerm))!==false)
					){
						$found[] = $item;
						$nbFoundItems++;
					}
				}
				if($nbFoundItems >= $MAX_FOUND_ITEM){
					break;
				}
			}
		}
		closedir($handle);
	}
	$shaarloRss = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	  <channel>
	    <title>Recherche des liens :  '. $searchTerm .'</title>
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
				$item['title'], $item['link'], $item['pubDate'],$item['description'], $item['category']
		);	
	}	
	$shaarloRss .= '</channel></rss>';
	$index = parseXsl('xsl/index.xsl', $shaarloRss);
	$index = sanitize_output($index);
	echo $index;
		
}else{
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
}


