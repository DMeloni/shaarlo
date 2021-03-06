<?php
if (!isset($_GET['do'])) {
    header('Location: /shaarli/?EYdBKw');
    exit();
}

set_time_limit(0);

require_once('config.php');
require_once('fct/fct_rss.php');
require_once('fct/fct_mysql.php');
require_once('fct/fct_session.php');

global $SHAARLO_DOMAIN, $CACHE_DIRECTORY_PATH, $FLUX_DIRECTORY_PATH;

/**
* Get the directory size
 *
 * @param string $directory
 *
 * @return int
*/
function dirSize($directory)
{
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
        $size+=$file->getSize();
    }
    return $size;
}

// Display shaarli URLs.
if ($_GET['do'] === 'getExternalShaarlistes') {
    $dataDir = 'data';
    $shaarlisDeShaarliFr = json_decode(file_get_contents(sprintf('%s/%s', $dataDir, 'shaarli.txt')), true);

    $listeDeShaarlistes = array();
    foreach($shaarlisDeShaarliFr as $shaarliste) {
        $urlShaarli = str_replace('?do=rss', '', $shaarliste);
        $listeDeShaarlistes[] = $urlShaarli;
    }

    header('Content-Type: application/json');
    echo json_encode($listeDeShaarlistes);
}

// Display shaarli URLs.
if ($_GET['do'] === 'getMyShaarlistes') {
    $myPath = 'my';
    $myDir = scandir($myPath);
    $pattern = 'data-7987213-';
    $listeDeShaarlistes = array();
    foreach ($myDir as $my) {
        if (strpos($my, $pattern) !== 0) {
            continue;
        }
        $dirPath = sprintf('%s/%s', $myPath, $my);
        $dirSize = dirSize($dirPath);

        // Continue if the shaarli content is empty.
        if($dirSize <= 88) {
            continue;
        }

        // Continue if the shaarli content is just the first default message.
        if($dirSize <= 1200 || $dirSize == 1336) {
            continue;
        }


        $shaarliste = substr($my, strlen($pattern));

        $listeDeShaarlistes[] = sprintf('http://%s/my/%s/', $SHAARLO_DOMAIN, $shaarliste);
    }

    echo(utf8_encode(json_encode($listeDeShaarlistes)));
}


// Dipslay all shaarli URLs.
if ($_GET['do'] === 'getAllShaarlistes') {
    $mysqli = shaarliMyConnect();
    $allShaarlistes = getAllRssActifs($mysqli);

    echo json_encode($allShaarlistes);
}

// Save all RSS.
if ($_GET['do'] === 'buildAllRss')
{
    $params = '?do=rss&nb=all';
    $params = '?do=rss';
    $fluxDir = 'flux';
    $dataDir = 'data';
    $uneJourneeEnSeconde = 24 * 4 * 60;
    $mysqli = shaarliMyConnect();

    $allShaarlistes = json_decode(remove_utf8_bom(file_get_contents("https://$SHAARLO_DOMAIN/api.php?do=getAllShaarlistes"), true));
    if(isset($_GET['nbthreads']) && isset($_GET['thread'])) {
        $allShaarlistesChunked = array_chunk($allShaarlistes, ceil(count($allShaarlistes)/(int)$_GET['nbthreads']), true);
        $allShaarlistes = $allShaarlistesChunked[(int)$_GET['thread']-1];
    }

    foreach($allShaarlistes as $shaarliste) {
        $urlSimplifiee = simplifieUrl($shaarliste);
        $fluxName = md5(($urlSimplifiee));
        $fluxFile = sprintf('%s/%s/%s.xml', $dataDir, $fluxDir, $fluxName);
        // Recuperation du delai à attendre entre chaque mise à jour
        $delai = getDelaiBeforeCall($mysqli, $fluxName);
        $delaiEnSeconde = $delai * 60 * 24;

        if(is_file($fluxFile)) {
            $lastvisit = @filemtime($fluxFile);
            $difference = time() - $lastvisit;
            // Dans le cas où le fichier est encore récent, on garde celui des 4 dernieres minutes
            if ($difference < $delaiEnSeconde) {
                echo sprintf("%s - %s : recent %s - delai : %s min<br/>\n", $fluxFile, $shaarliste, $difference, $delai);
                continue;
            }
        }
        if(strpos($shaarliste, '?') !== false) {
            $rss = getRss($shaarliste);
        } else {
            $rss = getRss(sprintf('%s%s', $shaarliste, $params));
        }
        if (!empty($rss)) {
            echo sprintf("%s - %s : nouveau <br/>\n", $fluxFile, $shaarliste);
            file_put_contents($fluxFile, $rss);
        }else{
           echo sprintf("%s - %s : injoignable <br/>\n", $fluxFile, $shaarliste);
           updateRssErreur($mysqli, $fluxName, 'injoignable');
        }
    }
}

