<?php
// include 'auto_restrict.php';
require_once 'config.php';
require_once 'fct/fct_rss.php';
require_once 'fct/fct_mysql.php';

error_reporting(true);
// Vérification de la clef
// TODO



$mysqli = shaarliMyConnect();

$dataDir = 'data';
$fluxDir = 'flux';
$maxArticlesParInsert = 100;
$allShaarlistes = json_decode(remove_utf8_bom(file_get_contents('http://shaarli.fr/api.php?do=getAllShaarlistes')), true);
$infos = array();
$time = microtime(true);
$i = 0;

$shaarlistes = array();
foreach($allShaarlistes as $url) {
    echo $url ;
    $fluxName = md5(urldecode($url));
    $fluxFile = sprintf('%s/%s/%s.xml', $dataDir, $fluxDir, $fluxName);

    if (is_file($fluxFile)) {
        $content = file_get_contents($fluxFile);
        $xmlContent = getSimpleXMLElement($content);
        if($xmlContent === false){
            echo ' -  flux foireux' . '<br/>';
            continue;
        }
        
        $list = $xmlContent->xpath(XPATH_RSS_TITLE);
        
        $titre = (string)$list[0];
        
        $shaarlistes[] = creerRss($fluxName, $titre, $url);
        
        $rssListArrayed= convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
        foreach($rssListArrayed as $rssItem) {
			$link = $rssItem['link'];
            $rssTimestamp = strtotime($rssItem['pubDate']);
            $articleDateJour = date('Ymd', $rssTimestamp);
            if($articleDateJour !== date('Ymd') && !isset($_GET['full'])) {
                continue;
            }
            
			$guid = $rssItem['guid'];
            $title = $rssItem['title'];
            $description = $rssItem['description'];
            $id = md5($guid);
            
            $linkSansHttp  = str_replace('http://', '', $link);
            $linkSansHttps = str_replace('https://', '', $linkSansHttp);
            $urlSimplifie = $linkSansHttps;

            $idCommun = md5($urlSimplifie);
            // Si c'est un lien qui pointe vers un shaarli, il est surement déjà en base
            // Donc on le récupère directement
            $nbBouclesMax = 5;
            $nbBoucles = 0;
            $lienSource = $link;
            $idRssOrigin = null;
            while(preg_match('#\?[_a-zA-Z0-9\-]{6}$#', $lienSource)) {
                $retourGetId = getIdCommunFromShaarliLink($mysqli, $lienSource);
                if($idRssOrigin === null) {
                    $idRssOrigin = getIdRssOriginFromShaarliLink($mysqli, $lienSource);
                }
                $nbBoucles++;
                if(!is_null($retourGetId) && $nbBoucles < $nbBouclesMax) {
                    $idCommun = $retourGetId['id_commun'];
                    $lienSource = $retourGetId['article_url'];
                }else{
                    break;
                }
            }
            
            $articleDate = date('YmdHis', $rssTimestamp);
            
            $articles[] = creerArticle($id, $idCommun, $link, $urlSimplifie, $title, $description, false, $articleDate, $guid, $fluxName, $idRssOrigin);

            if (count($articles) > $maxArticlesParInsert) {
                insertArticles($mysqli, $articles);
                $articles = array();
            }
        }
    } else{
        echo ' -  pas de flux';
    }
    
    insertArticles($mysqli, $articles);
    $articles = array();
    
    echo '<br>';
}

insertEntites($mysqli, 'rss', $shaarlistes);
    
shaarliMyDisconnect($mysqli);
