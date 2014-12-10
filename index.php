<?php

//ini_set("display_errors", 1);
//ini_set("track_errors", 1);
//ini_set("html_errors", 1);
//error_reporting(E_ALL);


require_once 'config.php';
require_once 'fct/fct_session.php';
include_once('fct/fct_capture.php');

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

/*
 * Lock du menu
 */
$menuLocked = false;
if (isset($_SESSION['lock']['state']) ) {
    if ($_SESSION['lock']['state'] == 'lock') {
        $menuLocked = true;
    }
}
 

/*
 * Filtre sur la popularité
 */
$filtreDePopularite = 0;
if (isset($_GET['pop']) && (int)$_GET['pop'] > 0) {
    $filtreDePopularite = (int)$_GET['pop'];
}

$q = null;
$afficherMessagerie = false;
if(!empty($_GET['q'])) {
    $q = $_GET['q'];
    // Affichage de la messagerie du shaarliste 
    $matches = array();
    if (preg_match_all('#^shaarli:([0-9a-f]{32})$#', urldecode($q), $matches) === 1) {
        $afficherMessagerie = true;
        $filtreDePopularite = 2;
        $titrePageMessagerie = sprintf('Messagerie de %s', getRssTitleFromId($mysqli, $matches[1][0]));
    }
}
$filterOn = null;
if (isset($_GET['sort'])) {
    $filterOn = 'yes';
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

$today = new DateTime();
// daily=tomorrow pour bloquer sur hier
if (isset($_GET['daily']) && $_GET['daily'] == 'tomorrow' ) {
    $tomorrow = $today->modify('-1 DAY');
    $from = $tomorrow->format('Ymd000000');
    $to = $tomorrow->format('Ymd235959');
}

if (isset($_GET['do']) && $_GET['do'] === 'rss') {
    $usernameRecherche='196e3006151883482e97250f4f1e8eb8';
}else{
    $mesAbonnements = getAllAbonnements($mysqli, $username);
    if(empty($mesAbonnements)) {
        $usernameRecherche='196e3006151883482e97250f4f1e8eb8';
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
    
    //ajout de l'icone de messagerie ssi non mode rss
    $iconeMessagerie = '';
    if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
        $iconeMessagerie = sprintf('<a href="?q=shaarli%%3A%s"><img class="display-inline-block-text-bottom  opacity-7" width="15" height="15" src="img/mail.gif"></a>', $article['id_rss']);
    }
        
    if(isset($shaarliBaseUrl[0])) {
        $shaarliBaseUrl = $shaarliBaseUrl[0];
        $rssTitreAffiche = sprintf('<a href="%s">%s</a> %s', $shaarliBaseUrl, $rssTitreAffiche, $iconeMessagerie);
    }

    if(isset($found[$article['id_commun']]) && !empty($article['rss_titre_origin'])) {
        $rssTitreAffiche = sprintf('%s > <a href="%s">%s</a>', $rssTitreAffiche, $article['article_url'], $article['rss_titre_origin']);
    } 
    
    
    // Si le lien est actif ou si l'administrateur est connecté
    // Le message est affiché en clair
    if($article['active'] === '1' ||  isAdmin()) {
        $img = '';
        //ajout de l'icone d'avatar ssi non mode rss
        if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
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
            $img = sprintf('<a href="%s"><img class="entete-avatar" width="16" height="16" src="%s"/></a>', $shaarliBaseUrl, sprintf('%s', $faviconPath));
        }
        if($articleDateTime->format('Ymd') == $today->format('Ymd')) {
            $dateAffichee = date('H:i', $articleDateTime->getTimestamp());
        } else {
            $dateAffichee = date('d/m/Y', $articleDateTime->getTimestamp());
        }

        
        $description = sprintf('%s<span class="entete-pseudo"><b>%s</b> <span class="opacity-3">%s</span> </span><br/> %s %s<br/><br/>', 
            $img, 
            $rssTitreAffiche, 
            $dateAffichee, 
            str_replace('<br>', '<br/>', $article['article_description']),
            $followUrl
       );
    } else {
        // Si le message a été censuré, on affiche un message
        $description = sprintf("<b>%s</b> %s <br/> %s $followUrl<br/><br/>", $rssTitreAffiche, date('d/m/Y \à H:i', $articleDateTime->getTimestamp()), str_replace('<br>', '<br/>', '<span title="Ce contenu ne correspond pas aux règles de ce site web.">-- Commentaire censuré --</span>'));  
    }
    
    if($articleDateTime->format('Ymd') == $today->format('Ymd')) {
        $derniereDateMaj = $articleDateTime->format('H:i');
    } else {
        $derniereDateMaj = $articleDateTime->format('d/m');
    }
    $dernierAuteur = $article['rss_titre'];
    $popularity=0;
    $articleDate = $article['article_date'];
    if(isset($found[$article['id_commun']])) {
        $description .= $found[$article['id_commun']]['description'];
        $popularity = $found[$article['id_commun']]['pop'] + 1;
        $articleDate = $found[$article['id_commun']]['date'];
        $dernierAuteur = $found[$article['id_commun']]['dernier_auteur'];
        $faviconPath = $found[$article['id_commun']]['dernier_auteur_favicon'];
        $derniereDateMaj = $found[$article['id_commun']]['derniere_date_maj'];
    }
    

    
    $found[$article['id_commun']] = array('description' => $description, 
                                          'title' =>  $article['article_titre'], 
                                          'link' => $article['article_url'],
                                          'pubDate' => $articleDateTime->format(DateTime::RSS),
                                          'date' => $articleDate,
                                          'category' => '',
                                          'pop' => $popularity,
                                          'rand' => rand(),
                                          'dernier_auteur' => $dernierAuteur,
                                          'dernier_auteur_favicon' => $faviconPath,
                                          'derniere_date_maj' => $derniereDateMaj
                                          );

}

