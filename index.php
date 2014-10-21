<?php

//ini_set("display_errors", 1);
//ini_set("track_errors", 1);
//ini_set("html_errors", 1);
//error_reporting(E_ALL);


require_once 'config.php';
require_once 'fct/fct_session.php';

 // Returns a token.
function getToken()
{
    $rnd = sha1(uniqid('',true).'_'.mt_rand().$GLOBALS['salt']);  // We generate a random string.
    $_SESSION['tokens'][$rnd]=1;  // Store it on the server side.
    return $rnd;
}

include 'fct/fct_valid.php';
require_once 'fct/fct_xsl.php';
require_once 'fct/fct_rss.php';
require_once 'fct/fct_mysql.php';
require_once('fct/fct_http.php');

global $SHAARLO_URL, $DATA_DIR, $CACHE_DIR_NAME, $ARCHIVE_DIR_NAME, $MAX_FOUND_ITEM, $MIN_FOUND_ITEM, $MOD, $ACTIVE_WOT, $ACTIVE_YOUTUBE, $MY_SHAARLI_FILE_NAME, $MY_RESPAWN_FILE_NAME, $ACTIVE_NEXT_PREVIOUS;


// Vérification de la clef
// TODO

$mysqli = shaarliMyConnect();

// Chargement de la configuration du shaarliste
if(!is_null(get('shaarli'))) {
    loadConfiguration(get('shaarli'));
}

if (!is_null(get('do')) && get('do') == 'logout') {
    // or this would remove all the variables in the session, but not the session itself 
     session_unset(); 
     
     // this would destroy the session variables 
     session_destroy(); 
}


$username = null;
$pseudo = null;
$myshaarli = null;
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
}
if (isset($_SESSION['pseudo'])) {
    $pseudo = $_SESSION['pseudo'];
    $myshaarli = $_SESSION['myshaarli'];
}

$q = null;
if(!empty($_GET['q'])) {
    $q = $_GET['q'];
}
$filterOn = null;
if (isset($_GET['sort'])) {
    $filterOn = 'yes';
}
/*
 * Filtre sur la popularité
 */
$filtreDePopularite = 0;
if (isset($_GET['pop']) && (int)$_GET['pop'] > 0) {
    $filtreDePopularite = (int)$_GET['pop'];
}

// Limite
$limit = $MIN_FOUND_ITEM;
if (isset($_GET['limit']) && $_GET['limit'] > 0) {
    $limit = (int)$_GET['limit'];
}
if ($limit > $MAX_FOUND_ITEM) {
    $limit = $MAX_FOUND_ITEM;
}
//Tri
$sortBy = 'date';
$sorts = array('asc' => SORT_ASC, 'desc' => SORT_DESC);
$reversedSorts = array_flip($sorts);
$sort = SORT_DESC;
if (isset($_GET['sort']) && array_key_exists($_GET['sort'], $sorts)) {
    $sort = $sorts[$_GET['sort']];
}
$sortBys = array('pop', 'rand');
if (isset($_GET['sortBy']) && in_array($_GET['sortBy'], $sortBys)) {
    $sortBy = $_GET['sortBy'];
}

$fromDateTime = new DateTime();
$toDateTime = new DateTime();
if (isset($_GET['do']) && $_GET['do'] === 'rss') {
    $from = $to = null;
}else{
    $from = $fromDateTime->format('Ymd000000');
    $to = $toDateTime->format('Ymd235959');
}

if (isset($_GET['from'])) {
    try {
        $fromDateTime = new DateTime($_GET['from']);
        $from = $fromDateTime->format('Ymd000000');
    } catch (Exception $e) {
        
    }
}

if (isset($_GET['to'])) {
    try {
        $toDateTime = new DateTime($_GET['to']);
        $to = $toDateTime->format('Ymd235959');
    } catch (Exception $e) {
        
    }
}

// daily=tomorrow pour bloquer sur hier
if (isset($_GET['daily']) && $_GET['daily'] == 'tomorrow' ) {
    $today = new DateTime();
    $tomorrow = $today->modify('-1 DAY');
    $from = $tomorrow->format('Ymd000000');
    $to = $tomorrow->format('Ymd235959');
}

if (isset($_GET['do']) && $_GET['do'] === 'rss') {
    $usernameRecherche='faa615913d3c84ce1ad0d6b86b53a4f5';
}else{
    $mesAbonnements = getAllAbonnements($mysqli, $username);
    if(empty($mesAbonnements)) {
        $usernameRecherche='faa615913d3c84ce1ad0d6b86b53a4f5';
    }else{
        $usernameRecherche=$username;
    }
}
if(isset($_GET['u'])) {
    $usernameRecherche=$_GET['u'];
}
$articles = getAllArticlesDuJour($mysqli, $usernameRecherche, $q, $filtreDePopularite, $sortBy, $sort, $from, $to, $limit);

