<?php
include 'fct/fct_rss.php';
include 'fct/fct_cache.php';
include 'fct/fct_file.php';
include 'fct/fct_sort.php';
error_reporting(0);
$cache = 'index';

$nbStep = 30;
$sleepBeetweenLoops = 110;

function sanitize_output($buffer)
{
    $search = array(
        '/\>[^\S ]+/s', //strip whitespaces after tags, except space
        '/[^\S ]+\</s', //strip whitespaces before tags, except space
        '/(\s)+/s'  // shorten multiple whitespace sequences
        );
    $replace = array(
        '>',
        '<',
        '\\1'
        );
    $buffer = preg_replace($search, $replace, $buffer);

    return $buffer;
}


header('Content-Type: text/html; charset=utf-8');
for($j=0; $j < $nbStep; $j++){
	$shaarloRss = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	  <channel>
	    <title>Shaarlo</title>
	    <link>http://shaarli.fr/</link>
	    <description>Shaarli Aggregators</description>
	    <language>fr-fr</language>
	    <copyright>http://shaarli.fr/</copyright>';
	ob_start();
	?><!DOCTYPE html>
		<html lang="fr">
		    <head>
		            <title>Shaarlo</title>
					<meta charset="utf-8">
		            <meta name="description" content="">
		            <meta name="author" content="">
		            <link rel="shortcut icon" href="favicon.ico">
		            <link rel="stylesheet" href="css/style.css" type="text/css"  media="screen">
		            <link rel="alternate" type="application/rss+xml" href="http://shaarli.fr/rss" title="RSS Feed" />
		    </head>
		    <body>
			<div id="header">
	            <h1 id="top"><a href="./index.php">Shaarlo : Shaarli's Aggregator</a></h1>
	        </div>	    
		    <div id="content">
				<?php
				// start profiling
		// 		xhprof_enable();
				/**
				 * Absolute path to Item 
				 */
				define('XPATH_RSS_ITEM', '/rss/channel/item');
				$rssListFile = 'data/shaarli.txt';
				
				if(is_file($rssListFile)){
					$rssList = json_decode(file_get_contents($rssListFile), true);
				}else{
					$theFirstOne = array('http://sebsauvage.net/links/?do=rss');
					file_put_contents($rssListFile, json_encode($rssList));
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
						$assocShaarliIdUrl[md5($guid)] = $link;
					}
				}
				var_export($assocShaarliIdUrl);
				
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
						$rssTimestamp = strtotime($rssItem['pubDate']);
						
						$actualTimestamp = time();
						$diffValue = round(($actualTimestamp - $rssTimestamp) / 60);
						$diffUnity = 'minute(s)';
						if($diffValue > 60){
							$diffValue = round($diffValue/ 60);
							$diffUnity = 'heure(s)';
						}
						if($diffValue <= 0){
							$diffValue = 0;
						}
						
						$uniqRssKey = md5($link);
						$description = sprintf('<b>%s</b>, il y a %s %s <br/> %s<br/>', $rssKey, $diffValue, $diffUnity, $rssItem['description']);
						$title = $rssItem['title'];
						
						// Delete the Shaarli link and replace it by the 'real' link
						if(array_key_exists($uniqRssKey, $assocShaarliIdUrl)){
							$link = $assocShaarliIdUrl[$uniqRssKey];
							$uniqRssKey = md5($assocShaarliIdUrl[$uniqRssKey]);
						}						
						
						if(!array_key_exists($uniqRssKey, $rssContents) 
						){
							$rssContents[$uniqRssKey] = array('link' => $link, 'description' => array($rssTimestamp => $description), 'title' => $title, 'date' => $rssTimestamp);
						}else{

							$rssContents[$uniqRssKey]['description'][$rssTimestamp] = $description;
							$rssContents[$uniqRssKey]['date'] = min($rssContents[$uniqRssKey]['date'], $rssTimestamp);
							if($uniqRssKey == '341d527915ba96af0c844bfe4ee3d11c'){
								var_export($rssContents[$uniqRssKey]);
							}
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
					ksort($rssContent['description']);
					if($limitRss < $i){
						break;
					}
					$i++;
					?>
					<div class="article shaarli-youm-org">
						<h2 class="article-title">
							<a title="Go to original place" href="<?php echo htmlspecialchars($rssContent['link']);?>"><?php echo htmlspecialchars($rssContent['title']);?></a>
						</h2>
						<div class="article-content"><?php echo implode('<br/>', $rssContent['description']);?></div>	
					</div>
					
				<?php 
					$shaarloRss .= sprintf("<item>
										<title>%s</title>
										<link>%s</link>
										<pubDate>%s</pubDate>
										<description>%s</description>
										</item>",
							htmlspecialchars($rssContent['title']), htmlspecialchars($rssContent['link']), date('r', $rssContent['date']), '<![CDATA[' . implode('<br/>', $rssContent['description']) . ']]>'
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
				?>
				</div>
				<div id="footer">
		            <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments</p>
		        </div>			
			</body>
		</html>
	<?php 
	$page = ob_get_contents(); // copie du contenu du tampon dans une chaîne
	ob_end_clean(); // effacement du contenu du tampon et arrêt de son fonctionnement
	$page = sanitize_output($page);
	file_put_contents($cache, $page);
	
	$shaarloRss .= '</channel></rss>';
	
	file_put_contents('rss.xml', sanitize_output($shaarloRss));
	
	echo $page;
// 	sleep($sleepBeetweenLoops);
	return;
}


