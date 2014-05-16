<?php
// include 'auto_restrict.php';
require_once 'config.php';
require_once 'fct/fct_rss.php';
require_once 'fct/fct_cache.php';
require_once 'fct/fct_file.php';
require_once 'fct/fct_sort.php';
require_once 'fct/fct_valid.php';
require_once 'fct/fct_xsl.php';
require_once 'fct/fct_time.php';

error_reporting(0);
$cache = 'index';

$nbStep = 40;
global $REFRESH_SLEEP, $SHAARLO_URL, $DATA_DIR, $CACHE_DIR_NAME, $SHAARLIS_FILE_NAME, $POTENTIAL_SHAARLIS_FILE_NAME, $DISABLED_SHAARLIS_FILE_NAME, $NO_HTTPS_SHAARLIS_FILE_NAME, $COMMENT_SORTING, $ACTIVE_FAVICON, $FAVICON_DIR_NAME;

header('Content-Type: text/html; charset=utf-8');
for($j=0; $j < $nbStep; $j++){
	$actualDate = date('Ymd');
	$actualDateFormat = date('d/m/Y');
	$shaarloRss = $shaarloRssDiff = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">
	  <channel>
	    <title>Les discussions de Shaarli du '.$actualDateFormat.'</title>
	    <link>'.$SHAARLO_URL.'</link>
	    <description>Shaarli Aggregators</description>
	    <language>fr-fr</language>
		<atom:link href="http://shaarli.fr/?do=rss" rel="self" type="application/rss+xml" />
	    <copyright>http://shaarli.fr/</copyright>';
		// start profiling
// 		xhprof_enable();
	/**
	 * Absolute path to Item
	 */

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
		if(is_file(sprintf('%s/rss_%s', $DATA_DIR, $rssKey))){
			$content = file_get_contents(sprintf('%s/rss_%s', $DATA_DIR, $rssKey));
			$xmlContent = getSimpleXMLElement($content);
			if($xmlContent === false){
				continue;
			}
			$rssListArrayed[$rssKey] = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
		}
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
		$imgFavicon = '';
		if('yes' === $ACTIVE_FAVICON ){
			if(!is_dir(sprintf('%s/%s', $DATA_DIR, $FAVICON_DIR_NAME))){
				mkdir(sprintf( '%s/%s', $DATA_DIR, $FAVICON_DIR_NAME));
			}

            $faviconPath = sprintf('%s/%s/%s.ico', $DATA_DIR, $FAVICON_DIR_NAME, $rssKey);
            $shaarliUrl = explode('?', $guid);
            $shaarliUrl = sprintf('%simages/favicon.ico', $shaarliUrl[0]);
            if(urlExists($shaarliUrl)){
                $favicon = @file_get_contents($shaarliUrl);
                if(false !== $favicon){
                    file_put_contents($faviconPath, $favicon);
                }
            }
            if(is_file($faviconPath)){
                $imgFavicon = sprintf('<img alt="" width="16px" height="16px" src="%s" />', $faviconPath);
            }
		}

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
			$category = '';
			if(isset($rssItem['category'])) {
				$category = $rssItem['category'];
			}
			
			$link = $rssItem['link'];
			$guid = $rssItem['guid'];
			$rssTimestamp = strtotime($rssItem['pubDate']);

			if($link == 'http://'){
				$link = $guid;
			}

            $link = str_replace('//?', '/?', $link);


			$uniqRssKey = md5($link);

			// Detect if another same link have an adding '/' character (last position)
			if($link[strlen($link) - 1] !== '/'){
				$slashLink = $link . '/';
				$uniqSlashRssKey = md5($slashLink);
				if(!array_key_exists($uniqRssKey, $rssContents) && array_key_exists($uniqSlashRssKey, $rssContents)){
					$link = $slashLink;
					$uniqRssKey = md5($link);
					$uniqRssKey = $uniqSlashRssKey;
				}
			}else{
				$unSlashLink = substr($link, 0, strlen($link) - 1);
				$uniqUnslashRssKey = md5($unSlashLink);
				if(!array_key_exists($uniqRssKey, $rssContents) && array_key_exists($uniqUnslashRssKey, $rssContents)){
					$link = $unSlashLink;
					$uniqRssKey = $uniqUnslashRssKey;
				}
			}

			/*
			 * Add forced Permalink if not found
			 */
			if(false === mb_stripos($rssItem['description'],sprintf('<a href="%s">Permalink</a>', $guid))){
				$rssItem['description'].= sprintf('<br/>(<a href="%s">Permalink</a>)', $guid);
			}

			$descriptionDiff = sprintf('%s <b>%s</b>%s<br/> %s<br/>', $imgFavicon, unMagicQuote($rssKey), time_elapsed_string($rssTimestamp), str_replace('<br>', '<br/>', $rssItem['description']));
			$description = sprintf('%s <b>%s</b>, le %s <br/> %s<br/>', $imgFavicon, unMagicQuote($rssKey), date('d/m/Y \à H:i', $rssTimestamp), str_replace('<br>', '<br/>', $rssItem['description']));

			$title = $rssItem['title'];

			// Delete the Shaarli link and replace it by the 'real' link
			if(array_key_exists($uniqRssKey, $assocShaarliIdUrl)){
				$link = $assocShaarliIdUrl[$uniqRssKey];
				$uniqRssKey = md5($assocShaarliIdUrl[$uniqRssKey]);
			}

			if(!array_key_exists($uniqRssKey, $rssContents)
			){
				$rssContents[$uniqRssKey] = array('toptopic' => false, 'link' => $link, 'description' => array($rssTimestamp => $description), 'descriptionDiff' => array($rssTimestamp => $descriptionDiff), 'title' => $title, 'date' => $rssTimestamp, 'category' => $category);
			}else{
				$rssContents[$uniqRssKey]['description'][$rssTimestamp] = $description;
				$rssContents[$uniqRssKey]['descriptionDiff'][$rssTimestamp] = $descriptionDiff;
				$rssContents[$uniqRssKey]['date'] = max($rssContents[$uniqRssKey]['date'], $rssTimestamp);
				$rssContents[$uniqRssKey]['toptopic'] = true;
                $rssContents[$uniqRssKey]['category'] .= ',' . $category;
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

        if(strpos($rssContent['category'], ',') > 0) {
            $categoryArray = explode(',', $rssContent['category']);
            natsort($categoryArray);
            $categoryArray = array_unique($categoryArray);
            $category = implode(',', $categoryArray);
        }else{
            $category = $rssContent['category'];
        }

		if('desc' === $COMMENT_SORTING){
			ksort($rssContent['description']);
			ksort($rssContent['descriptionDiff']);
		}else{
			krsort($rssContent['description']);
			krsort($rssContent['descriptionDiff']);
		}

		$shaarloRss .= sprintf("<item>
							<title>%s</title>
							<link>%s</link>
							<guid>%s</guid>
							<pubDate>%s</pubDate>
							<description>%s</description>
							<category>%s</category>
							</item>",
				htmlspecialchars($rssContent['title']), htmlspecialchars($rssContent['link']), htmlspecialchars($rssContent['link']), date('r', $rssContent['date']), '<![CDATA[' . implode('<br/>', $rssContent['description']) . ']]>', htmlspecialchars($category)
				);
		$shaarloRssDiff .= sprintf("<item>
				<title>%s</title>
				<link>%s</link>
				<guid>%s</guid>
				<pubDate>%s</pubDate>
				<description>%s</description>
				<category>%s</category>
				</item>",
				htmlspecialchars($rssContent['title']), htmlspecialchars($rssContent['link']), htmlspecialchars($rssContent['link']), date('r', $rssContent['date']), '<![CDATA[' . implode('<br/>', $rssContent['descriptionDiff']) . ']]>', htmlspecialchars($category)
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
	$shaarloRssDiff .= '</channel></rss>';

	file_put_contents(sprintf('%s/%s/rssDiff.xml', $DATA_DIR, $CACHE_DIR_NAME), sanitize_output($shaarloRssDiff));
	file_put_contents(sprintf('%s/%s/rss.xml', $DATA_DIR, $CACHE_DIR_NAME), sanitize_output($shaarloRss));
	$actualDate = date('Ymd');
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
	sleep($REFRESH_SLEEP);
}


