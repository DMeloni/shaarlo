<?php
include 'config.php';
include 'fct/fct_valid.php';
require_once 'fct/fct_xsl.php';
require_once 'fct/fct_rss.php';
require_once 'fct/fct_session.php';

error_reporting(1);

global $SHAARLO_URL, $DATA_DIR, $CACHE_DIR_NAME, $ARCHIVE_DIR_NAME, $MAX_FOUND_ITEM, $MOD, $ACTIVE_WOT, $ACTIVE_YOUTUBE, $MY_SHAARLI_FILE_NAME, $MY_RESPAWN_FILE_NAME, $ACTIVE_NEXT_PREVIOUS;

// Autoredirect on boot.php
$indexFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'index.html');

if (!checkInstall() && !is_file($indexFile)) {
    header('Location: boot.php');
    return;
}


$myShaarli = array();
$myShaarliUrl = '';
$myShaarliFile = sprintf('%s/%s', $DATA_DIR, $MY_SHAARLI_FILE_NAME);
if (is_file($myShaarliFile)) {
    $myShaarli = json_decode(file_get_contents($myShaarliFile), true);
    $myShaarliUrl = reset($myShaarli);
}

$myRespawnUrl = array();
$myRespawnUrl = '';
$myRespawnFile = sprintf('%s/%s', $DATA_DIR, $MY_RESPAWN_FILE_NAME);
if (is_file($myRespawnFile)) {
    $myRespawn = json_decode(file_get_contents($myRespawnFile), true);
    $myRespawnUrl = reset($myRespawn);
}

/*
 * Affichage des articles sur une période demandée
 */
if(isset($_GET['from']) || isset($_GET['to'])){
    if (!isset($_GET['from']) || $_GET['from'] < "20000000") {
        $_GET['from'] = "20000000";
    }
    if (!isset($_GET['to']) || $_GET['to'] > "90000000") {
        $_GET['from'] = "90000000";
    }
    if ($_GET['from'] > $_GET['to']) {
        $to = $_GET['from'];
        $from = $_GET['to'];
    } else {
        $to = $_GET['to'];
        $from = $_GET['from'];
    }
    try{
        $toDateTime = new DateTime($_GET['to']);
    }
    catch(Exception $e){
        $toDateTime = new DateTime();
    }

    try{
        $fromDateTime = new DateTime($_GET['from']);
    }
    catch(Exception $e){
        $fromDateTime = $toDateTime;
    }

    $to = $toDateTime->format('Ymd235959');
    $from = $fromDateTime->format('Ymd000000');

    $indexationFile = sprintf('%s/%s', $DATA_DIR, $INDEXATION_FILE);
    $row = 1;
    $articles = array();
    if (($handle = fopen($indexationFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $dateTmp = $data['2'];

            // On sort si la date est supérieure à celle demandée
            if ($dateTmp > $to) {
                break;
            }
            // On continue tant que la date est inférieure à celle demandée
            if ($dateTmp < $from) {
                continue;
            }

            $populariteTmp = $data['3'];
            $urlTmp = $data['4'];

            $articles[md5($urlTmp)] = array('file' => $data['0'], 'popularity' => $populariteTmp, 'url' => $urlTmp, 'date' => $data['2']);
        }
        fclose($handle);
    }


    //Tri
    $sortBy = 'date';
    $sorts = array('asc' => SORT_ASC, 'desc' => SORT_DESC);
    $reversedSorts = array_flip($sorts);
    $sort = SORT_DESC;
    if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $sorts)) {
        $sort = $sorts[$_GET['sort']];
    }

    $sortBys = array('popularity', 'date');
    $sortBy = 'date';
    if (isset($_GET['sortBy']) && in_array($_GET['sortBy'], $sortBys)) {
        $sortBy = $_GET['sortBy'];
    }

    // Obtain a list of columns
    foreach ($articles as $key => $row) {
        $triPar[$key] = $row[$sortBy];
    }

    $limit = $MIN_FOUND_ITEM;
    if (isset($_GET['limit']) && $_GET['limit'] > 0) {
        $limit = (int)$_GET['limit'];
    }
    if ($limit > $MAX_FOUND_ITEM) {
        $limit = $MAX_FOUND_ITEM;
    }

