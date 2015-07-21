<?php 

require_once('Controller.class.php');
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

class River extends Controller
{
    public function run() 
    {
        $a = microtime(true);
        global $SHAARLO_URL, $DATA_DIR, $CACHE_DIR_NAME, $ARCHIVE_DIR_NAME, $MAX_FOUND_ITEM, $MIN_FOUND_ITEM, $MOD, $ACTIVE_WOT, $ACTIVE_YOUTUBE, $MY_SHAARLI_FILE_NAME, $MY_RESPAWN_FILE_NAME, $ACTIVE_NEXT_PREVIOUS;
        $mysqli = shaarliMyConnect();
        // Chargement de la configuration du shaarliste
        if(!is_null(get('shaarli'))) {
            loadConfiguration(get('shaarli'));
        }

        if (!is_null(get('do')) && get('do') == 'logout') {
            
            unset($_COOKIE['shaarlieur']);
            unset($_COOKIE['shaarlieur_hash']);
            setcookie('shaarlieur', null, -1, '.shaarli.fr');
            setcookie('shaarlieur_hash', null, -1, '.shaarli.fr');
            
            // or this would remove all the variables in the session, but not the session itself 
             session_unset(); 
             
             // this would destroy the session variables 
             session_destroy(); 
        }

        $sessionId = getUtilisateurId();
        $username = null;
        $pseudo = null;
        

        if (isset($_SESSION['username'])) {
            $username = $_SESSION['username'];
        }
        if (isConnected()) {
            $username = $_SESSION['shaarlieur_id'];
        } else {
            if (!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
                header('Location: dashboard.php');
                return;
            }
        }

        if (isset($_SESSION['pseudo'])) {
            $pseudo = $_SESSION['pseudo'];
        }
        $myshaarli = getShaarliUrl();
        /*
         * Lock du menu
         */
        $menuLocked = isMenuLocked();

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
                $idRssMessagerie = $matches[1][0];
                if ($idRssMessagerie === getIdOkRss()) {
                    $afficherMessagerie = true;
                    $filtreDePopularite = 2;
                    $titrePageMessagerie = sprintf('Messagerie de %s', getRssTitleFromId($mysqli, $matches[1][0]));
                }
            }
        }
        $filterOn = null;
        if (isset($_GET['show_form'])) {
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



        // http://www.shaarli.fr/shaarli/
        if (isset($_GET['do']) && $_GET['do'] === 'rss') {
            $usernameRecherche='';
            if(isset($_GET['u'])) {
                $usernameRecherche = $_GET['u'];
            }
            $abonnements = getAbonnements($usernameRecherche);
        }else{
            $abonnements = getAbonnements();
            if(empty($abonnements)) {
                header('Location: dashboard.php', true, 301);
                return;
            }else{
                $usernameRecherche=$username;
            }
        }

        // Ajout d'un tag ignoré
        if (isset($_GET['do']) && $_GET['do'] === 'add_ignored_tag' && !empty($_GET['tag'])) {
            addNotAllowedTags($_GET['tag']);
        }
        
        // Filtre sur les tags
        $tags = array();
        // Envoyé depuis l'appel ajax
        if (!empty($_GET['tags_json'])) {
            $tags = json_decode($_GET['tags_json'], true);
        } elseif (!empty($_GET['tags'])) {
            if (!is_array($_GET['tags'])) {
                $tags[] = $_GET['tags'];
            } else {
                $tags = $_GET['tags'];
            }
        } else {
            // Dans la session
            $tags = getTags();
        }
        
        // N'affiche que le html d'un article
        $displayOnlyArticle = false;
            
        // Affiche le dernier article d'un utilisateur
        if (isset($_GET['getLastArticleFromUserId']) && getIdRss()) {
            $lastIdCommun = getLastIdCommunFromIdRss($mysqli, getIdRss());
            if (empty($lastIdCommun)) {
                die;
            }
            $q = 'id:' . $lastIdCommun;
            $displayOnlyArticle = true;
        }

        $displayOnlyUnreadArticles = false;
        if (!$afficherMessagerie) {
            $displayOnlyUnreadArticles = displayOnlyUnreadArticles();
        }
                
        $articles = getAllArticlesDuJour($mysqli, $usernameRecherche, $q, $filtreDePopularite, $sortBy, $sort, $from, $to, $limit, $tags, $displayOnlyUnreadArticles);

        $displayShaarlistesNonSuivis = displayShaarlistesNonSuivis();
        $isModeRiver = isModeRiver();
        $displayEmptyDescription = displayEmptyDescription();

        $clefAbonnements = array_keys($abonnements);
        if (isset($_GET['test'])) {
            var_dump($abonnements);
        }
        // Regroupement des articles
        $found = array();
        
        foreach($articles as $article) {
            // Filtre sur les articles sans description
            if (!$displayEmptyDescription
              && ($article['article_description'] =='' || 
                preg_match('#^\(<a href="[^"]+">Permalink</a>\)$#', $article['article_description']))
            ) {
                continue;
            }

            $articleDateTime = new DateTime($article['article_date']);
            // Si l'utilisateur ne suit pas ce shaarliste, on saute
            if (!in_array($article['id_rss'], $abonnements) && false === $displayShaarlistesNonSuivis) {
                continue;
            }
            $rssTitre = $article['rss_titre'];
            $followUrl = '';
            
            if (getUtilisateurId() !== '') {
                if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
                    if(!in_array($article['id_rss'], $abonnements)) {
                        $followUrl = ' (<a href="#" onclick="javascript:addAbo(this,\'' . $article['id_rss'] . '\', \'add\');return false;">Suivre</a>)';
                    }else{
                        $followUrl = ' (<a href="#" onclick="javascript:addAbo(this,\'' . $article['id_rss'] . '\', \'delete\');return false;">Se désabonner</a>)';
                    }            
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
            
            //if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
            //    $iconeMessagerie = sprintf('<a href="?q=shaarli%%3A%s"><img class="display-inline-block-text-bottom  opacity-7" width="15" height="15" src="img/mail.gif"></a>', $article['id_rss']);
            //}

            if(isset($shaarliBaseUrl[0])) {
                $shaarliBaseUrl = $shaarliBaseUrl[0];

                $pseudoClass = '';
                // Si le shaarlieur == le shaarliste
                if ($article['id_rss'] == getIdOkRss()) {
                    $pseudoClass = 'shaarlieur';
                }
                $rssTitreAffiche = sprintf('<a href="%s" class="%s">%s</a> %s', $shaarliBaseUrl, $pseudoClass, $rssTitreAffiche, $iconeMessagerie);
            }
            //if(isset($found[$article['id_commun']]) && !empty($article['rss_titre_origin'])) {
            if(!empty($article['rss_titre_origin']) && $article['id_rss_origin'] != $article['id_rss']) {
                $rssTitreAffiche = sprintf('%s > <a href="%s">%s</a> ', $rssTitreAffiche, $article['article_url'], $article['rss_titre_origin']);
            } 
            
            $faviconPath ='';
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
                    $img = sprintf('<div class="columns large-1 small-3"><a href="%s"><img class="entete-avatar" width="50" height="50" src="%s"/></a></div>', $shaarliBaseUrl, sprintf('%s', $faviconPath));
                }
                if($articleDateTime->format('Ymd') == $today->format('Ymd')) {
                    $dateAffichee = date('H:i', $articleDateTime->getTimestamp());
                } else {
                    $dateAffichee = date('d/m/Y', $articleDateTime->getTimestamp());
                }

                if (displayImages()) {
                    $description = sprintf('%s<div class="columns large-11 small-9"><span class="entete-pseudo"><b>%s</b><span class="mini-on-smartphone opacity-test-3">%s</span> </span></div><br/><div class="columns large-11 small-9 right"> %s %s</div><br/><br/><div class="clear"></div>', 
                        $img,
                        $rssTitreAffiche, 
                        $dateAffichee, 
                        str_replace('<br>', '<br/>', $article['article_description']),
                        $followUrl
                    );
                } else {
                    $description = sprintf('<div class="columns large-12 small-12"><span class="entete-pseudo"><b>%s</b><span class="mini-on-smartphone opacity-test-3">%s</span> </span></div><br/><div class="columns large-12 small-12"> %s %s</div><br/><br/><div class="clear"></div>', 
                        $rssTitreAffiche, 
                        $dateAffichee, 
                        str_replace('<br>', '<br/>', $article['article_description']),
                        $followUrl
                    );
                }
                

            } else {
                // Si le message a été censuré, on affiche un message
                $description = sprintf("<b>%s</b> %s <br/> %s $followUrl<br/><br/>", $rssTitreAffiche, date('d/m/Y \à H:i', $articleDateTime->getTimestamp()), str_replace('<br>', '<br/>', '<span title="Ce contenu ne correspond pas aux règles de ce site web.">-- Commentaire censuré --</span>'));  
            }

            // Les balises html sont "normalement" déjà htmlentitées dans le rss, mais on sait jamais
            $description = str_replace('<script', '&lt;script', $description);

            if($articleDateTime->format('Ymd') == $today->format('Ymd')) {
                $derniereDateMaj = $articleDateTime->format('H:i');
            } else {
                $derniereDateMaj = $articleDateTime->format('d/m');
            }
            $dernierAuteur = $article['rss_titre'];
            $popularity=0;
            $articleDate = $article['article_date'];
            $articleTags = '';
            $nouveauxTags = trim(strtolower($article['tags']));
            if (!empty($nouveauxTags)) {
                $articleTags .= $nouveauxTags;
            }

            $discussion = array();

            // C'est un de mes posts
            if (isShaarliste()) {
                if (getIdOkRss() === $article['id_rss']) {
                    $idPostShaarli = sprintf('%s_%s', substr($article['article_date'], 0, 8), substr($article['article_date'], 8, 6));
                    $discussion['edit_link'] = sprintf('%s?source=bookmarklet&edit_link=%s', getShaarliUrl(), $idPostShaarli);
                } else {
                    $discussion['comment_link'] = sprintf('%s?source=bookmarklet&post=%s', getShaarliUrl(), $article['article_uuid']);
                }
            }
            

            $discussion['permalink'] = $article['article_uuid'];
            $discussion['description'] = $description;

            $articleFirstDate = $article['article_date'];
            if(isset($found[$article['id_commun']])) {
                $discussions[$article['id_commun']][] = $discussion;
                $description .= $found[$article['id_commun']]['description'];
                $nouveauxTags = trim(strtolower($found[$article['id_commun']]['tags']));
                if (!empty($nouveauxTags)) {
                    if (!empty($articleTags)) {
                        $articleTags .= ',';
                    }
                    $articleTags .= $nouveauxTags;
                }
                $popularity = $found[$article['id_commun']]['pop'] + 1;
                $articleDate = $found[$article['id_commun']]['date'];
                $dernierAuteur = $found[$article['id_commun']]['dernier_auteur'];
                $faviconPath = $found[$article['id_commun']]['dernier_auteur_favicon'];
                $derniereDateMaj = $found[$article['id_commun']]['derniere_date_maj'];
            } else {
                $discussions[$article['id_commun']] = array($discussion);
            }
            


            $imgMiniCapturePath = captureUrl($article['article_url'], $article['id_commun'], 200, 200);
            $imgCapturePathMax = getImgPathFromId($article['id_commun']);
            if(isset($_SESSION['ireadit']['id'][$article['id_commun']])) {
                $readClass = 'read';
            } else {
                $readClass = 'not-read';
            }
            if ($isModeRiver) {
                $found[] = array('description' => $description, 
                                                      'title' =>  $article['article_titre'], 
                                                      'link' => $article['article_url'],
                                                      'pubDate' => $articleDateTime->format(DateTime::RSS),
                                                      'date' => $articleDate,
                                                      'first_date' => $articleFirstDate,
                                                      'category' => '',
                                                      'pop' => $popularity,
                                                      'tags' => $articleTags,
                                                      'rand' => rand(),
                                                      'dernier_auteur' => $dernierAuteur,
                                                      'dernier_auteur_favicon' => $faviconPath,
                                                      'derniere_date_maj' => $derniereDateMaj,
                                                      'read-class' => $readClass,
                                                      'id_commun' => $article['id_commun'],
                                                      'url_image' => $imgMiniCapturePath,
                                                      'url_image_max' => $imgCapturePathMax
                                                      );
            } else {
                $found[$article['id_commun']] = array('description' => $description, 
                                                          'title' =>  $article['article_titre'], 
                                                          'link' => $article['article_url'],
                                                          'pubDate' => $articleDateTime->format(DateTime::RSS),
                                                          'date' => $articleDate,
                                                          'first_date' => $articleFirstDate,
                                                          'category' => '',
                                                          'pop' => $popularity,
                                                          'tags' => $articleTags,
                                                          'rand' => rand(),
                                                          'dernier_auteur' => $dernierAuteur,
                                                          'dernier_auteur_favicon' => $faviconPath,
                                                          'derniere_date_maj' => $derniereDateMaj,
                                                          'read-class' => $readClass,
                                                          'id_commun' => $article['id_commun'],
                                                          'nb_clic' => $article['nb_clic'],
                                                          'url_image' => $imgMiniCapturePath,
                                                          'url_image_max' => $imgCapturePathMax,
                                                          'discussions' => $discussions[$article['id_commun']]
                                                          );  
            }
        }

        // Si on ne décide de ne pas afficher les articles 
        // qui pointent vers des sites bloqués par l'utilisateur
        $notAllowedUrls = getNotAllowedUrls();
        if (!empty($notAllowedUrls)) {
            foreach ($found as $k => $article) {
                foreach ($notAllowedUrls as $notAllowedUrl) {
                    if (strpos($article['link'], $notAllowedUrl) !== false) {
                        unset($found[$k]);
                    }
                }
            }
        }

        // Si on ne décide de ne pas afficher les articles reshaarlier
        if (isset($_GET['mode_infinite']) && isset($_GET['to'])) {
            foreach ($found as $k => $article) {
                if ($article['date'] > $_GET['to']) {
                    unset($found[$k]);
                }
            }
        }

        // Si on décide d'afficher uniquement les articles du jour précis
        // eg : first_date < date_du_jour
        if (displayOnlyNewArticles() && $sortBy !== 'rand') {
            foreach ($found as $k => $article) {
                $fromFiltre = date('Ymd000000');
                if (isset($_GET['from'])) {
                    $fromFiltre = new DateTime($_GET['from']);
                    $fromFiltre = $fromFiltre->format('YmdHis');
                }
                
                if ($article['first_date'] < $fromFiltre) {
                    unset($found[$k]);
                }
            }
        }
        
        // Filtre sur les noms de tags 
        $notAllowedTags = getNotAllowedTags();
        if (!empty($notAllowedTags)) {
            foreach ($found as $k => $article) {
                $tagsExploded = explode(',', $article['tags']);
                if (!empty(array_intersect($tagsExploded, $notAllowedTags))) {
                    unset($found[$k]);
                }
            }
        }
 
        // Suppression des tags en doublon       
        foreach ($found as $k => $article) {
            $tagsExploded = explode(',', trim($article['tags']));
            $tagsExploded = array_unique($tagsExploded);
            $found[$k]['tags_array'] = $tagsExploded;
            
            // Evaluation de la catégorie de l'article
            //if (count($found[$k]['tags_array'])  > 0 && count($found[$k]['tags_array']) < 1024) {
            //    $found[$k]['categorie'] = getTopCategorieFromTags($mysqli, $found[$k]['tags_array']);
            //}
            $found[$k]['categorie'] = '';
        }
        


        /*
        * Récupération du "meilleur" article du jour
        */
        $isToday = true;
        $meilleursArticlesDuJour = null;
        if(!isset($_GET['q']) && displayBestArticle()) {
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
            
            $meilleursArticlesDuJour =  getMeilleursArticlesDuJour($mysqli, $dateTimeFrom, $dateTimeTo, 1);
            $meilleursArticlesDuJourRss  = '';
            foreach ($meilleursArticlesDuJour as $k => $meilleurArticleDuJour) {
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
                    
                    $meilleursArticlesDuJour[$k]['url_image'] = $imgMiniCapturePath;
                    $meilleursArticlesDuJour[$k]['avatar'] = $avatar;
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
        if (isExtended() && count($found) > 1) {
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
                    $titre = 'River du ' . $fromDateTime->format('d/m/Y');
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
                    htmlspecialchars($item['url_image']),
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
        //if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
        //    $shaarloRss .= $meilleursArticlesDuJourRss;
        //}

        $shaarloRss .= '</channel></rss>';

        // Affichage
        if (isset($_GET['do']) && $_GET['do'] === 'rss') {
            header('Content-Type: application/rss+xml; charset=utf-8');
            echo sanitize_output($shaarloRss);

            // On repasse en mode utilisateur
            getSession($sessionId);
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

            
            $nodesc = null;
            if(isset($_GET['nodesc'])) {
                $nodesc = $_GET['nodesc'];
            }
            $nbSessions = null;

            $urlCourante = getUrlCourante();
            $urlRss = ajouterParametreGET($urlCourante, 'do', 'rss');
            $urlRss = ajouterParametreGET($urlRss, 'u', getUtilisateurId());
            //echo $urlRss;

            /*
            $logStat = json_decode(file_get_contents('log/stat'));
            $nbSessions = $logStat[0];
            */
            
            $dateDuJour = new DateTime();
            $dateTopHier = new DateTime('-1 day');
            $dateMoinsUneSemaine = new DateTime('-7 days');
            $dateMoinsUnMois = new DateTime(date('Ym00'));
            
            $hrefTopJour = sprintf('?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc', $dateDuJour->format('Ymd'), $dateDuJour->format('Ymd'));
            $hrefTopHier = sprintf('?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc', $dateTopHier->format('Ymd'), $dateTopHier->format('Ymd'));
            $hrefTopSemaine = sprintf('?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc', $dateMoinsUneSemaine->format('Ymd'), $dateDuJour->format('Ymd'));
            $hrefTopMois = sprintf('?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc', $dateMoinsUnMois->format('Ymd'), $dateDuJour->format('Ymd'));

            if (isset($_GET['display_only_article'])) {
                $displayOnlyArticle = true;
            }


            $params = array('sort' => $reversedSorts[$sort]
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
                , 'rss_url' => $urlRss
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
                , 'found' => $found
                , 'meilleurs_article_du_jour' => $meilleursArticlesDuJour
                , 'href_top_jour' => $hrefTopJour
                , 'href_top_hier' => $hrefTopHier
                , 'href_top_semaine' => $hrefTopSemaine
                , 'href_top_mois' => $hrefTopMois
                , 'displayOnlyArticle' => $displayOnlyArticle
                , 'displayBlocConversation' => displayBlocConversation()
                , 'tags_json'   => json_encode($tags)
                );
            header('Content-Type: text/html; charset=utf-8');
            $this->render($params);
        }
    }

    public function render($params=array())
    {
        // Protection des paramètres 
        $params = $this->htmlspecialchars($params, array('found'));

        if (!$params['displayOnlyArticle']) {
        ?><!doctype html>
        <html class="no-js" lang="en">
            <?php
            $this->renderHead($params['rss_url']);
            ?>

            <body>
                <?php
                $this->renderMenu('Shaarli.fr', $params['rss_url']);
                ?>

                <?php
                if (useTopButtons() && !$params['afficher_messagerie']) {
                    ?>
                    <div class="row">
                        <div class="columns large-12 text-center show-for-medium-up">
                            <a class="button" href="<?php eh($params['href_top_jour']); ?>">Top du jour</a>
                            <a class="button" href="<?php eh($params['href_top_hier']); ?>">Top d'hier</a>
                            <a class="button" href="<?php eh($params['href_top_semaine']);?>">Top hebdo</a>
                            <!--<a class="button" href="<?php eh($params['href_top_mois']); ?>">Top du mois</a>-->
                        </div>
                        <div class="columns large-12 text-center show-for-small-only">
                            <a class="button tiny" href="<?php eh($params['href_top_jour']); ?>">Top du jour</a>
                            <a class="button tiny" href="<?php eh($params['href_top_hier']); ?>">Top d'hier</a>
                            <a class="button tiny" href="<?php eh($params['href_top_semaine']);?>">Top hebdo</a>
                            <!--<a class="button" href="<?php eh($params['href_top_mois']); ?>">Top du mois</a>-->
                        </div>
                    </div>
                    <br/>
                    <?php
                }
                ?>
                <?php if (!$params['afficher_messagerie']) { ?>
                <form method="GET" action="index.php" id="searchform" class="<?php if('yes' == $params['filter_on']) { echo 'hidden'; } ?>">
                    <div>
                        <div class="columns large-12">
                            <div class="fake-panel">
                                <input id="searchbar" type="text" name="q" placeholder="Rechercher un article" value="<?php eh($params['searchTerm']); ?>"/>
                                <input name="from" type="hidden" value="20130000"></input>
                                <input name="to" type="hidden" value="90130000"></input>
                            </div>
                        </div>
                    </div>
                    
                    <div class="fake-panel show-for-medium-up">
                        <div class="columns large-12 text-right">
                            <a onclick="option_extend(this)">Avancé</a>
                        </div>
                    </div>
                </form>
                <?php } ?>
                <div style="display:none;" id="div-tags-json" data-tags-json="<?php eh($params['tags_json']);?>"></div>

                <div class="pagination">
                    <div id="bloc-filtre" class="<?php if(!$params['filter_on']) { echo 'hidden'; } ?>">
                        <form action="index.php" method="GET">
                            <input type="hidden" name="show_form" />
                            <div class="row">
                                <div class="column large-12">
                                    <div class="panel fake-panel">
                                        <div class="row">
                                            <div class="column large-12">
                                                <h4>Filtrer les articles</h4>
                                            </div>
                                        </div>
                                        <hr/>
                                        <div class="row">
                                            <div class="column large-12">
                                                <h5>Recherche pas mots clefs</h5>    
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="column large-6">
                                                <label for="sortBy">Mot(s) clef(s)</label>
                                            </div>
                                            <div class="column large-6">
                                                <input type="text" name="q" placeholder="shaarli,linux,..." value="<?php eh($params['searchTerm']); ?>"/>
                                            </div>
                                        </div>
                                        <hr/>
                                        <div class="row">
                                            <div class="column large-12">
                                                <h5>Filtre par popularité</h5>    
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="column large-6">
                                                <label for="pop">Popularité</label>
                                            </div>
                                            <div class="column large-6">
                                                <input id="pop" name="pop" type="number" value="<?php eh($params['filtre_popularite']); ?>" min="0"></input>
                                            </div>
                                        </div>
                                        <hr/>
                                        <div class="row">
                                            <div class="column large-12">
                                                <h5>Limite</h5>    
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="column large-6">
                                                <label for="limit">Nombre d'article à afficher</label>
                                            </div>
                                            <div class="column large-6">
                                                <input value="<?php eh(min($params['limit'], $params['min_limit'])); ?>" id="limit" name="limit" type="number" min="0" max="<?php echo($params['max_limit']); ?>" />
                                            </div>
                                        </div>
                                        <hr/>
                                        <div class="row">
                                            <div class="column large-12">
                                                <h5>Période</h5>    
                                            </div>
                                        </div>
                                        <div class="row" data-equalizer>
                                            <div class="column large-1 left" data-equalizer-watch>
                                                <label for="from">Du</label>
                                            </div>
                                            <div class="column large-4 left" data-equalizer-watch>
                                                <input id="from" name="from" type="date" value="<?php eh($params['date_from']); ?>"></input>
                                            </div>
                                            <div class="column large-1 left" data-equalizer-watch>
                                                <label for="to">Au</label>
                                            </div>
                                            <div class="column large-4 left" data-equalizer-watch>
                                                <input id="to" name="to" type="date" value="<?php eh($params['date_to']); ?>" max="<?php eh($params['max_date_to']); ?>"></input>
                                            </div>
                                        </div>
                                       <hr/>
                                        <div class="row">
                                            <div class="column large-12">
                                                <h5>Options de tri</h5>    
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="column large-3">
                                                <label for="sortBy">Trier par</label>
                                            </div>
                                            <div class="column large-3">
                                                <select id="sortBy" name="sortBy">
                                                    <option value="date" <?php if($params['sortBy'] == 'date') { echo 'selected="selected"'; } ?> >Date</option>
                                                    <option value="pop" <?php if($params['sortBy'] == 'pop') { echo 'selected="selected"'; } ?> >Popularité</option>
                                                </select>
                                            </div>
                                            <div class="column large-3">
                                                <label for="sort">Par ordre</label>
                                            </div>
                                            <div class="column large-3">
                                                <select name="sort">
                                                    <option value="desc" <?php if($params['sort'] == 'desc') { echo 'selected="selected"'; } ?> >Décroissant</option>
                                                    <option value="asc" <?php if($params['sort'] == 'asc') { echo 'selected="selected"'; } ?> >Croissant</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="column large-12 text-right">
                                                <input class="button" id="valider" type="submit" value="Rechercher" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="clear"></div>
                
                <br/>
                <?php if (!$params['afficher_messagerie'] && $params['displayBlocConversation'] && !empty($params['my_shaarli'])) { ?>
                <div class="">
                    <div class="columns large-12">
                        <div class="fake-panel">
                            <form id="form-conversation" target="_blank" method="GET" action="<?php eh($params['my_shaarli']); ?>">
                                <input type="hidden" name="post" value="" />
                                <input type="hidden" name="title" value="..." />
                                <input type="hidden" name="source" value="bookmarklet" />
                                <textarea data-input-conversation-id="conversation" class="textarea-conversation" name="description" placeholder="Dire quelque chose"></textarea>
                                <input class="button tiny right hidden" id="conversation" type="button" value="Converser" />
                            </form>
                        </div>
                    </div>
                </div>
                
                <div id="div-last-user-article"></div>
                <?php } ?>

                <?php if (!$params['afficher_messagerie']) { ?>
                <div class="columns large-12">
                    <div class="fake-panel text-right">
                        <?php if($params['date_hier']) { ?>
                            <a href="?from=<?php eh($params['date_hier']);?>000000&amp;to=<?php eh($params['date_hier']);?>235959">Jour précédent</a>
                        <?php } ?>
                        <?php if($params['date_demain']) { ?>
                            <a href="?from=<?php eh($params['date_demain']);?>000000&amp;to=<?php eh($params['date_demain']);?>235959">/ Jour suivant</a>
                        <?php } ?>
                        <div style="display:none;" id="div-date-precedente" data-date-precedente-from="<?php eh($params['date_hier']);?>000000" data-date-precedente-to="<?php eh($params['date_hier']);?>235959"></div>
                    </div>
                </div>
                <?php } ?>
                <?php
                if ($params['meilleurs_article_du_jour'] && !$params['afficher_messagerie']) {
                    foreach( $params['meilleurs_article_du_jour'] as $meilleurArticleDuJour) {
                    ?>
                    <div class="column large-12">
                        <div class="panel article fake-panel">
                            <div>
                                <div class="column large-8">
                                    <div class="article-mini-titre">
                                        <b>
                                            <?php if($params['isToday']) { ?>
                                                En ce moment sur la shaarlisphère
                                            <?php } else {?>
                                                A ce moment sur la shaarlisphère
                                            <?php } ?>
                                        </b>
                                    </div>
                                    <h3>
                                        <a title="Go to original place" href="<?php eh($meilleurArticleDuJour['article_url']);?>">
                                        <?php eh($meilleurArticleDuJour['article_titre']);?>
                                        </a> 
                                    </h3>
                                    <div class="mini hidden-on-smartphone visible-on-hover color-blue"><?php eh($meilleurArticleDuJour['url']);?></div>
                                    <h4><?php echo ($meilleurArticleDuJour['avatar']);?>
                                    <span class="entete-pseudo"><b><a href="<?php eh($meilleurArticleDuJour['url']);?>"><?php eh($meilleurArticleDuJour['rss_titre']);?></a></b></span>
                                    </h4>
                                    
                                    <div class="article-content"><?php echo ($meilleurArticleDuJour['article_description']);?></div>
                                </div>
                                <div class="column large-4">
                                    <?php if (!empty($meilleurArticleDuJour['url_image'])) { ?>
                                    <a class="thumbnail-modal-reveal" data-reveal-id="thumbnail-<?php eh($meilleurArticleDuJour['id_commun']); ?>" title="Zoom it" href="<?php eh($meilleurArticleDuJour['article_url']); ?>">
                                        <div class="article-thumbnail visible-on-hover" style="background:url('<?php eh($meilleurArticleDuJour['url_image']); ?>'); width:100%;height:450px;background-repeat: no-repeat;background-position: center;"></div>
                                    </a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    }
                }

                
                if (count($params['found']) == 0) {
                    ?>
                    <div class="">
                        <div class="columns large-12">
                            <div class="panel fake-panel article">
                                <div class="">
                                    <div class="columns large-10">
                                    Rien de neuf aujourd'hui. <a href="abonnements.php">Gérer mes abonnements</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                if (!$params['afficher_messagerie']) {
                    ?>
                    <div style="display:none;" class="div-date-suivante" data-date-suivante="201425"></div>
                    <div id="div-container-articles"><?php
                    foreach ($params['found'] as $idCommun => $found) {
                        $this->renderArticle($params, $found);
                    }
                    ?></div>
                    
                    <?php
                } else {
                    ?>
                    <div class="panel fake-panel">
                    <?php
                    foreach ($params['found'] as $idCommun => $found) {
                    ?>
                        <a  href="?q=id:<?php eh($found['id_commun']); ?>" class="add-padding-left-1 add-padding-bottom-1 add-padding-top-1 {read_class}">
                            <div class="truncate display-inline-block-middle no-margin no-padding width-20">
                                <img class="add-margin-right-1 entete-avatar" width="16" height="16" src="<?php eh($found['dernier_auteur_favicon']); ?>"/>
                                <span class="add-margin-right-2"><?php eh($found['dernier_auteur']); ?> (<?php eh($found['pop']); ?>)</span></div>
                            <span class="float-right"><?php eh($found['derniere_date_maj']); ?></span>
                            <div class="truncate display-inline-block-middle no-margin no-padding max-width-60">
                                <span class="add-margin-right-2"><?php eh($found['title']); ?></span>
                            </div>
                            
                        </a>
                        <hr />
                    <?php
                    }
                    ?>
                    </div>
                    <?php
                }
                ?>


                <?php
                $this->renderElevatorButton();
                $this->renderScript($params);
                ?>
            </body>
        </html>

        <?php
        } else {
            ?><div style="display:none;" data-date-precedente-from="<?php eh($params['date_hier']);?>000000" data-date-precedente-to="<?php eh($params['date_hier']);?>235959"></div><?php
            foreach ($params['found'] as $found) {
                $this->renderArticle($params, $found);
            }
        }
    }
    public static function renderArticle($params, $found)
    {
        $class = '';
        $discussions = array_reverse($found['discussions'], true);
        
        if (count($discussions) > 1) { 
            $class = 'toptopic';
        }
        
        ?>
        <div id="div-article-<?php eh($found['id_commun']); ?>" data-id-commun="<?php eh($found['id_commun']); ?>">
            <div class="columns large-12">
                <div class="panel fake-panel article <?php eh($found['read-class']); ?> persist-area">
                    <div class="columns large-11 <?php if(useRefreshButton()) { ?>small-9<? } else {?>small-11<?php } ?>">
                        <h3 class="<?php echo $class; ?> no-margin-bottom">
                            <a title="Go to original place" href="<?php eh($found['link']); ?>" onmouseup="ireadit(this, '<?php eh($found['id_commun']); ?>')"><?php eh($found['title']); ?>
                            <?php 
                            if (count($discussions) > 1) { 
                                echo sprintf('[%s]', count($discussions));
                            }
                            ?>
                            </a>
                        </h3>
                    </div>
                    
                    
                    <?php if(!isInvite()) { ?>
                        <?php if(useRefreshButton()) { ?>
                        <div class="columns large-1 small-3 text-right">
                            <span data-article-id="<?php eh($found['id_commun']); ?>" class="a-refresh-article cercle-fleche"><span id="img-article-refresh-<?php eh($found['id_commun']); ?>" >↺</span></span>
                            <span onclick="return confirmIgnoreIt(<?php eh($found['id_commun']); ?>);" class="croix noselect" data-article-id="<?php eh($found['id_commun']); ?>" title="Cette discussion ne m'intéresse pas">x</span>
                        </div>
                        <?php } else { ?>
                        <div class="columns large-1 small-1 text-right">
                            <span onclick="return confirmIgnoreIt(<?php eh($found['id_commun']); ?>);" class="croix noselect" data-article-id="<?php eh($found['id_commun']); ?>" title="Cette discussion ne m'intéresse pas">x</span>
                        </div>
                        <?php } ?>
                    <?php } ?>
                    
                    <div class="columns large-12">
                        <div class="mini visible-on-hover hidden-on-smartphone color-blue"><?php eh($found['link']); ?></div>
                    </div>
                    <div class="clear"></div>
                    <br/>
                    <div class="columns <?php if (displayImages()) {?>large-10 small-10<?php } ?>">
                        <div class="">
                            <div id="div-description-<?php eh($found['id_commun']); ?>" style="overflow:hidden;" class="columns large-10 <?php if($params['extended']) echo 'extended'; ?>">
                                <?php 
                                $d = 0;
                                foreach($discussions as $discussion) {
                                    $discussionClass = 'div-discussion';
                                    if ($d > 0 && $params['extended']) {
                                        $discussionClass .= ' div-discussion-hidden hidden';
                                    }
                                    if (isset($discussion['edit_link']) || isset($discussion['comment_link'])) {
                                        $discussionClass .= ' div-discussion-edit';
                                    }
                                    ?><div class="<?php echo $discussionClass;?>">
                                        <?php 
                                        if (isset($discussion['edit_link'])) {
                                            ?><a data-article-id="<?php eh($found['id_commun']); ?>" class="icon-edition a-reshaarlier" href="<? eh($discussion['edit_link']); ?>" target="_blank"> </a><?php
                                        } 
                                        elseif (isset($discussion['comment_link'])) {
                                            // Lien de réponse
                                            ?><a data-article-id="<?php eh($found['id_commun']); ?>" class="icon-comment a-reshaarlier" href="<? eh($discussion['comment_link']); ?>" target="_blank"> </a><?php
                                        }
                                        echo $discussion['description'];
                                        ?>
                                    </div><?php
                                    $d++;
                                }
                                ?>
                                <?php 
                                // Bloc commenter
                                if (!empty($params['my_shaarli']) && strpos($found['description'], $params['my_shaarli']) === false ) {
                                ?>
                                <div class="columns <?php if (displayImages()) {?>large-11 small-9 right<?php } else {?>large-12 small-12 <?php } ?>">
                                    <form target="_blank" method="GET" action="<?php echo $params['my_shaarli']; ?>">
                                        <input type="hidden" name="source" value="bookmarklet" />
                                        <input type="hidden" name="title" value="<?php eh($found['title']); ?>" />
                                        <input type="hidden" name="post" value="<?php eh($found['link']); ?>" />
                                        <input type="hidden" name="tags" value="<?php echo implode(' ', $found['tags_array']); ?>" />
                                        <textarea id="textarea-conversation-<?php eh($found['id_commun']); ?>" class="textarea-conversation" data-input-conversation-id="input-conversation-<?php eh($found['id_commun']); ?>" name="description" placeholder="Commenter/Shaarlier"></textarea>
                                        <input data-article-id="<?php eh($found['id_commun']); ?>" id="input-conversation-<?php eh($found['id_commun']); ?>" class="a-reshaarlier button tiny secondary right hidden" type="button" value="Commenter" />
                                    </form>
                                </div>
                                <?php 
                                }
                                ?>
                            </div>
                        </div>
                        
                        <?php if($params['extended'] && count($discussions) > 1) { ?>
                        <div class="clear"></div>
                        <div class="row text-center">
                            <a class="no-margin-bottom button secondary tiny" onclick="extend(this, '#div-description-<?php eh($found['id_commun']); ?>')">Voir la discussion</a>
                        </div>
                        <?php } ?>
                    </div>
                    
                    <?php if (displayImages()) { ?>
                    <div class="columns large-2 small-2">
                        <?php if (!empty($found['url_image'])) { ?>
                        <a class="thumbnail-modal-reveal visible-on-hover" data-reveal-id="thumbnail-<?php eh($found['id_commun']); ?>" title="Zoom it" href="<?php eh($found['link']); ?>">
                            <div class="article-thumbnail" style="background:url('<?php eh($found['url_image']); ?>'); "></div>
                        </a>
                        <div id="thumbnail-<?php eh($found['id_commun']); ?>" class="reveal-modal large" data-reveal aria-labelledby="Miniature" aria-hidden="true" role="dialog">
                            <a target="_blank" title="Go to original place" href="<?php eh($found['link']); ?>">
                                <img data-src="<?php eh($found['url_image_max']); ?>" id="thumbnail-<?php eh($found['id_commun']); ?>-src" src="" />
                            </a>
                            <a class="close-reveal-modal" aria-label="Fermer">&#215;</a>
                        </div>
                        <?php } ?>
                        &nbsp;
                    </div>
                    <?php
                    }
                    
                    ?>
                    <div class="clear"></div>
                    <hr class="mini"/>
                    <div class="article-footer">
                        <div class="columns large-10 text-left">
                        <?php 
                        foreach ($found['tags_array'] as $tag) {
                            if (!empty($tag)) {
                                ?><a href="?tags=<?php eh($tag);?>" class="button microscopic secondary"><?php echo $tag; ?><?php if(!isInvite()) { ?><a href="?do=add_ignored_tag&tag=<?php eh($tag);?>" onclick="return(confirm('Les articles portant ce tag ne seront plus affichés, continuer ? '));" class="button microscopic secondary">X</a><?php } ?></a> <?php
                            }
                        }

                        $nbClics = 0;
                        if ($found['nb_clic'] > 0) {
                            $nbClics = $found['nb_clic'];
                        }
                        
                        ?>
                        </div>
                        <div class="columns large-2 text-right">
                            <span title="<?php echo $found['categorie']; ?>" style="font-size:12px;" class="nb-clics"><?php echo $nbClics; ?></span>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <?php
    }

    public static function renderScript($params = array())
    {
        parent::renderScript($params);
        ?>
        <script>
            function getMy(){
                document.forms["loginform"].action = "https://www.shaarli.fr/my/" + document.getElementById('pseudo').value + "/";
                document.forms["loginform"].submit();
            }       
            function showDashboard(){
                document.getElementById('content').className = 'dashboarded';
                addClass(document.getElementById('panel-best'), 'dashboarded');
                document.getElementById("dashboard_icon").style.display="none";
                document.getElementById("dashboard").style.display="block";
            }
            function hideDashboard(){
                document.getElementById('content').className = '';
                removeClass(document.getElementById('panel-best'), 'dashboarded');
                document.getElementById("dashboard_icon").style.display="block";
                document.getElementById("dashboard").style.display="none";
            }                    
            function extend(him, id) {
                $(id).removeClass('extended');
                $(id).find('.div-discussion-hidden').removeClass('hidden');
                him.innerHTML = 'Cacher la discussion';
                him.onclick =  function(){ shorten(him, id); } ;
            }
            function shorten(him, id) {
                $(id).addClass('extended');
                $(id).find('.div-discussion-hidden').addClass('hidden');
                him.innerHTML = 'Voir la discussion';
                him.onclick =  function(){ extend(him, id); } ;
            }
            function option_extend(him) {
                removeClass(document.getElementById('bloc-filtre'), 'hidden');
                addClass(document.getElementById('searchform'), 'hidden');
                addClass(him, 'hidden');
            }
            function removeClass(el, name)
            {
                if (hasClass(el, name)) {
                    el.className=el.className.replace(new RegExp('(\\s|^)'+name+'(\\s|$)'),' ').replace(/^\s+|\s+$/g, '');
                }
            }
            function hasClass(el, name) {
                return new RegExp('(\\s|^)'+name+'(\\s|$)').test(el.className);
            }

            function addClass(el, name)
            {
                if (!hasClass(el, name)) { el.className += (el.className ? ' ' : '') +name; }
            }

            function getChar(event) {
                if (event.which == null) {
                    return event.keyCode;
                } else if (event.which!=0 && event.charCode!=0) {
                    return event.which;
                } else {
                    return null;
                }
            }

            /**
            * Fait un appel ajax pour ignorer un article
            */
            function ignoreit(him, id) 
            {
                var r = new XMLHttpRequest(); 
                var params = "do=ignoreit&id=" + id;
                r.open("POST", "add.php", true); 
                r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                r.onreadystatechange = function () {
                    if (r.readyState == 4) {
                        if(r.status == 200){
                            $('#div-article-' + id).hide();
                        }
                        return true; 
                    }
                };
                r.send(params);
            }
            
            function ireadit(him, id) 
            {
                var r = new XMLHttpRequest(); 
                var params = "do=ireadit&id=" + id;
                r.open("POST", "add.php", true); 
                r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                r.onreadystatechange = function () {
                    if (r.readyState == 4) {
                        if(r.status == 200){
                            var blocArticle = him.parentNode.parentNode.parentNode;
                            removeClass(blocArticle, 'not-read');
                            addClass(blocArticle, 'read');
                        }
                        return true; 
                    }
                };
                r.send(params);
            }
            
            function save_lock(state) 
            {
                var r = new XMLHttpRequest(); 
                var params = "do=lock&state=" + state;
                r.open("POST", "add.php", true); 
                r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                r.onreadystatechange = function () {
                    if (r.readyState == 4) {
                        return true; 
                    }
                };
                r.send(params);
            }
            
            function lock_menu(him, elementId)
            {
                addClass(document.getElementById('header'), 'add-padding-top-8');
                addClass(document.getElementById(elementId), 'position-fixed');
                addClass(him, 'icon-lock');
                removeClass(him, 'icon-open');
                document.getElementById(elementId).onclick = function () {scroll(0, 0);};
                him.onclick = function () {open_menu(him, elementId);};
                save_lock('lock');
            }

            function open_menu(him, elementId)
            {
                removeClass(document.getElementById('header'), 'add-padding-top-8');
                removeClass(document.getElementById(elementId), 'position-fixed');
                removeClass(him, 'icon-lock');
                addClass(him, 'icon-open');
                document.getElementById(elementId).onclick = function () {return true;};
                him.onclick = function () {lock_menu(him, elementId);};
                save_lock('open');
            }   
            
            document.onkeypress = function(event) {
                var char = getChar(event);
                if(char == '339') {
                    var els = document.getElementsByClassName("button-extend");
                    Array.prototype.forEach.call(els, function(el) {
                        extend(el);
                    });
                }
                return true;
            }

            function addAbo(that, id, action) {
                var r = new XMLHttpRequest(); 
                var params = "do="+action+"&id=" + id;
                r.open("POST", "add.php", true); 
                r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                r.onreadystatechange = function () {
                    if (r.readyState == 4) {
                        if(r.status == 200){
                            if(action == 'add') {
                                that.text = 'Se désabonner';
                                that.innerHTML = 'Se désabonner';
                                that.onclick = function () { addAbo(that, id, 'delete'); return false; };
                            }else {
                                that.text = 'Suivre';
                                that.innerHTML = 'Suivre';
                                that.onclick = function () { addAbo(that, id, 'add'); return false; };
                            }
                            return; 
                        }
                        else {
                            that.text = '-Erreur-';
                            return; 
                        }
                    }
                }; 
                r.send(params);
            }
            
            function validerLien(that, id, action) {
                var r = new XMLHttpRequest(); 
                var params = "do="+action+"&id=" + id;
                r.open("POST", "valide.php", true); 
                r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                r.onreadystatechange = function () {
                    if (r.readyState == 4) {
                        if(r.status == 200){
                            if(action == 'valider') {
                                that.text = 'Bloquer';
                                that.innerHTML = 'Bloquer';
                                that.onclick = function () { validerLien(that, id, 'bloquerLien'); return false; };
                            }else {
                                that.text = 'Valider';
                                that.innerHTML = 'Valider';
                                that.onclick = function () { validerLien(that, id, 'validerLien'); return false; };
                            }
                            return; 
                        }
                        else {
                            that.text = '-Erreur-';
                            return; 
                        }
                    }
                }; 
                r.send(params);
            }
        
        /* 
        Lorsque l'utilisateur reshaare un lien
        Un appel de synchro est fait pour récupérer sa réponse
        */
        $(document).on("click", '.a-reshaarlier', function() {
            var articleId = $(this).attr('data-article-id');
            $(this).closest("form").submit();
            $('#textarea-conversation-' + articleId).attr('disabled', 'disabled');
            $(this).attr('disabled', 'disabled');
            
            var shaarliUrl = '<?php echo getShaarliUrl() . '?' ;?>';
            
            $(window).focus(function(){
                setTimeout(
                    function() {
                        synchroShaarli(articleId, shaarliUrl);
                    }
                    , 5 * 1000
                );
                $(window).unbind('focus');
            });
        });

        /*
        Lorsque l'utilisateur ne souhaite plus jamais voir un lien
        */
       $(document).on("click", '.croix', function() {
            var articleId = $(this).attr('data-article-id');
            confirmIgnoreIt(articleId);
        });
        
        function confirmIgnoreIt(articleId) {
            var r = confirm("Attention, cette conversation n'apparaitra plus jamais (pas de retour possible pour l'instant). Continuer ? ");
            if (r == true) {
                ignoreit(this, articleId);
            }
        }
        
        

        /* 
        Lorsque l'utilisateur utilise le bouton Converser
        Un appel de synchro est fait pour récupérer sa réponse
        */
        $(document).on("focus", '.textarea-conversation', function() {
            $('#' + $(this).attr('data-input-conversation-id')).show();
        });
        $(document).on("focusout", '.textarea-conversation', function() {
            if(!$(this).val()) {
                $('#' + $(this).attr('data-input-conversation-id')).hide();
            }
        });

        $('#conversation').on("click", function() {
            $('#form-conversation').submit();
            $('#form-conversation').find('input').attr('disabled', 'disabled');
            $('#form-conversation').find('textarea').attr('disabled', 'disabled');
            $(window).focus(function(){
                synchroShaarliLastArticle();
                $(window).unbind('focus');
            });
        });

        // On récupère le dernier article de l'utilisateur pour l'ajouter à la page courante
        function refreshLastArticle() {
            $.ajax({
              method: "GET",
              url: "index.php",
              data: { getLastArticleFromUserId: "true" }
            }).done(function( msg ) {
                if (typeof($($(msg)[2])) != 'undefined') {
                    var idAAjouter = $($(msg)[2]).attr('id');
                    // On vérifie qu'il n'existe pas déjà dans la page courante ou 
                    // dans le champ prévu pour
                    if ($('#' + idAAjouter).length ==0 && $('#div-last-user-article').find('#' + idAAjouter).length == 0) {
                        $('#div-last-user-article').append(msg);
                        $('.a-refresh-article').unbind('click');
                        $('.a-refresh-article').click(function() {
                            refreshArticle($(this).attr('data-article-id'));
                        });
                        
                        // On purge la valeur de la conversation uniquement si le commentaire a été soumis
                        $('#form-conversation').find('textarea').val('');
                    }

                    // On reactive le champs de conversation
                    $('#form-conversation').find('input').attr('disabled', false);
                    $('#form-conversation').find('textarea').attr('disabled', false);
                    $('#conversation').hide();
                }
            });
        }
        
        synchroShaarli();

        function refreshArticle(articleId, ssiTextePresent) {
            $('#img-article-refresh-' + articleId).addClass('refresh-on');
            $.ajax({
              method: "GET",
              url: "index.php",
              data: { q: "id:"+articleId, display_only_article: "true" }
            }).done(function( msg ) {
                if (typeof(ssiTextePresent) == 'undefined' 
                 || msg.indexOf(ssiTextePresent) != -1
                ) {
                    $('#div-article-' + articleId).replaceWith(msg);
                    $('.a-refresh-article').unbind('click');
                    $('.a-refresh-article').click(function() {
                        refreshArticle($(this).attr('data-article-id'));
                    });
                } else {
                    // On ne raffraichie pas car le commentaire n'est pas retrouvé
                    $('#textarea-conversation-' + articleId).attr('disabled', false);
                    $('#input-conversation-' + articleId).attr('disabled', false);
                }
            });
        }

        $('.a-refresh-article').click(function() {
            var articleId = $(this).attr('data-article-id');
            refreshArticle(articleId);
        });

        <?php if(useScrollInfini() && $params['sortBy'] !== 'pop') { ?>
        var datePrecedenteTo   = $("#div-date-precedente").attr('data-date-precedente-to');
        var datePrecedenteFrom = $("#div-date-precedente").attr('data-date-precedente-from');
        var tagsJson = $("#div-tags-json").attr('data-tags-json');
        
        var appelScrollEnCours = false;
        
        function appelleAnciensArticles() {
            $.ajax({
              method: "GET",
              url: "index.php",
              data: { to:datePrecedenteTo, from:datePrecedenteFrom, display_only_article: "true", mode_infinite: "true", tags_json: tagsJson }
            }).done(function( msg ) {
                datePrecedenteTo   = $($(msg)[0]).attr('data-date-precedente-to');
                datePrecedenteFrom = $($(msg)[0]).attr('data-date-precedente-from');
                var i =0;
                
                $(msg).each(function( index, element ) {
                    
                    if (index==0) {
                        // On affiche directement le premier div qui n'est pas un article
                        $('#div-container-articles').append(element);
                        // S'il n'y a aucun article a part l'entete, on rappelle le site
                        if ($(msg).size() == 1) {
                            appelleAnciensArticles();
                        }
                        return;
                    }

                    setTimeout(function(){
                        $(element).addClass('slider');
                        
                        $('#div-container-articles').append(element);
                        // On active les evenements onclick sur les nouveaux articles
                        $(element).click(function() {
                            refreshArticle($(this).attr('data-article-id'));
                        });
                        // On active le chargement du prochain scroll
                        if((i-1) == index) {
                            appelScrollEnCours = false;
                        }
                    }, i * 100);
                    
                    i++;
                });
            });
        }
        
        /* Detection de l'arrivée en bas de page */
        $(window).scroll(function() {
            if (appelScrollEnCours == false) {
                if($(window).scrollTop() + $(window).height() > $(document).height() - 400) {
                    appelScrollEnCours = true;
                    appelleAnciensArticles();
                }
            }
        });
        <?php } ?>

        $(document).on("click", '.thumbnail-modal-reveal', function() {
            var id = $(this).attr("data-reveal-id");
            var imgElement = $("#"+id+"-src");
            imgElement.attr('src', imgElement.attr('data-src'));
        });
        


        $(document).foundation(); 

        </script>
    <?php
    }
}


$controller = new River();
$controller->run();