// Regroupement des articles
$found = array();
foreach($articles as $article) {
    $articleDateTime = new DateTime($article['article_date']);
    
    $rssTitre = $article['rss_titre'];
    if(!empty($article['alias'])){
        $rssTitre = $article['alias'];
    }
    
    $followUrl = '';
    
    if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
        if(isset($_SESSION['username'])) {
            if(!isset($mesAbonnements[$article['id_rss']])) {
                $followUrl = ' (<a href="#" onclick="javascript:addAbo(this,\'' . $article['id_rss'] . '\', \'add\');return false;">Suivre</a>)';
            }else{
                $followUrl = ' (<a href="#" onclick="javascript:addAbo(this,\'' . $article['id_rss'] . '\', \'delete\');return false;">Se désabonner</a>)';
            }            
        }else{
            $followUrl = ' (<a href="my.php" title="Authentification nécessaire">Suivre</a>)';
        }
    }
    
    // L'admin peut bloquer un lien
    if (isAdmin()) {
        if($article['active'] === '1') {
           $followUrl = ' (<a href="#" onclick="javascript:validerLien(this,\'' . $article['id'] . '\', \'bloquerLien\');return false;">Censurer ce lien</a>)';
        } else {
           $followUrl = ' (<a href="#" onclick="javascript:validerLien(this,\'' . $article['id'] . '\', \'validerLien\');return false;">Débloquer ce lien</a>)';
        }
    }
    
    $rssTitreAffiche = htmlspecialchars($rssTitre);
    
    if(strpos($article['article_uuid'], 'my.shaarli.fr') > 0) {
        $rssTitreAffiche = '@' . $rssTitreAffiche;
    }
    
    $shaarliBaseUrl = explode('?', $article['article_uuid']);
    if(isset($shaarliBaseUrl[0])) {
        $shaarliBaseUrl = $shaarliBaseUrl[0];
        $rssTitreAffiche = sprintf('<a href="%s">%s</a>', $shaarliBaseUrl, $rssTitreAffiche);
    }

    if(!empty($article['alias_origin'])) {
        if($rssTitre != $article['alias_origin']) {
            $rssTitreAffiche = sprintf('%s > <a href="%s">%s</a>', $rssTitreAffiche, $article['article_url'], $article['alias_origin']);
        }
    }
    elseif(!empty($article['rss_titre_origin'])) {
        $rssTitreAffiche = sprintf('%s > <a href="%s">%s</a>', $rssTitreAffiche, $article['article_url'], $article['rss_titre_origin']);
    }
    
    
    // Si le lien est actif ou si l'administrateur est connecté
    // Le message est affiché en clair
    if($article['active'] === '1' ||  isAdmin()) {
        $img = '';
        if(!(isset($_GET['do']))) {
            $faviconPath = 'img/favicon/63a61a22845f07c89e415e5d98d5a0f5.ico';
            
            $faviconGifPath = sprintf('img/favicon/%s.gif', $article['id_rss']);
            if(is_file($faviconGifPath)) {
               $faviconPath = $faviconGifPath;
            } else {
                $faviconIcoPath = sprintf('img/favicon/%s.ico', $article['id_rss']);
                if(is_file($faviconIcoPath)) {
                   $faviconPath = $faviconIcoPath;
                }
            }
            $img = sprintf('<a href="%s"><img class="entete-avatar" width="50" height="50" src="%s"/></a>', $shaarliBaseUrl, sprintf('%s/%s', $SHAARLO_URL, $faviconPath));
        }
        $description = sprintf('%s<span class="entete-pseudo"><b>%s</b>, le %s </span><br/> %s %s<br/><br/>', 
            $img, 
            $rssTitreAffiche, 
            date('d/m/Y \à H:i', $articleDateTime->getTimestamp()), 
            str_replace('<br>', '<br/>', $article['article_description']),
            $followUrl
       );
    } else {
        // Si le message a été censuré, on affiche un message
        $description = sprintf("<b>%s</b>, le %s <br/> %s $followUrl<br/><br/>", $rssTitreAffiche, date('d/m/Y \à H:i', $articleDateTime->getTimestamp()), str_replace('<br>', '<br/>', '<span title="Ce contenu ne correspond pas aux règles de ce site web.">-- Commentaire censuré --</span>'));  
    }
    
    $popularity=0;
    $articleDate = $article['article_date'];
    if(isset($found[$article['id_commun']])) {
        $description .= $found[$article['id_commun']]['description'];
        $popularity = $found[$article['id_commun']]['pop'] + 1;
        $articleDate = $found[$article['id_commun']]['date'];
    }
      
    $found[$article['id_commun']] = array('description' => $description, 
                                          'title' =>  $article['article_titre'], 
                                          'link' => $article['article_url'],
                                          'pubDate' => $articleDateTime->format(DateTime::RSS),
                                          'date' => $articleDate,
                                          'category' => '',
                                          'pop' => $popularity,
                                          'rand' => rand()
                                          );

}
/*
var_export($found);
echo $sort;
echo $sortBy;*/
if(is_array($found)) {
    $triPar = array();
    // Obtain a list of columns
    foreach ($found as $key => $row) {
        $triPar[$key] = $row[$sortBy];
    }
    // Sort the data with volume descending, edition ascending
    // Add $data as the last parameter, to sort by the common key
    array_multisort($triPar, $sort, $found);
}
$message = array('pop' => 'Popularité', 'rand' => 'Random', 'date' => 'Date', SORT_ASC => 'croissant', SORT_DESC => 'décroissant');

