<?php 
/**
 * Mod : proposition
 * Search valid shaarli's url ($limit : 10)
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
global $MOD, $DATA_DIR, $ARCHIVE_DIR_NAME, $SHAARLIS_FILE_NAME;

if(isset($_GET['mod']) && $_GET['mod'] === 'proposition'){
	$limit = 10;
	
	require_once 'fct/fct_rss.php';
	require_once 'fct/fct_xsl.php';
	
	
	$archiveDir = sprintf('%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME);
	if(!defined('XPATH_RSS_ITEM')){
		define('XPATH_RSS_ITEM', '/rss/channel/item');
	}
	
	
	$potentialShaarlis = array();
	$potentialShaarlisListFile = sprintf('%s/%s', $DATA_DIR, $POTENTIAL_SHAARLIS_FILE_NAME);
	if(is_file($potentialShaarlisListFile)){
		$potentialShaarlis = json_decode(file_get_contents($potentialShaarlisListFile), true);
	}
	
	
	if(count($potentialShaarlis) <= $limit){
		$rssListFile = sprintf('%s/%s', $DATA_DIR, $SHAARLIS_FILE_NAME);
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
					$description =  $item['description'];
					$urlMatches = array();
					preg_match_all('#href=\"(.*?)\"#', $description, $urlMatches);
					foreach($urlMatches[1] as $newsUrl){
						if(count($potentialShaarlis) > $limit){
							break 3;
						}				
						$newsUrl = explode('?', $newsUrl);
						if(isset($newsUrl[1]) && strlen($newsUrl[1]) === 6){
		
							if('index.php' === substr($newsUrl[0], -9)){
								$newsUrl[0] = substr($newsUrl[0], 0, strlen($newsUrl[0]) - 9);
							}
								
							if('/' !== $newsUrl[0][strlen($newsUrl[0])-1]) {
								$newsUrl[0] .= '/';
							}
							$potentialRssUrl = $newsUrl[0] . '?do=rss';
								
							if(substr($potentialRssUrl, 8) === 'https://'){
								if(!in_array($potentialRssUrl, $potentialShaarlis)
								&& !in_array($potentialRssUrl, $rssList)
								&& !in_array($potentialRssUrl, $deletedRssList)
								&& !in_array($potentialRssUrl, $disabledRssList)
								&& !in_array($potentialRssUrl, $noHttpsRssList)
								){
									$newRssFlux = is_valid_rss($potentialRssUrl);
									if($newRssFlux !== false){
										$potentialShaarlis[$newRssFlux] = $potentialRssUrl;
									}else{
										$noHttpsRssList[] = $potentialRssUrl;
										file_put_contents($noHttpsRssListFile, json_encode($noHttpsRssList));
									}
								}
							}else{
								$potentialRssUrls = str_replace('http://', 'https://', $potentialRssUrl);
								if(!in_array($potentialRssUrls, $potentialShaarlis)
								&& !in_array($potentialRssUrls, $rssList)
								&& !in_array($potentialRssUrls, $deletedRssList)
								&& !in_array($potentialRssUrls, $disabledRssList)
								&& !in_array($potentialRssUrls, $noHttpsRssList)
								){
									$newRssFlux = is_valid_rss($potentialRssUrls);
									if($newRssFlux !== false){
										$potentialShaarlis[$newRssFlux] = $potentialRssUrls;
									}else{
										$noHttpsRssList[] = $potentialRssUrls;
										file_put_contents($noHttpsRssListFile, json_encode($noHttpsRssList));
											
										if(!in_array($potentialRssUrl, $potentialShaarlis)
										&& !in_array($potentialRssUrl, $rssList)
										&& !in_array($potentialRssUrl, $deletedRssList)
										&& !in_array($potentialRssUrl, $disabledRssList)){
											$newRssFlux = is_valid_rss($potentialRssUrl);
											if($newRssFlux !== false){
												$potentialShaarlis[$newRssFlux] = $potentialRssUrl;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		file_put_contents($potentialShaarlisListFile, json_encode($potentialShaarlis));
	}
}

if(!empty($potentialShaarlis)){
		$MOD['admin.php_top'] .= '<div class="article shaarli-youm-org">
		<h2 class="article-title ">
		<a title="Go to original place" href="">Proposition de flux</a>
		</h2>
		<div class="article-content">
			<form action="admin.php" method="POST">	
			<table style="width:90%;">';
					foreach($potentialShaarlis as $rssKey => $rssUrl){
						$MOD['admin.php_top'] .= '<tr>
							<td  style="width:2%;" rowspan="2">
								<input style="float:left;" type="checkbox" checked name="rssKey[]" id="'.$rssUrl.'" value="'.$rssUrl.'" />
							</td>
							<td></td>
						</tr>
						<tr>
							<td>
								<input type="text" style="width:99%;" name="label[]" value="'.$rssKey.'" />
								<br/>
								<input type="text" style="width:99%;" name="url[]" readonly value="'.$rssUrl.'" />
							</td>
						</tr>';
				}
			$MOD['admin.php_top'] .= '</table>		
						<input type="hidden" name="action" value="add" />
						<input type="submit" value="Ajouter les selectionnés" class="bigbutton"/>					
					</form>
				</div>	
			</div>';
}else{
	/*
	 * View on admin.php
	*/
	$MOD['admin.php_top'] .= '<div class="article shaarli-youm-org">
			<h2 class="article-title ">
			<a title="Go to original place" href="">Proposition de flux</a>
			</h2>
			<div class="article-content">
				<a href="admin.php?mod=proposition" title="Chercher des flux" ><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAYAAADEtGw7AAAEO0lEQVR4Xs3TcUyUZRwH8O/z3vvee3ceB3cHiCAQeiKRliitTDQtpwOTxOklUxR3UUA1RSmrLWXoDET+UFqlbpoOqMbMwkQiBs4aEqYxEBHE4ADl6Di4Ozjujnvvfaq5+c+hxvynz/b89du+e/bdvvhfKDhejU+P1aDo+Hmm49RLoGfwUAymYJwEAYpQad+oeu3eW7lRW3oasHih7smDRUYGIuEC+qzS7BG333bG3sbPWH4Qk2HxEHd6TAhQsiS/vF9rNDl0tjFnRG3jn5xGJQ+yOGULQGRPc5KwK92axIuYBMEk3v38NlbFqZTVTeY1t4zWTbaxiUVuwavxil4JQ6joJRIZw8kZrT+qtTJrJqXU+MP+5EcHn7tuASshmt87x3Ou3x5722iyBbrcTgICUIaAEvF+gRIWCgXvDFSJhzUKW8GERzoOf4rKN1/x7fh0iw00QCpvHxGzTS4xx8sJfrxcMFLRcU3w2K4S1tPDaKYJVKsE1cgxrpTKhyVs2sCIc+Fo1HIMXG307bjohgMXG4yYOz/kxcEhW+qQqbdhvLf7e7fZ0uwcHjdLBI83YnXiRrNStU+gYKngFkT7cP/wvd6fbKaOIY8mAdKuG/Dx8R925LWOctvLr320Ou+bfc+s3DYTAJGwHHIuD6LWOMomVfYXRJ+85Yo49Etr6M7S4sA1uQnyiHglHmV3XTeyKpqkyZ8ci49duTlAt2T9g5v+q0bsrmhWvfzZpcKw90sPaNfteV4eukBBKcVj6XcdwaupOeAVvh9YnLYXKTtKJPPW7QxW6BL4Ofvb8URyG4dQdXeM3XnJGLP26IV5IQlbmPjsww/uVxruoaa+h+w4eolNyTuP9EM1eKTIMjPKWgfk2fWmRa9XD+YtOdvd9GzhuUwAZNaGd/CvsiYLLt+hzMELHcvSi79bykcvJ0m5Jb7LO9HYjRa7AtF+bkWX3RX3bSdSBl1IsnpI9IRjdIAOWzpi6yjt++BDNNxsI813HdquETHZbJ/YYBkxF9d11NOMxM2+wT+2dyGGryJVd1O3WdyaPVaXGOb2UBYiAesVvCrV3Oem32hTxBoMyhO/8XN4nltKiGuhY3DgrKW98VrBLhtuVpf7Lk+XdxRdqvcQZa/YJNLZJUTgA6lXABEBQhmRZeDmQD2cRMop5TI+aBqIv9hbZ79dm1P8ZX7rLEImnzS/GJiecBIeZ6dKKl22nxFmZFGPyMHrAURyf6SUAKIIBee1hsiGfpbZrhZWnc6/DoDiccKzjvzzSmKeyqz/NcrQTCPTLlpD9adbwvRft4VvrGiKSikrj15TlB7zwoYQAAj2Y/GfaFKnA+uBiIzSTVEZDX9FbD5TqV6hj1OvMMwPTHhLFxybqFZICQmcGYMpC8/4AmEZB6ZFGk4dDn/jwG51NJjQpK14YjMMegSvzkKIfuvsoFWvzeUAqBfFYyr+Bvk2whLdALgAAAAAAElFTkSuQmCC"/></a>&nbsp;
				Pas de nouveaux flux :-( 
			</div>
		</div>';	
}