/**
 * Get info about a shaarli URL.
 *
 * @param string $url
 * @param array  $externalShaarlistes
 * @param array  $myShaarlistes
 * @param string $cache
 *
 * @return array
 */
function getInfoAboutUrl($url, $externalShaarlistes, $myShaarlistes, $cache = 'true')
{
    $fluxDir = 'flux';
    $dataDir = 'data';

    $fluxName = md5(urldecode($url));
    $fluxFile = sprintf('%s/%s/%s.xml', $dataDir, $fluxDir, $fluxName);

    if (is_file($fluxFile)) {
        $lastvisit = @filemtime($fluxFile);
        $difference = mktime() - $lastvisit;
        $max_time = 60 * 30; // A flux is valid 30 minutes.
        if ($difference < $max_time) {
           $cache = 'true';
        }
    }

    if( !is_file($fluxFile) || $cache== 'false' ) {
        $params = '?do=rss';
        $urlExploded = explode('?', $_GET['url']);
        $rss = getRss(sprintf('%s%s', $urlExploded[0], $params));
        if(!empty($rss)) {
            file_put_contents($fluxFile, $rss);
        }
    }else {
        $rss = file_get_contents($fluxFile);
    }


    $xmlContent = getSimpleXMLElement($rss);
    if( ! $xmlContent instanceof SimpleXMLElement){
        return null;
    }

    $list = $xmlContent->xpath(XPATH_RSS_TITLE);
    $titre = (string)$list[0];

    $list = $xmlContent->xpath(XPATH_RSS_LINK);
    $link = (string)$list[0];

    $list = $xmlContent->xpath(XPATH_RSS_DESCRIPTION);
    $description = (string)$list[0];

    $list = $xmlContent->xpath(XPATH_RSS_LANGUAGE);
    $language = (string)$list[0];

    $list = $xmlContent->xpath(XPATH_RSS_COPYRIGHT);
    $copyright = (string)$list[0];

    $list = $xmlContent->xpath(XPATH_RSS_PUBDATE);
    $pubDate = (string)$list[0];

    $allTags = array();
    $nbItems = 0;
    if ($xmlContent !== false) {
        $categories = $xmlContent->xpath(XPATH_RSS_CATEGORY);
        $items = $xmlContent->xpath(XPATH_RSS_ITEM);
        $nbItems = count($items);
        foreach($categories as $category) {
            $category = (string)$category[0];
            $tags = explode(',', $category);
            foreach($tags as $tag){
                if(empty($tag) || strlen($tag)  < 1) {
                   continue;
                }
                if(!isset($allTags['_' .$tag])){
                    $allTags['_' . $tag] = 0;
                }
                $allTags['_' .$tag]++;
            }
        }
    }

    $isInShaarliFr = false;
    if (in_array($link, $externalShaarlistes)) {
        $isInShaarliFr = true;
    }


    $isMy = false;
    if (in_array($link, $myShaarlistes)) {
        $isMy = true;
    }

    arsort($allTags);

    $tagsLimit = 5;
    if(isset($_GET['tag_limit'])){
        $tagsLimit = $_GET['tag_limit'];
    }

    $allTags = array_slice ($allTags , 0, (int)$tagsLimit);

    if(empty($titre) || empty($link)) {
        return null;
    }

    $nbReshaare = 0;
    $followers = array();
    $allShaarlistes = array_merge($externalShaarlistes, $myShaarlistes);
    $allShaarlistes = array_unique($allShaarlistes);
    foreach($allShaarlistes as $autreShaarli) {
        $fluxNameAutreShaarli = md5($autreShaarli);
        $fluxFileAutreShaarli = sprintf('%s/%s/%s.xml', $dataDir, $fluxDir, $fluxNameAutreShaarli);

        $rss ='';
        if(is_file($fluxFileAutreShaarli)) {
            $fluxAutreShaarli = file_get_contents($fluxFileAutreShaarli);
            if(strpos($fluxAutreShaarli, $url) > 0) {
                $nbReshaare++;
                $followers[] = $autreShaarli;
            }
        }
    }


    $informations = array();

    $informations['title'] = $titre;
    $informations['tags'] = $allTags;
    $informations['link'] = $link;
    $informations['url'] = $url;
    $informations['description'] = $description;
    $informations['language'] = $language;
    $informations['copyright'] = $copyright;
    $informations['shaarlifr'] = $isInShaarliFr;
    $informations['my'] = $isMy;
    $informations['nb_items'] = $nbItems;
    $informations['nb_followers'] = $nbReshaare;
    $informations['followers'] = $followers;
    $informations['pubdate'] = $pubDate;
    $dt = new DateTime($pubDate);
    $informations['pubdateiso'] = $dt->format('Y-m-d');

    return $informations;
}

