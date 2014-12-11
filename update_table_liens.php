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
$pidFile = 'data/cache/updateTableLiens.pid';

$fluxDir = 'flux';
$maxArticlesParInsert = 100;
$allShaarlistes = json_decode(remove_utf8_bom(file_get_contents("http://$SHAARLO_DOMAIN/api.php?do=getAllShaarlistes")), true);
$infos = array();
$time = microtime(true);
$i = 0;


if (is_file($pidFile) && is_null(get('force'))) {
    $lastvisit = @filemtime($pidFile);
    $difference = mktime() - $lastvisit;
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

$shaarlistes = array();
$articles = array();
foreach($allShaarlistes as $url) {
    $urlRssSimplifiee = simplifieUrl($url);
    echo $url . "<br/>";
    $fluxName = md5(($urlRssSimplifiee));
    $fluxFile = sprintf('%s/%s/%s.xml', $dataDir, $fluxDir, $fluxName);

    if (is_file($fluxFile)) {
            if (!is_null(get('force'))) {
                echo "Traitement  de : " . $fluxFile;
            }

        $content = file_get_contents($fluxFile);
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

        $rssListArrayed= convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
        foreach($rssListArrayed as $rssItem) {
			$link = $rssItem['link'];
            $rssTimestamp = strtotime($rssItem['pubDate']);
            $articleDateJour = date('Ymd', $rssTimestamp);
            if($articleDateJour !== date('Ymd') && $articleDateJour !== '20141106' && !isset($_GET['full'])) {
                continue;
            }

			$guid = $rssItem['guid'];
            $title = $rssItem['title'];
            $description = $rssItem['description'];
            $id = md5(simplifieUrl($guid));

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
                || preg_match('#\id=[0-9]{14}$#', $lienSource)
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
            captureUrl($link, $idCommun, 200, 200, true);
            captureUrl($link, $idCommun, 256, 256, true);
            captureUrl($link, $idCommun, 450, 450, true);

            $articleDate = date('YmdHis', $rssTimestamp);
            $articles[] = creerArticle($id, $idCommun, $link, $urlSimplifie, $title, $description, false, $articleDate, $guid, $fluxName, $idRssOrigin);


            if (count($articles) > $maxArticlesParInsert) {
                insertArticles($mysqli, $articles);
                $articles = array();
            }
        }
    }
    //else{
    //    echo ' -  pas de flux';
    //}

    insertArticles($mysqli, $articles);
    $articles = array();

    //echo '<br>';
}

insertEntites($mysqli, 'rss', $shaarlistes);

shaarliMyDisconnect($mysqli);
@unlink($pidFile);
