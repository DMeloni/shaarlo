<?php
// include 'auto_restrict.php';
include 'config.php';
include 'fct/fct_rss.php';
include 'fct/fct_cache.php';
include 'fct/fct_file.php';
include 'fct/fct_sort.php';
include 'fct/fct_valid.php';
include 'fct/fct_xsl.php';
error_reporting(0);
$cache = 'index';

$nbStep = 30;
$sleepBeetweenLoops = 110;
global $DATA_DIR, $CACHE_DIR_NAME, $SHAARLIS_FILE_NAME, $POTENTIAL_SHAARLIS_FILE_NAME, $DISABLED_SHAARLIS_FILE_NAME, $NO_HTTPS_SHAARLIS_FILE_NAME, $COMMENT_SORTING;

header('Content-Type: text/html; charset=utf-8');
for($j=0; $j < $nbStep; $j++){
	$actualDate = date('Ymd');
	$actualDateFormat = date('d/m/Y');
	$shaarloRss = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	  <channel>
	    <title>Les discussions de Shaarli du '.$actualDateFormat.'</title>
	    <link>http://shaarli.fr/</link>
	    <description>Shaarli Aggregators</description>
	    <language>fr-fr</language>
	    <copyright>http://shaarli.fr/</copyright>';
		// start profiling
// 		xhprof_enable();
	/**
	 * Absolute path to Item 
	 */
	
	define('XPATH_RSS_ITEM', '/rss/channel/item');
	$rssListFile = sprintf('%s/%s', $DATA_DIR, $SHAARLIS_FILE_NAME);
	
	$potentialShaarlis = array();
	$potentialShaarlisListFile = sprintf('%s/%s', $DATA_DIR, $POTENTIAL_SHAARLIS_FILE_NAME);
	if(is_file($potentialShaarlisListFile)){
		$potentialShaarlis = json_decode(file_get_contents($potentialShaarlisListFile), true);
	}

	if(!is_file($rssListFile)){
		return;
	}
	
	$rssList = json_decode(file_get_contents($rssListFile), true);

	$disabledRssList = array();
	$disabledRssListFile = sprintf('%s/%s', $DATA_DIR, $DISABLED_SHAARLIS_FILE_NAME);
	if(is_file($disabledRssListFile)){
		$disabledRssList = json_decode(file_get_contents($disabledRssListFile), true);
	}

	$noHttpsRssList = array();
	$noHttpsRssListFile = sprintf('%s/%s', $DATA_DIR, $NO_HTTPS_SHAARLIS_FILE_NAME);
	if(is_file($noHttpsRssListFile)){
		$noHttpsRssList = json_decode(file_get_contents($noHttpsRssListFile), true);
	}
	
	
	$deletedRssList = array();
	$deletedRssListFile = sprintf('%s/%s', $DATA_DIR, $DELETED_SHAARLIS_FILE_NAME);
	if(is_file($deletedRssListFile)){
		$deletedRssList = json_decode(file_get_contents($deletedRssListFile), true);
	}

	/*
	 * Save the XML to Array
	 */
	/*
	 * Push the shaarli's id in tab
	*/
	$rssListArrayed = array();
	$assocShaarliIdUrl = array();
	foreach($rssList as $rssKey => $rssUrl){
		$content = getRss($rssUrl);
		$xmlContent = getSimpleXMLElement($content);
		if($xmlContent === false){
			continue;
		}
		$rssListArrayed[$rssKey] = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
	}				
	
	/*
	 * Push the shaarli's id in tab
	 */
	$assocShaarliIdUrl = array();
	foreach($rssListArrayed as $rssKey => $arrayedRss){
		/*
		 * Récupération des liens
		*/
		foreach($arrayedRss as $rssItem){					
			$guid = $rssItem['guid'];
			$link = $rssItem['link'];
			if($link == 'http://'){
				$assocShaarliIdUrl[md5($guid)] = $guid;
			}else{
				$assocShaarliIdUrl[md5($guid)] = $link;
			}
		}
	}
	$rssContents = array();
	foreach($rssListArrayed as $rssKey => $arrayedRss){
		/*
		 * Récupération des liens
		 */
		foreach($arrayedRss as $rssItem){
	// 		array (
	// 		  0 => 'Rss Title',
	// 		  1 => 'http://links.net/links/?mSpC6w',
	// 		  2 => 'http://xxx.xxx', // Rss Url
	// 		  3 => 'Wed, 05 Jun 2013 10:01:53 +0200', // date
	// 		  4 => 'security', // tags
	// 		  5 => 'description')		
	
			/*
			 * Automatic recuperation of linked rss 
			 */
			$link = $rssItem['link'];
			$guid = $rssItem['guid'];
			$category = $rssItem['category'];
			$rssTimestamp = strtotime($rssItem['pubDate']);
			
			$actualTimestamp = time();
			$diffValue = round(($actualTimestamp - $rssTimestamp) / 60);
			$diffUnity = 'minute(s)';
			if($diffValue > 60){
				$diffValue = round($diffValue/ 60);
				$diffUnity = 'heure(s)';
				if($diffValue > 24){
					$diffValue = round($diffValue/ 24);
					$diffUnity = 'jour(s)';
				}	
			}
				
			if($diffValue <= 0){
				$diffValue = 0;
			}
			
			$uniqRssKey = md5($link);
			$description = sprintf('<b>%s</b>, il y a %s %s <br/> %s<br/>', unMagicQuote($rssKey), $diffValue, $diffUnity, str_replace('<br>', '<br/>', $rssItem['description']));
			$title = $rssItem['title'];
			
			// Delete the Shaarli link and replace it by the 'real' link
			if(array_key_exists($uniqRssKey, $assocShaarliIdUrl)){
				$link = $assocShaarliIdUrl[$uniqRssKey];
				$uniqRssKey = md5($assocShaarliIdUrl[$uniqRssKey]);
			}						
			if($link == 'http://'){
				$link = $guid;
			}

			if(!array_key_exists($uniqRssKey, $rssContents) 
			){
				$rssContents[$uniqRssKey] = array('toptopic' => false, 'link' => $link, 'description' => array($rssTimestamp => $description), 'title' => $title, 'date' => $rssTimestamp, 'category' => $category);
			}else{

				$rssContents[$uniqRssKey]['description'][$rssTimestamp] = $description;
				$rssContents[$uniqRssKey]['date'] = max($rssContents[$uniqRssKey]['date'], $rssTimestamp);
				$rssContents[$uniqRssKey]['toptopic'] = true;
			}
		}
	}
	// Obtient une liste de colonnes
	$dateToSort = array();
	foreach ($rssContents as $key => $row) {
		$dateToSort[$key]  = $row['date'];
	}
	
	// Trie les données par volume décroissant, edition croissant
	// Ajoute $data en tant que dernier paramètre, pour trier par la clé commune
	if(count($dateToSort) === count($rssContents)){
		array_multisort($dateToSort, SORT_DESC, $rssContents);
	}
	$limitRss = 100;
	$i=0;
	foreach($rssContents as $rssContent){
		if(date('Ymd', $rssContent['date']) !== $actualDate){
			break;
		}
		
		$i++;

		if('desc' === $COMMENT_SORTING){
			ksort($rssContent['description']);
		}else{
			krsort($rssContent['description']);
		}
		
		$shaarloRss .= sprintf("<item>
							<title>%s</title>
							<link>%s</link>
							<pubDate>%s</pubDate>
							<description>%s</description>
							<category>%s</category>
							</item>",
				htmlspecialchars($rssContent['title']), htmlspecialchars($rssContent['link']), date('r', $rssContent['date']), '<![CDATA[' . implode('<br/>', $rssContent['description']) . ']]>', $rssContent['category']
				);
	}
// 		// stop profiler
// 		$xhprof_data = xhprof_disable();
// 		// display raw xhprof data for the profiler run
// // 		print_r($xhprof_data);
// 		include_once "/var/www/xhprof-master/xhprof_lib/utils/xhprof_lib.php";
// 		include_once "/var/www/xhprof-master/xhprof_lib/utils/xhprof_runs.php";
		
// 		// save raw data for this profiler run using default
// 		// implementation of iXHProfRuns.
// 		$xhprof_runs = new XHProfRuns_Default();
		
// 		// save the run under a namespace "xhprof_foo"
// 		$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");	
// 		echo $run_id;	

	$shaarloRss .= '</channel></rss>';
	
	file_put_contents(sprintf('%s/%s/rss.xml', $DATA_DIR, $CACHE_DIR_NAME), sanitize_output($shaarloRss));
	file_put_contents(sprintf('%s/%s/rss_%s.xml', $DATA_DIR, $ARCHIVE_DIR_NAME, $actualDate), sanitize_output($shaarloRss));
	// Save the potential Shaarlis list
	file_put_contents($potentialShaarlisListFile, json_encode($potentialShaarlis));

	$indexFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'index.html');
	$index = parseXsl('xsl/index.xsl', $shaarloRss);
	$index = sanitize_output($index);
	file_put_contents($indexFile, $index);
		
	if(isset($_GET['oneshoot'])){
		header('Location: index.php');
		return;
	}
// 	return;
	sleep($sleepBeetweenLoops);
}