/*
* Récupération du "meilleur" article du jour
*/
$isToday = true;
if(!isset($_GET['q'])) {
    if(isset($_GET['from'])) {
        $dateDeLaVeille = new DateTime($_GET['from']);
        //$dateDeLaVeille->modify('-1 day');
        $dateTimeFrom = new DateTime($dateDeLaVeille->format('Ymd000000'));
        $dateTimeTo   = new DateTime($dateDeLaVeille->format('Ymd235959'));
        $isToday = false;
    } else {
        $dateDeLaVeille = new DateTime();
        if(isset($_GET['veille'])) {
            $dateDeLaVeille = new DateTime($_GET['veille']);
            $isToday = false;
        }
        
        // Selection de la date du meilleur article
        if ($dateDeLaVeille->format('H') < 10 ) {
            // Si l'heure actuelle est avant 10h, on récupère l'article de la veille de 21h à minuit
            $dateDeLaVeille->modify('-1 day');
            $dateTimeFrom = new DateTime($dateDeLaVeille->format('Ymd210000'));
            $dateTimeTo   = new DateTime($dateDeLaVeille->format('Ymd235959'));
        } elseif ($dateDeLaVeille->format('H') < 13 ) {
            // Si l'heure actuelle est avant 13h, on récupère l'article du jour de minuit à 10h
            $dateTimeFrom = new DateTime($dateDeLaVeille->format('Ymd000000'));
            $dateTimeTo   = new DateTime($dateDeLaVeille->format('Ymd095959'));
        } elseif ($dateDeLaVeille->format('H') < 16 ) {
            // Si l'heure actuelle est avant 16h, on récupère l'article du jour de 10h à 13h
            $dateTimeFrom = new DateTime($dateDeLaVeille->format('Ymd100000'));
            $dateTimeTo   = new DateTime($dateDeLaVeille->format('Ymd125959'));
        } elseif ($dateDeLaVeille->format('H') < 19 ) {
            // Si l'heure actuelle est avant 19h, on récupère l'article du jour de 13h à 16h
            $dateTimeFrom = new DateTime($dateDeLaVeille->format('Ymd130000'));
            $dateTimeTo   = new DateTime($dateDeLaVeille->format('Ymd155959'));
        } elseif ($dateDeLaVeille->format('H') < 21 ) {
            // Si l'heure actuelle est avant 21h, on récupère l'article du jour de 16h à 19h
            $dateTimeFrom = new DateTime($dateDeLaVeille->format('Ymd160000'));
            $dateTimeTo   = new DateTime($dateDeLaVeille->format('Ymd185959'));
        } else {
            // Sinon on récupère l'article du jour de 19h à 21h
            $dateTimeFrom = new DateTime($dateDeLaVeille->format('Ymd190000'));
            $dateTimeTo   = new DateTime($dateDeLaVeille->format('Ymd205959'));
        }
    }
    
    $meilleursArticlesDuJour =  getMeilleursArticlesDuJour($mysqli, $dateTimeFrom, $dateTimeTo);
    $meilleursArticlesDuJourRss  = '';
    foreach ($meilleursArticlesDuJour as $meilleurArticleDuJour) {
            //Récupération d'une capture d'écran du site
            $imgMiniCapturePath = captureUrl($meilleurArticleDuJour['article_url'], $meilleurArticleDuJour['id_commun'], 450, 450, true);
            
            $faviconPath = 'img/favicon/63a61a22845f07c89e415e5d98d5a0f5.ico';

            $faviconGifPath = sprintf('img/favicon/%s.gif', $meilleurArticleDuJour['id_rss']);
            if(is_file($faviconGifPath)) {
               $faviconPath = $faviconGifPath;
            } else {
                $faviconIcoPath = sprintf('img/favicon/%s.ico', $meilleurArticleDuJour['id_rss']);
                if(is_file($faviconIcoPath)) {
                   $faviconPath = $faviconIcoPath;
                }
            }
            $avatar = sprintf('<a href="%s"><img class="entete-avatar" width="16" height="16" src="%s"/></a>', $meilleurArticleDuJour['url'], sprintf('%s', $faviconPath));
            
            $meilleursArticlesDuJourRss .= sprintf("<best>
                                <title>%s</title>
                                <link>%s</link>
                                <pubDate>%s</pubDate>
                                <description>%s</description>
                                <url_image>%s</url_image>
                                <rss_titre>%s</rss_titre>
                                <avatar>%s</avatar>
                                <rss_url>%s</rss_url>
                                </best>",
            htmlspecialchars($meilleurArticleDuJour['article_titre']),
            htmlspecialchars($meilleurArticleDuJour['article_url']),
            $meilleurArticleDuJour['date_insert'],
            htmlspecialchars($meilleurArticleDuJour['article_description']),
            $imgMiniCapturePath,
            htmlspecialchars($meilleurArticleDuJour['rss_titre']),
            htmlspecialchars($avatar),
            htmlspecialchars($meilleurArticleDuJour['url'])
        );
    }
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

$extended = false;
if (count($found) > 1) {
    $extended = true;
}

if($afficherMessagerie) {
    $titre = $titrePageMessagerie;
}else{
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
foreach ($found as $idCommun => $item) {
    $count = substr_count($item['description'], "Permalink");
    if ($count < $filtreDePopularite) {
        continue;
    }
    if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
        if(isset($_SESSION['ireadit']['id'][$idCommun])) {
            $readClass = 'read';
        } else {
            $readClass = 'not-read';
        }
        
        $imgMiniCapturePath = captureUrl($item['link'], $idCommun, 200, 200);
        
        $shaarloRss .= sprintf("<item>
                                <title>%s</title>
                                <link>%s</link>
                                <pubDate>%s</pubDate>
                                <description>%s</description>
                                <category>%s</category>
                                <read_class>%s</read_class>
                                <id_commun>%s</id_commun>
                                <url_image>%s</url_image>
                                <popularity>%s</popularity>
                                <dernier_auteur>%s</dernier_auteur>
                                <dernier_auteur_favicon>%s</dernier_auteur_favicon>
                                <derniere_date_maj>%s</derniere_date_maj>
                                </item>",
            htmlspecialchars($item['title']),
            htmlspecialchars($item['link']),
            $item['pubDate'],
            htmlspecialchars($item['description']),
            htmlspecialchars($item['category']),
            $readClass,
            $idCommun,
            $imgMiniCapturePath,
            $item['pop'],
            htmlspecialchars($item['dernier_auteur']),
            htmlspecialchars($item['dernier_auteur_favicon']),
            htmlspecialchars($item['derniere_date_maj'])
        );
    } else {
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
}

//Ajout des meilleurs articles au fil
if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
    $shaarloRss .= $meilleursArticlesDuJourRss;
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
            , 'isToday' => $isToday
            , 'afficher_messagerie' => $afficherMessagerie
            , 'extended' => $extended 
            , 'menu_locked' => $menuLocked
            )
            );
        $index = sanitize_output($index);
        header('Content-Type: text/html; charset=utf-8');
        echo $index;
}

