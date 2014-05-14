<?php 
/**
 * Mod : delete_duplicate_url.php
 * Search duplicate shaarli's url and keep one of them (https priority)
 * @author : DMeloni
 * no support : at your peril
 */
/*
 * Show best tags on admin.php
*/
include_once 'config.php';

/*
 * Mod filter
*/
$modActivedOnPages = array('admin.php');
$path = $_SERVER['PHP_SELF'];
$file = basename ($path);
if(!in_array($file, $modActivedOnPages)){
	return ;
}
global $MOD, $DATA_DIR, $ARCHIVE_DIR_NAME, $SHAARLIS_FILE_NAME, $DELETED_SHAARLIS_FILE_NAME, $DISABLED_SHAARLIS_FILE_NAME;

$rssListFile = sprintf('%s/%s', $DATA_DIR, $SHAARLIS_FILE_NAME);
if(!is_file($rssListFile)){
	return;
}
$rssList = json_decode(file_get_contents($rssListFile), true);
$flippedRssList = array_flip($rssList);
if(isset($_GET['mod']) && $_GET['mod'] === 'delete_duplicate_url'){
	
	require_once 'fct/fct_rss.php';
	require_once 'fct/fct_xsl.php';
	
	
	$deletedRssList = array();
	$deletedRssListFile = sprintf('%s/%s', $DATA_DIR, $DELETED_SHAARLIS_FILE_NAME);
	if(is_file($deletedRssListFile)){
		$deletedRssList = json_decode(file_get_contents($deletedRssListFile), true);
	}
	
	$disabledRssList = array();
	$disabledRssListFile = sprintf('%s/%s', $DATA_DIR, $DISABLED_SHAARLIS_FILE_NAME);
	if(is_file($disabledRssListFile)){
		$disabledRssList = json_decode(file_get_contents($disabledRssListFile), true);
	}
		
	$shaarliLinks = array();
	foreach($rssList as $rssKey => $rssUrl){
		$content = getRss($rssUrl);
		$xmlContent = getSimpleXMLElement($content);
		if($xmlContent === false){
			$disabledRssList[$rssKey] = $rssUrl;
			unset($flippedRssList[$rssUrl]);
			continue;
		}

		$shaarliTitle = $xmlContent->xpath(XPATH_RSS_TITLE);
		$shaarliDescription = $xmlContent->xpath(XPATH_RSS_DESCRIPTION);
		
		if(!isset($shaarliTitle) || !isset($shaarliDescription)){
			$deletedRssList[$rssKey] = $rssUrl;
			unset($flippedRssList[$rssUrl]);	
		}else{
			$uniqId = md5((string)$shaarliTitle[0].(string)$shaarliDescription[0]);
			if(!array_key_exists($uniqId, $shaarliLinks)){		
				$shaarliLinks[$uniqId] = $rssUrl;
			}else{
				if(substr($rssUrl, 0, 8) == 'https://'){
					$deletedRssList[$flippedRssList[$shaarliLinks[$uniqId]]] = $shaarliLinks[$uniqId];
					unset($flippedRssList[$shaarliLinks[$uniqId]]);					
					$shaarliLinks[$uniqId] = $rssUrl;							
				}else{
					$deletedRssList[$rssKey] = $rssUrl;
					unset($flippedRssList[$rssUrl]);
				}
			}
		}
	}
	
	$rssList = array_flip($flippedRssList);
	file_put_contents($rssListFile, json_encode($rssList));
	file_put_contents($disabledRssListFile, json_encode($disabledRssList));
	file_put_contents($deletedRssListFile, json_encode($deletedRssList));		
}

// var_export($rssList);
if(!empty($rssList)){
	/*
	 * View on admin.php
	*/
	$MOD['admin.php_top'] .= '<div class="article shaarli-youm-org">
			<h2 class="article-title ">
			<a title="Go to original place" href="">Suppression des doublons shaarlis</a>
			</h2>
			<div class="article-content">
				<a href="admin.php?mod=delete_duplicate_url" title="Supprimer des flux doublons" class="refreshButton">&#10227;</a>&nbsp;
				Supprimer les doublons shaarlis ?
			</div>
		</div>';	
}