if ($_GET['do'] === 'getInfoAboutUrl') {
    $externalShaarlistes = json_decode(remove_utf8_bom(file_get_contents("http://$SHAARLO_DOMAIN/api.php?do=getExternalShaarlistes"), true));
    $myShaarlistes = json_decode(remove_utf8_bom(file_get_contents("http://$SHAARLO_DOMAIN/api.php?do=getMyShaarlistes"), true));

    $statDir = 'stats';
    $dataDir = 'data';

    $fluxName = md5(urldecode($_GET['url']));

    $statFile = sprintf('%s/%s/%s.json', $dataDir, $statDir, $fluxName);

    $cache = 'true';
    if(isset($_GET['cache'])) {
        $cache = $_GET['cache'];
    }

    $stat = null;
    if( !is_file($statFile) || $cache == 'false')  {
        $retour = getInfoAboutUrl($_GET['url'], $externalShaarlistes, $myShaarlistes, $cache);
        if(is_array($retour)) {
            $stat = utf8_encode(json_encode($retour));
            file_put_contents($statFile, $stat);
        }
    }else {
        $stat = file_get_contents($statFile);
    }

    echo $stat;
}

// Display all shaarli who talk about a link.
if ($_GET['do'] === 'getDiscussionAboutUrl') {
    if(empty($_GET['url'])) {
        return;
    }

    $expire = time() - 300 - rand(0, 300); // Valid only between 5 and 10 minutes.

    $url = urldecode($_GET['url']);

    $dataDir = 'data';
    $fluxDir = 'flux';
    $cacheFile = sprintf('%s/getDiscussionAboutUrl_%s.json', $CACHE_DIRECTORY_PATH, md5($url));

    if(file_exists($cacheFile) && filemtime($cacheFile) > $expire) {
        readfile($cacheFile);
        exit();
    }

    $tableauDeDiscussions = array();

    $allShaarlistes = json_decode(remove_utf8_bom(file_get_contents("http://$SHAARLO_DOMAIN/api.php?do=getAllShaarlistes"), true));

    $pileUrl = array($url);
    $urlDejaTraite = array();
    $premierPassage = true;

    do {
        $urlEnCours = array_shift($pileUrl);
        $urlEnCoursSansHttp = str_replace('http://', '', $urlEnCours);
        $urlEnCoursSansHttps = str_replace('https://', '', $urlEnCours);
        $urlEnCoursSansHttpNiWWW = str_replace('http://www.', '', $urlEnCours);
        $urlEnCoursSansHttpsNiWWW = str_replace('https://www.', '', $urlEnCours);

        $urlEnCoursSansRien = str_replace('http://www.', '', $urlEnCours);
        $urlEnCoursSansRien = str_replace('https://www.', '', $urlEnCoursSansRien);
        $urlEnCoursSansRien = str_replace('http://', '', $urlEnCoursSansRien);
        $urlEnCoursSansRien = str_replace('https://', '', $urlEnCoursSansRien);


        $urlEnCoursSansHttpAvecWWW = 'www.' . $urlEnCoursSansRien;
        $urlEnCoursAvecHttpAvecWWW = 'http://www.' . $urlEnCoursSansRien;
        $urlEnCoursAvecHttpsSansWWW = 'https://' . $urlEnCoursSansRien;
        $urlEnCoursAvecHttpSansWWW = 'http://' . $urlEnCoursSansRien;
        $urlEnCoursAvecHttpsAvecWWW = 'https://www.' . $urlEnCoursSansRien;


        $urlEnCoursToutFormat = array($urlEnCoursAvecHttpSansWWW, $urlEnCoursAvecHttpsSansWWW, $urlEnCoursSansHttpAvecWWW, $urlEnCoursAvecHttpAvecWWW, $urlEnCoursAvecHttpsAvecWWW, $urlEnCoursSansRien, $urlEnCours, $urlEnCoursSansHttp, $urlEnCoursSansHttp, $urlEnCoursSansHttp, $urlEnCoursSansHttpsNiWWW, $urlEnCoursSansHttpNiWWW);
        $urlEnCoursToutFormat = array_unique($urlEnCoursToutFormat);

        $urlDejaTraite = array_merge($urlDejaTraite, $urlEnCoursToutFormat) ;

        foreach($allShaarlistes as $shaarliste) {
            $fluxName = md5($shaarliste);
            $fluxFile = sprintf('%s/%s.xml', $FLUX_DIRECTORY_PATH, $fluxName);

            // Récupération du flux
            if( !is_file($fluxFile)) {
                continue; // Tant pis pour chercher le flux
            }else {
                $rss = file_get_contents($fluxFile);
            }

            // Recherche du lien dans le flux
            if(strpos($rss, $urlEnCoursSansRien) !== false
            ){

                $xmlContent = getSimpleXMLElement($rss);
                if( ! $xmlContent instanceof SimpleXMLElement){
                    continue;
                }
                $tableauItems = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
                foreach($tableauItems as $rssItem) {
                    $link = $rssItem['link'];
                    $guid = $rssItem['guid'];

                    if(in_array($link, $urlEnCoursToutFormat) ||
                       in_array($guid, $urlEnCoursToutFormat)
                    ) {
                        // On stocke le guid du message posté dans le cas
                        // où quelqu'un répond directement
                        if(!in_array($guid, $urlDejaTraite)) {
                            $pileUrl[] = $guid;
                        }

                        //$link = str_replace('http://', '', $link);
                        //$link = str_replace('https://', '', $link);

                        // On stocke le lien uniquement si c'est un lien shaarli
                        // qui finit par "?HCdy1w"
                        if (!in_array($link, $urlDejaTraite)
                         && (preg_match('#\?[_a-zA-Z0-9]{6}$#', $link) || $premierPassage)
                         ) {
                            $pileUrl[] = $link;
                        }
                        // Si c'est le lien d'origine, on l'indique
                        if ($link == $url) {
                            $rssItem['origin'] = true;
                        }

                        $date = new DateTime($rssItem['pubDate']);
                        $rssItem['pubdateiso'] = $date->format('Y-m-d H:i:s');
                        $list = $xmlContent->xpath(XPATH_RSS_TITLE);
                        $titre = (string)$list[0];
                        $rssItem['rss_title'] = $titre;
                        $tableauDeDiscussions['_' . md5($guid)] = $rssItem;
                    }
                }
            }
        }
         $premierPassage = false;
    } while(count($pileUrl) > 0) ;

    $tableauDeDiscussionsEncoded = json_encode($tableauDeDiscussions);
    file_put_contents($cacheFile, $tableauDeDiscussionsEncoded);

    echo $tableauDeDiscussionsEncoded;
}

