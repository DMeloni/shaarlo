<?php
/**
 * Mod : opml
* Load and save opml feed
* @author : DMeloni
* no support : at your peril
*/
include_once 'config.php';
require_once 'fct/fct_rss.php';


/*
 * Load opml file
*/
global $DATA_DIR, $SHAARLIS_FILE_NAME;
$allFlux = array();
if(isset($_POST['mod']) && $_POST['mod'] === 'opml' && !empty($_FILES['subscription']['tmp_name'])){
	$xmlDoc=new DomDocument();
	$rc=@($xmlDoc->loadXML(file_get_contents($_FILES['subscription']['tmp_name'])));
	if($rc==false){
		$serverMsg =  'Le fichier importé est naze.';
	}else{
		$allFlux = getAllGReaderFlux($_FILES['subscription']['tmp_name']);
		$rssList = array();
		$rssListFile = sprintf('%s/%s', $DATA_DIR, $SHAARLIS_FILE_NAME);
		if(is_file($rssListFile)){
			$rssList = json_decode(file_get_contents($rssListFile), true);
		}
		$rssList = array_merge($rssList, $allFlux);
		file_put_contents($rssListFile, json_encode($rssList));
	}
}

/*
 * Get the opml file
 */
if(isset($_GET['mod']) && $_GET['mod'] === 'opml'){
	$rssListFile = sprintf('%s/%s', $DATA_DIR, $SHAARLIS_FILE_NAME);
	if(is_file($rssListFile)){
		$rssList = json_decode(file_get_contents($rssListFile), true);

		$subscription = '<?xml version="1.0" encoding="UTF-8"?>
			<opml version="1.0">
			    <head>
			        <title>Abonnements</title>
			    </head>
			    <body>';
		foreach($rssList as $rssLabel => $rssUrl){
			$subscription .=  sprintf('<outline text="%s" title="%s" type="rss" xmlUrl="%s" htmlUrl="%s"/>', $rssLabel, $rssLabel, $rssUrl, $rssUrl);
		}
		$subscription .= '</body></opml>';
		
		$opmlFileTmp = sprintf('%s/opml', $DATA_DIR);
		file_put_contents($opmlFileTmp, $subscription);
		
		header("Content-disposition: attachment; filename=subscriptions.xml");
		header("Content-Type: application/xml");
		header("Content-Transfer-Encoding: xml\n"); // Surtout ne pas enlever le \n
		//header('Content-Transfer-Encoding: binary');
		header("Content-Length: ".(filesize($opmlFileTmp) + 10));
		header("Pragma: no-cache");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
		header("Expires: 0");
		readfile($opmlFileTmp);
		
		unlink($opmlFileTmp);
		exit;
	}
}

/*
 * Show html for user
*/
$MOD['admin.php_top'] .= sprintf('<div class="article shaarli-youm-org">
		<h2 class="article-title ">
		<a title="Go to original place" href="">Module Opml</a>
		</h2>
		<div class="article-content">
			<form action="" method="POST" enctype="multipart/form-data">    
				<input type="hidden" name="MAX_FILE_SIZE" value="2097152">    
				<input name="mod" type="hidden" value="opml" />
				<input type="file" name="subscription"/>
				<input type="submit" value="Charger" />
			</form>
			<a href="?mod=opml">Sauvegarder</a>
		</div>
	</div>');

