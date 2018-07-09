<?php

namespace ShaarloBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RiverController extends AbstractController
{
    /**
     * Return a token string.
     *
     * @return string
     */
    function getToken()
    {
        $salt = $this->getParameter('salt');
        $rnd = sha1(uniqid('',true).'_'.mt_rand().$salt);  // We generate a random string.
        $_SESSION['tokens'][$rnd]=1;  // Store it on the server side.
        return $rnd;
    }

    /**
     * @Route("/river")
     */
    public function indexAction(Request $request)
    {
        global $SHAARLO_DOMAIN, $MAX_FOUND_ITEM, $MIN_FOUND_ITEM, $ACTIVE_WOT, $ACTIVE_YOUTUBE, $ACTIVE_NEXT_PREVIOUS, $CACHE_DIRECTORY_PATH;

        $mysqlUtils = $this->get('shaarlo.mysql_utils');
        $userOptionsUtils = $this->get('shaarlo.user_options_utils');
        $urlUtils = $this->get('shaarlo.url_utils');

        $cacheDirectoryPath = $this->getParameter('kernel.root_dir').'/../web/cache/';

        $mysqli = $mysqlUtils->shaarliMyConnect();
        // Chargement de la configuration du shaarliste
        if(!is_null($request->get('shaarli'))) {
            loadConfiguration($request->get('shaarli'));
        }

        if (!is_null($request->get('do')) && $request->get('do') == 'logout') {

            unset($_COOKIE['shaarlieur']);
            unset($_COOKIE['shaarlieur_hash']);

            setcookie('shaarlieur', null, -1, $SHAARLO_DOMAIN);
            setcookie('shaarlieur_hash', null, -1, $SHAARLO_DOMAIN);
            session_start();
            // or this would remove all the variables in the session, but not the session itself
            session_unset();

            // this would destroy the session variables
            session_destroy();
        }

        $sessionId = $userOptionsUtils->getUtilisateurId();
        $username = null;
        $pseudo = null;

        if (isset($_SESSION['username'])) {
            $username = $_SESSION['username'];
        }
        if ($userOptionsUtils->isConnected()) {
            $username = $_SESSION['shaarlieur_id'];
        } else {
            if (!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
                return $this->redirectToRoute('shaarlo_dashboard');
            }
        }

        if (isset($_SESSION['pseudo'])) {
            $pseudo = $_SESSION['pseudo'];
        }
        $myshaarli = $userOptionsUtils->getShaarliUrl();
        /*
         * Lock du menu
         */
        $menuLocked = $userOptionsUtils->isMenuLocked();

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
                if ($idRssMessagerie === $userOptionsUtils->getIdOkRss()) {
                    $afficherMessagerie = true;
                    $filtreDePopularite = 2;
                    $titrePageMessagerie = sprintf('Messagerie de %s', $mysqlUtils->getRssTitleFromId($mysqli, $matches[1][0]));
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

        $fromDateTime = new \DateTime();
        $toDateTime = new \DateTime();
        if (isset($_GET['do']) && $_GET['do'] === 'rss') {
            $from = $to = null;
        }else{
            $from = $fromDateTime->format('Ymd000000');
            $to = $toDateTime->format('Ymd235959');
        }

        if (isset($_GET['from'])) {
            try {
                $fromDateTime = new \DateTime($_GET['from']);
                $from = $fromDateTime->format('Ymd000000');
            } catch (\Exception $e) {

            }
        }

        if (isset($_GET['to'])) {
            try {
                $toDateTime = new \DateTime($_GET['to']);
                $to = $toDateTime->format('Ymd235959');
            } catch (\Exception $e) {

            }
        }

        $today = new \DateTime();
        // daily=tomorrow pour bloquer sur hier
        if (isset($_GET['daily']) && $_GET['daily'] == 'tomorrow' ) {
            $tomorrow = $today->modify('-1 DAY');
            $from = $tomorrow->format('Ymd000000');
            $to = $tomorrow->format('Ymd235959');
        }

        if (isset($_GET['do']) && $_GET['do'] === 'rss') {
            $usernameRecherche='';
            if(isset($_GET['u'])) {
                $usernameRecherche = $_GET['u'];
            }
            $abonnements = $userOptionsUtils->getAbonnements($usernameRecherche);
        }else{
            $abonnements = $userOptionsUtils->getAbonnements();
            if(empty($abonnements)) {
                return $this->redirectToRoute('shaarlo_dashboard');
            }else{
                $usernameRecherche=$username;
            }
        }

        // Ajout d'un tag ignoré
        if (isset($_GET['do']) && $_GET['do'] === 'add_ignored_tag' && !empty($_GET['tag'])) {
            $userOptionsUtils->addNotAllowedTags($_GET['tag']);
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
            $tags = $userOptionsUtils->getTags();
        }

        // N'affiche que le html d'un article
        $displayOnlyArticle = false;

        // Affiche le dernier article d'un utilisateur
        if (isset($_GET['getLastArticleFromUserId']) && getIdRss()) {
            $lastIdCommun = $userOptionsUtils->getLastIdCommunFromIdRss($mysqli, getIdRss());
            if (empty($lastIdCommun)) {
                die;
            }
            $q = 'id:' . $lastIdCommun;
            $displayOnlyArticle = true;
        }

        $displayOnlyUnreadArticles = false;
        if (!$afficherMessagerie && !preg_match('#^id:([0-9a-f]{32})$#', $q)) {
            $displayOnlyUnreadArticles = $userOptionsUtils->displayOnlyUnreadArticles();
        }

        $cacheArticlesFilePath = sprintf('%s/articles-%s', $cacheDirectoryPath, md5($usernameRecherche.var_export($_GET, true).var_export($tags, true))) ;
        $cacheExpireTime = time() - 120 ;
        if(file_exists($cacheArticlesFilePath) && filemtime($cacheArticlesFilePath) > $cacheExpireTime) {
            $articles = json_decode(file_get_contents($cacheArticlesFilePath), true);
        } else {
            $articles = $mysqlUtils->getAllArticlesDuJour($mysqli, $usernameRecherche, $q, $filtreDePopularite, $sortBy, $sort, $from, $to, $limit, $tags, $displayOnlyUnreadArticles);
            file_put_contents($cacheArticlesFilePath, json_encode($articles));
        }
        $displayShaarlistesNonSuivis = $userOptionsUtils->displayShaarlistesNonSuivis();
        $isModeRiver = $userOptionsUtils->isModeRiver();
        $displayEmptyDescription = $userOptionsUtils->displayEmptyDescription();

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

            $discussion = array();

            $articleDateTime = new \DateTime($article['article_date']);
            // Si l'utilisateur ne suit pas ce shaarliste, on saute
            if (!in_array($article['id_rss'], $abonnements) && false === $displayShaarlistesNonSuivis) {
                continue;
            }
            $rssTitre = $article['rss_titre'];
            $followUrl = '';


            // Le shaarliste est aussi un shaarlieur
            $discussion['shaarlieur_pseudo'] = null;
            // Seul les comptes protégés par mdp peuvent se faire poussinés
            if (!empty($article['shaarlieur_pwd'])) {
                $discussion['shaarlieur_pseudo'] = $article['shaarlieur_pseudo'];
                //$followUrl .= ' (<a href="dashboard.php?shaarliste='.($discussion['shaarlieur_pseudo']).'" >Profil</a>)';
            }

            if (getUtilisateurId() !== '') {
                if (empty($article['shaarlieur_pseudo'])) {
                    if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
                        if(!in_array($article['id_rss'], $abonnements)) {
                            $followUrl = ' (<a href="#" onclick="javascript:lienAddAbo(this,\'' . $article['id_rss'] . '\', \'add\');return false;">Suivre</a>)';
                        }else{
                            $followUrl = ' (<a href="#" onclick="javascript:lienAddAbo(this,\'' . $article['id_rss'] . '\', \'delete\');return false;">Se désabonner</a>)';
                        }
                    }
                } else {
                    $followUrl = ' (<a href="dashboard.php?shaarliste='.htmlentities($article['shaarlieur_pseudo']).'">Profil</a>)';
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

            if(strpos($article['article_uuid'], 'my.shaarlo.fr') > 0) {
                $rssTitreAffiche = '@' . $rssTitreAffiche;
            }

            $shaarliBaseUrl = explode('?', $article['article_uuid']);
            //ajout de l'icone de messagerie ssi non mode rss
            $iconeMessagerie = '';

            //if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
            //    $iconeMessagerie = sprintf('<a href="?q=shaarli%%3A%s"><img class="display-inline-block-text-bottom  opacity-7" width="15" height="15" src="img/mail.gif"></a>', $article['id_rss']);
            //}
            $discussion['shaarliste_class'] = null;
            if(isset($shaarliBaseUrl[0])) {
                $shaarliBaseUrl = $shaarliBaseUrl[0];

                $pseudoClass = '';
                // Si le shaarlieur == le shaarliste
                if ($article['id_rss'] == $userOptionsUtils->getIdOkRss()) {
                    $discussion['shaarliste_class'] = 'shaarlieur';
                }
            }
            $faviconPath ='';


            $discussion['shaarliste_href'] = $shaarliBaseUrl;
            if($article['active'] !== '1') {
                continue;
            }
            $discussion['avatar_src'] = null;
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

                //$img = sprintf('', $shaarliBaseUrl, sprintf('%s', $faviconPath));
                $discussion['avatar_src'] = sprintf('%s', $faviconPath);
            }
            $discussion['shaarliste_titre'] = $rssTitreAffiche;
            if(!empty($article['rss_titre_origin']) && $article['id_rss_origin'] != $article['id_rss']) {
                $discussion['shaarliste_cible_titre'] = $article['rss_titre_origin'];
                $discussion['shaarliste_cible_href'] = $article['article_url'];
            }
            $discussion['article_date_humaine'] = date('d/m/Y H:i', $articleDateTime->getTimestamp());


            $description = sprintf('%s %s',
                str_replace('<br>', '<br/>', process_markdown($article['article_description'])),
                $followUrl
            );

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



            // C'est un de mes posts
            if ($userOptionsUtils->isShaarliste()) {
                if ($userOptionsUtils->getIdOkRss() === $article['id_rss']) {
                    $idPostShaarli = sprintf('%s_%s', substr($article['article_date'], 0, 8), substr($article['article_date'], 8, 6));
                    $discussion['edit_link'] = sprintf('%s?source=bookmarklet&edit_link=%s', $userOptionsUtils->getShaarliUrl(), $idPostShaarli);
                } else {
                    $discussion['comment_link'] = sprintf('%s?source=bookmarklet&post=%s', $userOptionsUtils->getShaarliUrl(), $article['article_uuid']);
                }
            }


            $discussion['article_uuid'] = $article['article_uuid'];
            $discussion['description'] = $description;
            //$discussion['description'] = '';
            $discussion['article_url'] = $article['article_url'];
            $discussion['article_date'] = $articleDateTime->format('YmdHis');
            $discussion['id_commun'] = $article['id_commun'];
            $discussion['id'] = $article['id'];
            $discussion['id_rss'] = $article['id_rss'];


            $articleFirstDate = $article['article_date'];
            if(isset($found[$article['id_commun']])) {
                $discussions[$article['id_commun']][md5($article['article_uuid'])] = $discussion;
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
                $discussions[$article['id_commun']] = array(md5($article['article_uuid']) => $discussion);
            }

            $imgMiniCapturePath = $imgCapturePathMax = null;
            //$imgMiniCapturePath = captureUrl($article['article_url'], $article['id_commun'], 200, 200);
            //$imgCapturePathMax = getImgPathFromId($article['id_commun']);
            if(isset($_SESSION['ireadit']['id'][$article['id_commun']])) {
                $readClass = 'read';
            } else {
                $readClass = 'not-read';
            }

            $found[$article['id_commun']] = array('description' => $description,
                'title' =>  $article['article_titre'],
                'link' => $article['article_url'],
                'pubDate' => $articleDateTime->format(\DateTime::RSS),
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


        // Si on ne décide de ne pas afficher les articles
        // qui pointent vers des sites bloqués par l'utilisateur
        $notAllowedUrls = $userOptionsUtils->getNotAllowedUrls();
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
        if ($userOptionsUtils->displayOnlyNewArticles() && $sortBy !== 'rand') {
            foreach ($found as $k => $article) {
                $fromFiltre = date('Ymd000000');
                if (isset($_GET['from'])) {
                    $fromFiltre = new \DateTime($_GET['from']);
                    $fromFiltre = $fromFiltre->format('YmdHis');
                }

                if ($article['first_date'] < $fromFiltre) {
                    unset($found[$k]);
                }
            }
        }

        // Filtre sur les noms de tags
        $notAllowedTags = $userOptionsUtils->getNotAllowedTags();
        if (!empty($notAllowedTags)) {
            foreach ($found as $k => $article) {
                $tagsExploded = explode(',', $article['tags']);
                if (!empty(array_intersect($tagsExploded, $notAllowedTags))) {
                    unset($found[$k]);
                }
            }
        }


        function detectSublink(&$noeud, &$discussionsOrigine)
        {

            $articleUrlSansHttps = str_replace('https://', 'http://', $discussionsOrigine['article_url']);
            if (isset($noeud[md5($articleUrlSansHttps)])) {
                $discussionsOrigine['article_url'] = $articleUrlSansHttps;
            }

            // Le lien d'origine est trouvé \o/
            if (isset($noeud[md5($discussionsOrigine['article_url'])])) {
                if (!isset($noeud[md5($discussionsOrigine['article_url'])]['sublink'])) {
                    $noeud[md5($discussionsOrigine['article_url'])]['sublink'] = array();
                }
                // Reshaare du lien d'origine uniquement
                $noeud[md5($discussionsOrigine['article_url'])]['sublink'][md5($discussionsOrigine['article_uuid'])] = $discussionsOrigine;

                return true;
            }
            // Peut etre sur un de ces sublink ?
            foreach ($noeud as $d => $disc) {
                //  Si présence de commentaire, on teste
                if (!empty($noeud[$d]['sublink'])) {
                    return detectSublink($noeud[$d]['sublink'], $discussionsOrigine);
                }
            }

            return false;
        }


        function sortNoeudSublink(&$noeud)
        {
            //  Si présence de commentaire, on tri sur eux avant
            if (!empty($noeud['sublink'])) {
                foreach ($noeud['sublink'] as $d => $disc) {
                    sortNoeudSublink($noeud['sublink'][$d]);
                }
                usort($noeud['sublink'], "triParDate");
            }
        }


        // A partir d'ici, pour chaque discussion, on réarrangent les liens dedans pour faire des groupes
        $foundGroupes = array();
        foreach ($found as $idCommun => $article) {
            $foundGroupes[$idCommun] = $article;

            // On reset les discussions
            $foundGroupes[$idCommun]['discussions'] = array();
            $foundGroupes[$idCommun]['description'] = '';

            // On commence par regarder si un des uuid est le lien d'origine
            $lienTypeShaarli = false;
            foreach ($article['discussions'] as $idOriginal => $discussion) {
                // Lien d'origine venant d'un shaarli
                if (md5($discussion['article_url']) === $idOriginal) {
                    $foundGroupes[$idCommun]['discussions'][md5($discussion['article_uuid'])] = $discussion;

                    // Pour le flux rss, on construit la chaine ici
                    $foundGroupes[$idCommun]['description'] .= sprintf('<b>%s</b><br/>%s<br/><br/>', $discussion['shaarliste_titre'], $discussion['description']);
                    // On l'enlève du found d'origine
                    unset($found[$idCommun]['discussions'][$idOriginal]);
                    $lienTypeShaarli = true;
                    break;
                }
            }

            // Pour chaque commentaires, on regarde ceux liés entre eux
            if (!$lienTypeShaarli) {
                foreach ($article['discussions'] as $idOriginal => $discussion) {
                    // Reshaare du lien d'origine uniquement
                    if (md5($article['link']) === md5($discussion['article_url'])) {
                        $foundGroupes[$idCommun]['discussions'][md5($discussion['article_uuid'])] = $discussion;

                        // Pour le flux rss, on construit la chaine ici
                        $foundGroupes[$idCommun]['description'] .= sprintf('<b>%s</b><br/>%s<br/>', $discussion['shaarliste_titre'], $discussion['description']);

                        // On l'enlève du found d'origine
                        unset($found[$idCommun]['discussions'][$idOriginal]);
                    }
                }
            }

            if($userOptionsUtils->displayDiscussions()) {
                $profondeurMax = 10;
                $profondeurCourante = 0;
                do {
                    // On traite maintenant les commentaires liés aux reshaares
                    foreach ($found[$idCommun]['discussions'] as $idOriginal => $discussion) {
                        if (true == detectSublink($foundGroupes[$idCommun]['discussions'], $found[$idCommun]['discussions'][$idOriginal])) {
                            unset($found[$idCommun]['discussions'][$idOriginal]);
                        }
                    }
                    $profondeurCourante++;
                } while($profondeurCourante < $profondeurMax && count($found[$idCommun]['discussions']) != 0);
            }
            // Faire pareil pour les commentaires de commentaires de commentaires etc...
        }


        // Maintenant on vire les articles des gens auxquels on n'est pas abonné
        function unsetNoeudSiPasAbonne(&$noeud, $abonnements) {
            //  Si présence de commentaire, on tri sur eux avant
            if (!empty($noeud['sublink'])) {
                foreach ($noeud['sublink'] as $d => $disc) {
                    if (!in_array($disc['id_rss'], $abonnements )) {
                        unset($noeud['sublink'][$d]);
                        continue;
                    }
                    unsetNoeudSiPasAbonne($noeud['sublink'][$d], $abonnements);
                }
            }
        }
        if(!(isset($_GET['do']) && $_GET['do'] === 'rss')) {
            foreach ($foundGroupes as $idCommun => $article) {
                foreach ($article['discussions'] as $d => $disc) {
                    // Si l'utilisateur original de la discussion n'est pas dans
                    // la liste des abonnements, on supprime la discussion
                    if (!in_array($disc['id_rss'], $abonnements )) {
                        unset($foundGroupes[$idCommun]['discussions'][$d]);
                        continue;
                    }
                    // Si l'utilisateur souhaite tout de même voir les commentaires des shaarlistes non suivi, on ne filtre par les sous discussions
                    if (displayShaarlistesNonSuivis()) {
                        continue;
                    }
                    unsetNoeudSiPasAbonne($foundGroupes[$idCommun]['discussions'][$d], $abonnements);
                }
                // Si le nombre de discussions est vide, on supprime l'article
                if (count($foundGroupes[$idCommun]['discussions']) === 0) {
                    unset($foundGroupes[$idCommun]);
                }
            }
        }


        // Tri par date que maintenant car les clefs des tableaux se barrent via le usort
        foreach ($foundGroupes as $idCommun => $article) {
            foreach ($article['discussions'] as $d => $disc) {
                sortNoeudSublink($foundGroupes[$idCommun]['discussions'][$d]);
            }
        }

        $found = $foundGroupes;

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
        if(!isset($_GET['q']) && $userOptionsUtils->displayBestArticle()) {
            if(isset($_GET['from'])) {
                $dateDeLaVeille = new \DateTime($_GET['from']);
                //$dateDeLaVeille->modify('-1 day');
                $dateTimeFrom = new \DateTime($dateDeLaVeille->format('Ymd000000'));
                $dateTimeTo   = new \DateTime($dateDeLaVeille->format('Ymd235959'));
                $isToday = false;
            } else {
                $dateDeLaVeille = new \DateTime();
                if(isset($_GET['veille'])) {
                    $dateDeLaVeille = new \DateTime($_GET['veille']);
                    $isToday = false;
                }

                // Selection de la date du meilleur article
                if ($dateDeLaVeille->format('H') < 10 ) {
                    // Si l'heure actuelle est avant 10h, on récupère l'article de la veille de 21h à minuit
                    $dateDeLaVeille->modify('-1 day');
                    $dateTimeFrom = new \DateTime($dateDeLaVeille->format('Ymd210000'));
                    $dateTimeTo   = new \DateTime($dateDeLaVeille->format('Ymd235959'));
                } elseif ($dateDeLaVeille->format('H') < 13 ) {
                    // Si l'heure actuelle est avant 13h, on récupère l'article du jour de minuit à 10h
                    $dateTimeFrom = new \DateTime($dateDeLaVeille->format('Ymd000000'));
                    $dateTimeTo   = new \DateTime($dateDeLaVeille->format('Ymd095959'));
                } elseif ($dateDeLaVeille->format('H') < 16 ) {
                    // Si l'heure actuelle est avant 16h, on récupère l'article du jour de 10h à 13h
                    $dateTimeFrom = new \DateTime($dateDeLaVeille->format('Ymd100000'));
                    $dateTimeTo   = new \DateTime($dateDeLaVeille->format('Ymd125959'));
                } elseif ($dateDeLaVeille->format('H') < 19 ) {
                    // Si l'heure actuelle est avant 19h, on récupère l'article du jour de 13h à 16h
                    $dateTimeFrom = new \DateTime($dateDeLaVeille->format('Ymd130000'));
                    $dateTimeTo   = new \DateTime($dateDeLaVeille->format('Ymd155959'));
                } elseif ($dateDeLaVeille->format('H') < 21 ) {
                    // Si l'heure actuelle est avant 21h, on récupère l'article du jour de 16h à 19h
                    $dateTimeFrom = new \DateTime($dateDeLaVeille->format('Ymd160000'));
                    $dateTimeTo   = new \DateTime($dateDeLaVeille->format('Ymd185959'));
                } else {
                    // Sinon on récupère l'article du jour de 19h à 21h
                    $dateTimeFrom = new \DateTime($dateDeLaVeille->format('Ymd190000'));
                    $dateTimeTo   = new \DateTime($dateDeLaVeille->format('Ymd205959'));
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
                $avatar = sprintf('<a href="%s"><img class="entete-avatar" width="50" height="50" src="%s"/></a>', $meilleurArticleDuJour['url'], sprintf('%s', $faviconPath));

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
        if ($userOptionsUtils->isExtended() && count($found) > 1) {
            $extended = true;
        }

        if($afficherMessagerie) {
            $titre = $titrePageMessagerie;
        }else{
            if ($fromDateTime->format('Ymd') != $toDateTime->format('Ymd')) {
                $titre = 'Du ' . $fromDateTime->format('d/m/Y') . ' au  ' . $toDateTime->format('d/m/Y') . ' - Tri par :  ' . $message[$sortBy] . ' (' . $message[$sort] . ')';
            } else {
                if(isset($usernameRecherche) && $usernameRecherche != 'shaarlo') {
                    $shaarliste = $mysqlUtils->getShaarliste($mysqli, $usernameRecherche);
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
                    <link>http://'.$SHAARLO_DOMAIN.'/</link>
                    <description>Shaarli Aggregators</description>
                    <language>fr-fr</language>
                    <copyright>http://'.$SHAARLO_DOMAIN.'/</copyright>';
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
            $userOptionsUtils->getSession($sessionId);
        } else {

            $dateDemain = '';
            $dateHier = '';

            if (substr($from, 0, 4) == substr($from, 0, 4)) {
                $dateJMoins1 = new \DateTime($from);
                $dateJMoins1->modify('-1 day');
                $dateHier = $dateJMoins1->format('Ymd');
                $dateJPlus1 = new \DateTime($from);
                $dateJPlus1->modify('+1 day');
                if ($dateJPlus1->format('Ymd') <= date('Ymd')) {
                    $dateDemain = $dateJPlus1->format('Ymd');
                }
            }
            $dateActuelle = new \DateTime();
            $isSecure = 'no';
            if(!empty($_SERVER['HTTPS'])) {
                $isSecure = 'yes';
            }


            $nodesc = null;
            if(isset($_GET['nodesc'])) {
                $nodesc = $_GET['nodesc'];
            }
            $nbSessions = null;

            $urlCourante = $urlUtils->getUrlCourante();
            $urlRss = $urlUtils->ajouterParametreGET($urlCourante, 'do', 'rss');
            $urlRss = $urlUtils->ajouterParametreGET($urlRss, 'u', $userOptionsUtils->getUtilisateurId());
            //echo $urlRss;

            /*
            $logStat = json_decode(file_get_contents('log/stat'));
            $nbSessions = $logStat[0];
            */

            $dateDuJour = new \DateTime();
            $dateTopHier = new \DateTime('-1 day');
            $dateMoinsUneSemaine = new \DateTime('-7 days');
            $dateMoinsUnMois = new \DateTime(date('Ym00'));

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
            , 'token' => $this->getToken()
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
            , 'displayBlocConversation' => $userOptionsUtils->displayBlocConversation()
            , 'tags_json'   => json_encode($tags)
            , 'shaarlieurs_poussines' => null
            , 'nb_poussins_disponibles' => null
            , 'min_limit' => min($MIN_FOUND_ITEM, $limit),
                'user_id' => $userOptionsUtils->getUtilisateurId(),

            );


            return $this->render(
                '@Shaarlo/river.html.twig',
                array_merge($this->getGlobalTemplateParameters(),
                    $params
                )
            );
        }
    }
}