// Display all messages about a shaarli URL.
if ($_GET['do'] === 'getMessagerieAboutUrl') {
    if(empty($_GET['url']) || empty($_GET['limit'])) {
        return;
    }

    $dataDir = 'data';
    $fluxDir = 'flux';

    $fluxName = md5(urldecode($_GET['url']));
    $fluxFile = sprintf('%s/%s/%s.xml', $dataDir, $fluxDir, $fluxName);

    $rss ='';
    if( !is_file($fluxFile) || true) {
        $params = '?do=rss';
        $rss = getRss(sprintf('%s%s', urldecode($_GET['url']), $params));
        if(!empty($rss)) {
            file_put_contents($fluxFile, $rss);
        }
    }else {
        $rss = file_get_contents($fluxFile);
    }

    $xmlContent = getSimpleXMLElement($rss);
    if( ! $xmlContent instanceof SimpleXMLElement){
        exit();
    }
    $tableauItems = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
    $itemTraites = 0;
    $messagerie = array();
    foreach($tableauItems as $rssItem) {
        if($itemTraites > (int)$_GET['limit']) {
            break;
        }

        // Si lien déjà traité
        if(isset($messagerie['_' . md5($rssItem['link'])])){
            continue;
        }

        $discussion = json_decode(remove_utf8_bom(file_get_contents(sprintf("http://$SHAARLO_DOMAIN/api.php?do=getDiscussionAboutUrl&url=%s", urlencode($rssItem['link'])))), true);
        if(!is_array($discussion) || count($discussion) <= 1) {
            continue;
        }

        $messagerie['_' . md5($rssItem['link'])] = $discussion;

        $itemTraites++;
    }

    echo json_encode($messagerie);
}

if ($_GET['do'] === 'getInfoAboutAll') {
    $dataDir = 'data';
    $fluxName = 'stats.json';

    $statFile = sprintf('%s/%s.json', $dataDir, $fluxName);

    if ( !is_file($statFile) || true || rand(0,100) == 100) {
        $mysqli = shaarliMyConnect();
        $infos = selectAllShaarlistes($mysqli);
        $topRss = getTopRss($mysqli);

        // Croisement entre les shaarlistes et leur nb de followers
        foreach ($infos as $idRss => $info) {
            if (isset($topRss[$idRss])) {
                $infos[$idRss]['nb_followers'] = $topRss[$idRss];
            } else {
                $infos[$idRss]['nb_followers'] = 0;
            }
        }

        if(is_array($infos)) {
            $stat = utf8_encode(json_encode($infos));
            file_put_contents($statFile, $stat);
        }
    }else {
        $infos = json_decode(file_get_contents($statFile));
    }


    echo json_encode(array('stat' => $infos, 'pubdate' => date('Y-m-d H:i:s', filemtime ( $statFile )) ));
}
