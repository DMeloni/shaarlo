<?php
ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);
// include 'auto_restrict.php';
require_once 'config.php';
require_once 'fct/fct_rss.php';
require_once 'fct/fct_mysql.php';
require_once 'fct/fct_http.php';
require_once 'fct/fct_session.php';
include_once('fct/fct_capture.php');

// Vérification de la clef
// TODO


global $SHAARLO_DOMAIN;

$mysqli = shaarliMyConnect();

$dataDir = 'data';
$pidFile = 'cache/updateTableLiens.pid';

$fluxDir = 'flux';
$maxArticlesParInsert = 100;
$allShaarlistes = json_decode(remove_utf8_bom(file_get_contents("http://$SHAARLO_DOMAIN/api.php?do=getAllShaarlistes")), true);
$infos = array();
$time = microtime(true);
$i = 0;


if (is_file($pidFile) && is_null(get('force'))) {
    $lastvisit = @filemtime($pidFile);
    $difference = time() - $lastvisit;
    $max_time = 60; // On ne peut lancer le script qu'une fois par minute
    if ($difference < $max_time) {
        die('lancement deja en cours');
    } else {
        @unlink($pidFile);
        file_put_contents($pidFile, date('YmdHis'));
    }   
} else {
    file_put_contents($pidFile, date('YmdHis'));
}

$adebut = microtime(true);
$shaarlistes = array();
$articles = array();
$tags = array();
foreach($allShaarlistes as $url) {
    $urlRssSimplifiee = simplifieUrl($url);
    
    //echo $url ;
    $fluxName = md5(($urlRssSimplifiee));
    $fluxFile = sprintf('%s/%s/%s.xml', $dataDir, $fluxDir, $fluxName);

    if (is_file($fluxFile)) {
        echo "Traitement  de : " . $fluxFile . "<br/>";
        
        $content = file_get_contents($fluxFile);

        //Fri, 13 Mar 2015 16:09:22 +0400
        if (strpos($content, date('D, d M Y')) === false && !isset($_GET['full'])) {
            echo "Rien de neuf" . "<br/>";
            continue;
        }
        
        $xmlContent = getSimpleXMLElement($content);
        if($xmlContent === false){
            if (!is_null(get('force'))) {
                echo "flux foireux : " . $fluxFile;
            }
            continue;
        }
        
        $list = $xmlContent->xpath(XPATH_RSS_TITLE);
        
        if(!isset($list[0])) {
            continue;
        }

        $titre = (string)$list[0];
        
        $shaarlistes[] = creerRss($fluxName, $titre, $url, $urlRssSimplifiee, 1);
        
        if (!isset($_GET['full'])) {
            $rssListArrayed = convertXmlToTableauAndStop($xmlContent, XPATH_RSS_ITEM);
        } else {
            $rssListArrayed = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
        }
        
        foreach($rssListArrayed as $rssItem) {
            
            $link = $rssItem['link'];
            
            $link = str_replace('my.shaarli.fr/', 'www.shaarli.fr/my/', $link);
            
            $rssTimestamp = strtotime($rssItem['pubDate']);
            $articleDateJour = date('Ymd', $rssTimestamp);
            if($articleDateJour !== date('Ymd') 
            && !isset($_GET['full'])) {
                break;
            }
            
            echo "Ajout  de : " . $link . "<br/>";
            $guid = $rssItem['guid'];
            if (preg_match('#^http://lehollandaisvolant.net/\?mode=links&id=[0-9]{14}$#', $guid)) {
                $guid = str_replace('mode=links&', '', $guid);
            }
            if (preg_match('#^http://lehollandaisvolant.net/\?mode=links&id=[0-9]{14}$#', $link)) {
                $link = str_replace('mode=links&', '', $link);
            }
            
            $title = $rssItem['title'];
            $description = $rssItem['description'];
            $id = md5(simplifieUrl($guid));
            $category = '';
            if (isset($rssItem['category'])) {
                $category = $rssItem['category'];
            }
            
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
            
            while ( preg_match('#\?[_a-zA-Z0-9\-]{6}$#', $lienSource)
                || preg_match('#\?id=[0-9]{14}$#', $lienSource)
            ) {
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
            
            // Creation miniature
            if (!isset($_GET['skip-mini'])) {
                captureUrl($link, $idCommun, 200, 200, true);
                captureUrl($link, $idCommun, 256, 256, true);
                captureUrl($link, $idCommun, 450, 450, true);
            }
            $articleDate = date('YmdHis', $rssTimestamp);
            $articles[] = creerArticle($id, $idCommun, $link, $urlSimplifie, $title, $description, false, $articleDate, $guid, $fluxName, $idRssOrigin, $category);
            
            $categories = explode(',', $category);
            foreach ($categories as $categoriesPart) {
                if (empty($categoriesPart)) {
                    continue;
                }
                $tags[] = creerTag($id, $categoriesPart);
            }
            
            if (count($tags) > $maxArticlesParInsert) {
                insertEntites($mysqli, 'tags', $tags);
                $tags = array();
            }
            
            if (count($articles) > $maxArticlesParInsert) {
                insertArticles($mysqli, $articles);
                $articles = array();
            }
        }
    } 
    //else{
    //    echo ' -  pas de flux';
    //}
    
    insertEntites($mysqli, 'tags', $tags);
    $tags = array();
                
    insertArticles($mysqli, $articles);
    $articles = array();
    
    //echo '<br>';
}

insertEntites($mysqli, 'rss', $shaarlistes);
    
shaarliMyDisconnect($mysqli);
@unlink($pidFile);

$afin = microtime(true) - $adebut;


file_put_contents('temps_script.txt', sprintf('%s : %s', date('d/m/Y H:i:s'), $afin));


