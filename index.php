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
 * Filtre sur la popularité
 */
$filreDePopularite = 1;
if ((int)$_GET['pop'] > 0) {
    $filreDePopularite = (int)$_GET['pop'];
}

/*
 * Affichage des articles sur une période demandée
 */
if (isset($_GET['sort'])) {
    $filterOn = 'yes';
}
if (!isset($_GET['from']) && !isset($_GET['to'])) {
    $_GET['from'] = $_GET['to'] = date('Ymd');
}

if (isset($_GET['from']) || isset($_GET['to'])) {
    if (!isset($_GET['to'])) {
        $_GET['to'] = $_GET['from'];
    }
    if (!isset($_GET['from'])) {
        $_GET['from'] = $_GET['to'];
    }
    try {
        $toDateTime = new DateTime($_GET['to']);
    } catch (Exception $e) {
        $toDateTime = new DateTime();
    }

    try {
        $fromDateTime = new DateTime($_GET['from']);
    } catch (Exception $e) {
        $fromDateTime = $toDateTime;
    }

    $to = $toDateTime->format('Ymd235959');
    $from = $fromDateTime->format('Ymd000000');

    $limit = $MIN_FOUND_ITEM;
    if (isset($_GET['limit']) && $_GET['limit'] > 0) {
        $limit = (int)$_GET['limit'];
    }
    if ($limit > $MAX_FOUND_ITEM) {
        $limit = $MAX_FOUND_ITEM;
    }

    $indexationFile = sprintf('%s/%s', $DATA_DIR, $INDEXATION_FILE);
    $row = 1;
    $articles = array();
    $linkAlreadyFound = array();
    if (($handle = fopen($indexationFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
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
            if ($filreDePopularite >= ((int)$populariteTmp + 1)) {
                continue;
            }
            $urlTmp = $data['4'];
            $descriptionTmp = $data['5'];
            $searchTerm = $_GET['q'];
            $titreTmp = $data['7'];
            $categoryTmp = $data['6'];

            if(isset($_GET['q']) && $_GET['q'] != ''){
                $type = 'fulltext';
                if (isset($_GET['type']) && $_GET['type'] === 'category') {
                    $type = 'category';
                }
                if ((mb_stripos(strip_tags($descriptionTmp), ($searchTerm)) !== false && ('fulltext' === $type || 'description' == $type))
                    || (mb_stripos($urlTmp, $searchTerm) !== false && ('fulltext' === $type || 'link' == $type))
                    || (mb_stripos($titreTmp, $searchTerm) !== false && ('fulltext' === $type || 'title' == $type))
                    || (mb_stripos($categoryTmp, $searchTerm) !== false && 'fulltext' === $type)
                    || ((preg_match("#,$searchTerm,#", $categoryTmp) || preg_match("#^$searchTerm,#", $categoryTmp) /*Very ugly*/
                    || preg_match("#,$searchTerm$#", $categoryTmp)) && 'category' === $type)) {
                    if (!array_key_exists($urlTmp, $linkAlreadyFound)
                        || $linkAlreadyFound[$urlTmp] < strlen($item['description'])) {
                            $articles[md5($urlTmp)] = array('file' => $data['0'], 'popularity' => $populariteTmp, 'url' => $urlTmp, 'date' => $data['2']);
                    }
                }
            }else{
                $articles[md5($urlTmp)] = array('file' => $data['0'], 'popularity' => $populariteTmp, 'url' => $urlTmp, 'date' => $data['2']);
            }
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
        $linkAlreadyFound = array();

        foreach ($rssFileArrayed as $item) {
            if (md5($item['link']) == $md5Url) {
                $found[] = $item;
                $foundCnt++;
                if (($foundCnt >= $limit) && ($toDateTime != $fromDateTime || isset($_GET['limit']))) {
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
        $dateJPlus1 = new DateTime($from);
        $dateJPlus1->modify('+1 day');
        if ($dateJPlus1->format('Ymd') <= date('Ymd')) {
            $dateDemain = $dateJPlus1->format('Ymd');
        }
    }

    if ($fromDateTime != $toDateTime) {
        $titre = 'Du ' . $fromDateTime->format('d/m/Y') . ' au  ' . $toDateTime->format('d/m/Y') . ' - Tri par :  ' . $message[$sortBy] . ' (' . $message[$sort] . ')';
    } else {
        $titre = 'Les discussions de Shaarli du ' . $fromDateTime->format('d/m/Y');
    }
    // Affichage
    $shaarloRss = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	  <channel>
	    <title>' . $titre . '</title>
	    <link>http://shaarli.fr/</link>
	    <description>Shaarli Aggregators</description>
	    <language>fr-fr</language>
	    <copyright>http://shaarli.fr/</copyright>';
    foreach ($found as $item) {
        $count = substr_count($item['description'], "Permalink");
        if ($count < $filreDePopularite) {
            continue;
        }
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
            , 'limit' => $limit
            , 'min_limit' => $MIN_FOUND_ITEM
            , 'filtre_popularite' => $filreDePopularite
            , 'next_previous' => $ACTIVE_NEXT_PREVIOUS
            , 'rss_url' => $SHAARLO_URL
            , 'wot' => $ACTIVE_WOT
            , 'youtube' => $ACTIVE_YOUTUBE
            , 'my_shaarli' => $myShaarliUrl
            , 'no_description' => $_GET['nodesc']
            , 'my_respawn' => $myRespawnUrl
            ,  'filter_on' => $filterOn
            , 'searchTerm' => $_GET['q']
            , 'mod_content_top' => $MOD[basename($_SERVER['PHP_SELF'] . '_top')]));
        $index = sanitize_output($index);
        header('Content-Type: text/html; charset=utf-8');
        echo $index;
    }

}


