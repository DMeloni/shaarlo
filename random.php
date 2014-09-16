<?php 
include 'config.php';
include 'fct/fct_valid.php';
require_once 'fct/fct_xsl.php';
require_once 'fct/fct_rss.php';

error_reporting(0);


global $SHAARLO_URL, $DATA_DIR, $CACHE_DIR_NAME, $ARCHIVE_DIR_NAME, $MAX_FOUND_ITEM, $MOD, $ACTIVE_WOT, $MY_SHAARLI_FILE_NAME, $MY_RESPAWN_FILE_NAME, $ACTIVE_NEXT_PREVIOUS;

// Autoredirect on boot.php
$indexFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'index.html');

if(!checkInstall() && !is_file($indexFile)){
	header('Location: boot.php');
	return;
}

/*
 * Shaarli & respawn Url
 */
$myShaarli = array();
$myShaarliUrl = '';
$myShaarliFile = sprintf('%s/%s', $DATA_DIR, $MY_SHAARLI_FILE_NAME);
if(is_file($myShaarliFile)){
	$myShaarli = json_decode(file_get_contents($myShaarliFile), true);
	$myShaarliUrl = reset($myShaarli);
}

$myRespawnUrl = array();
$myRespawnUrl = '';
$myRespawnFile = sprintf('%s/%s', $DATA_DIR, $MY_RESPAWN_FILE_NAME);
if(is_file($myRespawnFile)){
	$myRespawn = json_decode(file_get_contents($myRespawnFile), true);
	$myRespawnUrl = reset($myRespawn);
}

$archiveDir = sprintf('%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME);
$fileList =	scandir($archiveDir, 1);
//. and .. suppression
array_pop($fileList);
array_pop($fileList);
$nbDisplayedArticles = 50;

/*
 * Items randomization
 */
$items = array();
for($i=0 ; $i < $nbDisplayedArticles ; $i++){
	$randomFile = rand(0, count($fileList) - 1);
	$file = $fileList[$randomFile];
	$rssFile = file_get_contents(sprintf('%s/%s', $archiveDir, $file));
	$xmlContent = getSimpleXMLElement($rssFile);
	if($xmlContent === false){
		continue;
	}
	$rssFileArrayed = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
	$randomItem = rand(0, count($rssFileArrayed) - 1);
	
	$item = $rssFileArrayed[$randomItem];
	$items[] = $item;
}
    $nbSessions = null;    
    if(isset($_SESSION['username'])){
        $myShaarliUrl = htmlentities(sprintf('http://my.shaarli.fr/%s/', $_SESSION['username']));
    }
/*
 * Rss construction
 */
$shaarloRss = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	  <channel>
	    <title>Articles au hasard</title>
	    <link>http://shaarli.fr/</link>
	    <description>Shaarli Aggregators</description>
	    <language>fr-fr</language>
	    <copyright>http://shaarli.fr/</copyright>';
foreach($items as $item){
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
            htmlspecialchars($item['category'])
	);
}
$shaarloRss .= '</channel></rss>';
/*
 * Index construction
 */
$index = parseXsl('xsl/index.xsl', $shaarloRss, array( 'no_description' => $_GET['nodesc'], 'nb_sessions' => $nbSessions, 'filtre_popularite' => 0, 'next_previous' => $ACTIVE_NEXT_PREVIOUS, 'rss_url' => $SHAARLO_URL, 'wot' => $ACTIVE_WOT, 'my_shaarli' => $myShaarliUrl,  'my_respawn' => $myRespawnUrl));
$index = sanitize_output($index);
echo $index;