if ($fromDateTime->format('Ymd') != $toDateTime->format('Ymd')) {
    $titre = 'Du ' . $fromDateTime->format('d/m/Y') . ' au  ' . $toDateTime->format('d/m/Y') . ' - Tri par :  ' . $message[$sortBy] . ' (' . $message[$sort] . ')';
} else {
    if(isset($usernameRecherche) && $usernameRecherche != 'shaarlo') {
        $shaarliste = getShaarliste($mysqli, $usernameRecherche);
        $titre = 'Les discussions de @' .htmlentities($shaarliste['pseudo']). ' du ' . $fromDateTime->format('d/m/Y');
    }else{
        $titre = 'Les discussions de Shaarli du ' . $fromDateTime->format('d/m/Y');
    }
}

// Création du flux rss
    $shaarloRss = '<?xml version="1.0" encoding="utf-8"?>
	<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	  <channel>
	    <title>'.$titre.'</title>
	    <link>http://shaarli.fr/</link>
	    <description>Shaarli Aggregators</description>
	    <language>fr-fr</language>
	    <copyright>http://shaarli.fr/</copyright>';
foreach ($found as $item) {
    $count = substr_count($item['description'], "Permalink");
    if ($count < $filtreDePopularite) {
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



// Affichage
if (isset($_GET['do']) && $_GET['do'] === 'rss') {
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo sanitize_output($shaarloRss);
} else {

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
    $dateActuelle = new DateTime();
    $isSecure = 'no';
    if(!empty($_SERVER['HTTPS'])) {
        $isSecure = 'yes';
    }
    $myShaarliUrl='';
    if(isset($_SESSION['username'])){
        $myShaarliUrl = htmlentities(sprintf('http://my.shaarli.fr/%s/', $_SESSION['username']));
    }
    
    $nodesc = null;
    if(isset($_GET['nodesc'])) {
        $nodesc = $_GET['nodesc'];
    }
    $nbSessions = null;
    /*
    $logStat = json_decode(file_get_contents('log/stat'));
    $nbSessions = $logStat[0];
    */
        $index = parseXsl('xsl/index.xsl', $shaarloRss,
            array('sort' => $reversedSorts[$sort]
            , 'sortBy' => $sortBy
            , 'date_to' => $toDateTime->format('Y-m-d')
            , 'max_date_to' => $dateActuelle->format('Y-m-d')
            , 'date_from' => $fromDateTime->format('Y-m-d')
            , 'date_actual' => $fromDateTime->format('\L\e d/m/Y')
            , 'nb_sessions' => $nbSessions
            , 'date_demain' => $dateDemain
            , 'date_hier' => $dateHier
            , 'limit' => $limit
            , 'min_limit' => $MIN_FOUND_ITEM
            , 'max_limit' => $MAX_FOUND_ITEM
            , 'filtre_popularite' => $filtreDePopularite
            , 'next_previous' => $ACTIVE_NEXT_PREVIOUS
            , 'rss_url' => $SHAARLO_URL
            , 'wot' => $ACTIVE_WOT
            , 'youtube' => $ACTIVE_YOUTUBE
            , 'my_shaarli' => $myshaarli
            , 'no_description' => $nodesc
            , 'filter_on' => $filterOn
            , 'searchTerm' => $q
            , 'is_secure' => $isSecure
            , 'mod_content_top' => ''
            , 'username' => $username
            , 'pseudo' => $pseudo
            , 'token' => getToken()
            )
            );
        $index = sanitize_output($index);
        header('Content-Type: text/html; charset=utf-8');
        echo $index;
}