// Sort the data with volume descending, edition ascending
// Add $data as the last parameter, to sort by the common key
    array_multisort($triPar, $sort, $articles);

    /*
     * Récupération des flux
     */
    $found = array();
    $archiveDir = sprintf('%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME);
    $foundCnt = 0;
    foreach ($articles as $md5Url => $article) {
        $rssFile = file_get_contents(sprintf('%s/%s', $archiveDir, $article['file']));

        $xmlContent = getSimpleXMLElement($rssFile);
        if ($xmlContent === false) {
            continue;
        }
        $rssFileArrayed = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
        foreach ($rssFileArrayed as $item) {
            if (md5($item['link']) == $md5Url) {
                $found[] = $item;
                $foundCnt++;
                if ($foundCnt >= $limit) {
                    break 2;
                }
            }
        }
    }
    $message = array('popularity' => 'Popularité', 'date' => 'Date', SORT_ASC => 'croissant', SORT_DESC => 'décroissant');

    $dateActuelle = $fromDateTime;
    $dateDemain = '';
    $dateHier = '';
    if (substr($from, 0, 4) == substr($from, 0, 4)) {
        $dateJMoins1 = new DateTime($from);
        $dateJMoins1->modify('-1 day');
        $dateHier = $dateJMoins1->format('Ymd');
        $dateJPlus1 = new DateTime($date);
        $dateJPlus1->modify('+1 day');
        if ($dateJPlus1->format('Ymd') <= date('Ymd')) {
            $dateDemain = $dateJPlus1->format('Ymd');
        }
    }

    // Affichage
    $shaarloRss = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	  <channel>
	    <title>' . 'Du ' . $fromDateTime->format('d/m/Y') . ' au  ' . $toDateTime->format('d/m/Y') . ' - Tri par :  ' . $message[$sortBy] . ' (' . $message[$sort] . ')' . '</title>
	    <link>http://shaarli.fr/</link>
	    <description>Shaarli Aggregators</description>
	    <language>fr-fr</language>
	    <copyright>http://shaarli.fr/</copyright>';
    foreach ($found as $item) {
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
    if (isset($_GET['do']) && $_GET['do'] === 'rss') {
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo sanitize_output($shaarloRss);
    } else {
        $index = parseXsl('xsl/index.xsl', $shaarloRss,
            array('sort' => $reversedSorts[$sort]
            , 'sortBy' => $sortBy
            , 'date_to' => $toDateTime->format('Y-m-d')
            , 'date_from' => $fromDateTime->format('Y-m-d')
            , 'date_actual' => $fromDateTime->format('\L\e d/m/Y')
            , 'nb_sessions' => countNbSessions()
            , 'date_demain' => $dateDemain
            , 'date_hier' => $dateHier
            , 'next_previous' => $ACTIVE_NEXT_PREVIOUS, 'rss_url' => $SHAARLO_URL, 'wot' => $ACTIVE_WOT, 'youtube' => $ACTIVE_YOUTUBE, 'my_shaarli' => $myShaarliUrl, 'my_respawn' => $myRespawnUrl, 'searchTerm' => $_GET['q'], 'mod_content_top' => $MOD[basename($_SERVER['PHP_SELF'] . '_top')]));
        $index = sanitize_output($index);
        header('Content-Type: text/html; charset=utf-8');
        echo $index;
    }
}
else if(isset($_GET['q']) && !empty($_GET['q'])){
    $archiveDir = sprintf('%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME);
    $rssFileList = array();
    $searchTerm = $_GET['q'];
    $type = 'fulltext';
    if (isset($_GET['type']) && $_GET['type'] === 'category') {
        $type = 'category';
    }
    if (!defined('XPATH_RSS_ITEM')) {
        define('XPATH_RSS_ITEM', '/rss/channel/item');
    }
    $found = array();
    $linkAlreadyFound = array();
    $nbFoundItems = 0;
    $fileList = scandir($archiveDir, 1);
    foreach ($fileList as $file) {
        if ($file != "." && $file != "..") {
            sscanf($file, 'rss_%4s%2s%2s.xml', $years, $months, $days);
            $rssFile = file_get_contents(sprintf('%s/%s', $archiveDir, $file));
            $xmlContent = getSimpleXMLElement($rssFile);
            if ($xmlContent === false) {
                continue;
            }
            $rssFileArrayed = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
            foreach ($rssFileArrayed as $item) {
                if ((mb_stripos(strip_tags($item['description']), ($searchTerm)) !== false && ('fulltext' === $type || 'description' == $type))
                    || (mb_stripos($item['link'], $searchTerm) !== false && ('fulltext' === $type || 'link' == $type))
                    || (mb_stripos($item['title'], $searchTerm) !== false && ('fulltext' === $type || 'title' == $type))
                    || (mb_stripos($item['category'], $searchTerm) !== false && 'fulltext' === $type)
                    || ((preg_match("#,$searchTerm,#", $item['category']) || preg_match("#^$searchTerm,#", $item['category']) /*Very ugly*/
                            || preg_match("#,$searchTerm$#", $item['category'])) && 'category' === $type)
                ) {
                    if (!array_key_exists($item['link'], $linkAlreadyFound)
                        || $linkAlreadyFound[$item['link']] < strlen($item['description'])
                    ) {
                        $linkAlreadyFound[$item['link']] = strlen($item['description']);
                        $found[$item['link']] = $item;
                        $nbFoundItems++;
                    }
                }
            }

            if (($nbFoundItems >= $MAX_FOUND_ITEM && !($searchTerm == 'youtube' && isset($_GET['do']) && $_GET['do'] == 'rss')) || $nbFoundItems >= 20) {
                break;
            }

        }
    }

    // Obtient une liste de colonnes
    $dateToSort = array();
    foreach ($found as $key => $row) {
        $dateToSort[$key] = strtotime($row['pubDate']);
    }

    // Trie les données par volume décroissant, edition croissant
    // Ajoute $data en tant que dernier paramètre, pour trier par la clé commune
    if (count($dateToSort) === count($found)) {
        array_multisort($dateToSort, SORT_DESC, $found);
    }

    $shaarloRss = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	  <channel>
	    <title>Recherche des liens :  ' . htmlspecialchars($searchTerm) . '</title>
	    <link>http://shaarli.fr/</link>
	    <description>Shaarli Aggregators</description>
	    <language>fr-fr</language>
	    <copyright>http://shaarli.fr/</copyright>';
    foreach ($found as $item) {
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

    if (isset($_GET['do']) && $_GET['do'] === 'rss') {
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo sanitize_output($shaarloRss);
    } else {
        $index = parseXsl('xsl/index.xsl', $shaarloRss, array('nb_sessions' => countNbSessions(), 'next_previous' => $ACTIVE_NEXT_PREVIOUS, 'rss_url' => $SHAARLO_URL, 'wot' => $ACTIVE_WOT, 'youtube' => $ACTIVE_YOUTUBE, 'my_shaarli' => $myShaarliUrl, 'my_respawn' => $myRespawnUrl, 'searchTerm' => $_GET['q'], 'mod_content_top' => $MOD[basename($_SERVER['PHP_SELF'] . '_top')]));
        $index = sanitize_output($index);
        header('Content-Type: text/html; charset=utf-8');
        echo $index;
    }
}else{
if(isset($_GET['do']) && $_GET['do'] === 'rss'){
    header('Content-Type: application/rss+xml; charset=utf-8');
    $rssFilePath = sprintf('%s/%s/rss.xml', $DATA_DIR, $CACHE_DIR_NAME);
    $rssFile = file_get_contents($rssFilePath);
    echo sanitize_output($rssFile);
}
else
{
?><!DOCTYPE html><?php
if (isset($_GET['date']) && is_file($rssFilePath = sprintf('%s/%s/rss_%s.xml', $DATA_DIR, $ARCHIVE_DIR_NAME, $_GET['date']))) {
    $rssFilePath = sprintf('%s/%s/rss_%s.xml', $DATA_DIR, $ARCHIVE_DIR_NAME, $_GET['date']);
    $rssFile = file_get_contents($rssFilePath);

    $date = $_GET['date'];
    $dateActuelle = new DateTime($date);
    $dateJMoins1 = new DateTime($date);
    $dateJMoins1->modify('-1 day');
    $dateHier = $dateJMoins1->format('Ymd');
    $dateJPlus1 = new DateTime($date);
    $dateJPlus1->modify('+1 day');

    $dateDemain = '';
    if ($dateJPlus1->format('Ymd') <= date('Ymd')) {
        $dateDemain = $dateJPlus1->format('Ymd');
    }

    if (is_file($rssFilePath)) {
        $index = parseXsl('xsl/index.xsl', $rssFile, array(
          'date_to' => $dateActuelle->format('Y-m-d')
        , 'date_from'=> $dateActuelle->format('Y-m-d')
        , 'nb_sessions' => countNbSessions()
        , 'date_demain' => $dateDemain
        , 'date_hier' => $dateHier
        , 'next_previous' => $ACTIVE_NEXT_PREVIOUS, 'rss_url' => $SHAARLO_URL, 'wot' => $ACTIVE_WOT, 'youtube' => $ACTIVE_YOUTUBE, 'my_shaarli' => $myShaarliUrl, 'my_respawn' => $myRespawnUrl, 'mod_content_top' => $MOD[basename($_SERVER['PHP_SELF'] . '_top')]));
        $index = sanitize_output($index);
        header('Content-Type: text/html; charset=utf-8');
        echo $index;
    }
} else {
    if (is_file($indexFile)) {
        header('Content-Type: text/html; charset=utf-8');
        $rssFilePath = sprintf('%s/%s/rssDiff.xml', $DATA_DIR, $CACHE_DIR_NAME);
        $rssFile = file_get_contents($rssFilePath);

        $isSecure = 'no';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
            $isSecure = 'yes';
            $ACTIVE_WOT = 'no';
        }
        $date = date('Ymd');
        $dateActuelle = new DateTime();
        $dateJMoins1 = new DateTime($date);
        $dateJMoins1->modify('-1 day');
        $dateHier = $dateJMoins1->format('Ymd');
        $dateJPlus1 = new DateTime($date);
        $dateJPlus1->modify('+1 day');

        $dateDemain = '';
        if ($dateJPlus1->format('Ymd') <= date('Ymd')) {
            $dateDemain = $dateJPlus1->format('Ymd');
        }

        $index = parseXsl('xsl/index.xsl', $rssFile, array(
            'nb_sessions' => countNbSessions()
        , 'date_demain' => $dateDemain
        , 'date_hier' => $dateHier
        , 'date_to' => $dateActuelle->format('Y-m-d')
        , 'date_from' => $dateActuelle->format('Y-m-d')
        , 'next_previous' => $ACTIVE_NEXT_PREVIOUS
        , 'rss_url' => $SHAARLO_URL, 'wot' => $ACTIVE_WOT, 'youtube' => $ACTIVE_YOUTUBE, 'my_shaarli' => $myShaarliUrl, 'my_respawn' => $myRespawnUrl, 'mod_content_top' => $MOD[basename($_SERVER['PHP_SELF'] . '_top')]));
        $index = sanitize_output($index);
        echo $index;
    } else {
        header('Location: refresh.php?oneshoot=true');
    }
}
}
}


