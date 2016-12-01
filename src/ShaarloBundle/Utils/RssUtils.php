<?php

namespace ShaarloBundle\Utils;

/**
 * Préfixe du XHTML dans les requêtes XPATH
 */
define('XPATH_PREFIX_XHTML', 'x');
/**
 * Namespace XHTML
 */
define('XPATH_NAMESPACE_XHTML', 'http://www.w3.org/1999/xhtml');
/**
 * Préfixe de Atom dans les requêtes XPATH
 */
define('XPATH_PREFIX_ATOM', 'a');
/**
 * Namespace Atom
 */
define('XPATH_NAMESPACE_ATOM', 'http://www.w3.org/2005/Atom');
/**
 * Préfixe de OpenSearch pour les requêtes XPATH
 */
define('XPATH_PREFIX_OPEN_SEARCH', 'openSearch');
/**
 * Namespace OpenSearch
 */
define('XPATH_NAMESPACE_OPEN_SEARCH', 'http://a9.com/-/spec/opensearchrss/1.0/');

/**
 * Namespace XSL
 */
define('XPATH_NAMESPACE_XSL', 'http://www.w3.org/1999/XSL/Transform');


/**
 * Préfixe de Purl pour les requêtes XPATH
 */
define('XPATH_PREFIX_PURL_CONTENT', 'content');
/**
 * Namespace Purl
 */
define('XPATH_NAMESPACE_PURL_CONTENT', 'http://purl.org/rss/1.0/modules/content/');

define('XPATH_RSS_ITEM', '/rss/channel/item');

define('XPATH_RSS_TITLE', '/rss/channel/title');

define('XPATH_RSS_LINK', '/rss/channel/link');

define('XPATH_RSS_LANGUAGE', '/rss/channel/language');

define('XPATH_RSS_DESCRIPTION', '/rss/channel/description');

define('XPATH_RSS_COPYRIGHT', '/rss/channel/copyright');

define('XPATH_RSS_PUBDATE', '/rss/channel/item/pubDate');

define('XPATH_RSS_CATEGORY', '/rss/channel/item/category');

class RssUtils
{
    /*
     * Get a RSS by a proxy
     *
     * @param $url
     *
     * @return string
     */
    function getRssByPont($url){
        $urlEncoded = urlencode($url);
        // On rajoute un v aléatoire pour éviter le cache coté proxy
        $result = file_get_contents(sprintf('http://ns3010509.ip-46-105-120.eu/pont.php?url=%s%%26v%%3D%s', $urlEncoded, rand(0,1000)));

        return $result;
    }


    /*
     * Get a RSS
     *
     * @param string $url
     *
     * @return string
     *
     */
    function getRssByCurl($url, $sslVersion=null){
        $ch = curl_init();

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_AUTOREFERER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3',
                'Accept-Encoding: gzip, deflate',
                'DNT: 1',
                'Connection: keep-alive',
            ),
        );

        if($sslVersion != null) {
            $options[CURLOPT_SSLVERSION] = $sslVersion;
        }

        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);
        curl_close($ch);

        if($result== false && $sslVersion == null){
            return getRss($url, 1);
        }
        if($result== false && $sslVersion == 1){
            return getRss($url, 2);
        }
        if($result== false && $sslVersion == 2){
            return getRss($url, 3);
        }

        $result = remove_utf8_bom($result);

        return $result;
    }

    /*
     * Get a RSS
     *
     * @param $ur
     * @return atom
     *
     */
    function getRss($url, $sslVersion=null){
        $ch = curl_init();

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_AUTOREFERER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_HTTPHEADER => array(
                'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3',
                'Accept-Encoding: gzip, deflate',
                'DNT: 1',
                'Connection: keep-alive',
            ),
        );

        if($sslVersion != null) {
            $options[CURLOPT_SSLVERSION] = $sslVersion;
        }

        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);
        curl_close($ch);

        if($result== false && $sslVersion == null){
            return getRss($url, 1);
        }
        if($result== false && $sslVersion == 1){
            return getRss($url, 2);
        }
        if($result== false && $sslVersion == 2){
            return getRss($url, 3);
        }

        $result = remove_utf8_bom($result);

        return $result;
    }

    /*
     * Conversion de xml à tableau associatif de php
    * @param $xml   : XML
    * @param $xpath : xpath de element à récupérer
    * return : tableau associatif
    */
    function convertXmlToTableau($xml,$xpath){
        $list = $xml->xpath($xpath);
        $tableau = array();
        foreach ($list as $elt){
            $classArray = array();
            foreach ($elt as $key => $el){
                $value = (string)$el;
                if(empty($classArray[$key])){
                    $classArray[$key] = $value;
                }else{
                    $classArray[$key] .= ',' . $value;
                }
            }
            $tableau[] = $classArray ;
        }
        return $tableau;
    }

    /*
     * Conversion de xml à tableau associatif de php
    * @param $xml   : XML
    * @param $xpath : xpath de element à récupérer
    * return : tableau associatif
    */
    function convertXmlToTableauAndStop($xml,$xpath){
        $list = $xml->xpath($xpath);
        $tableau = array();

        foreach ($list as $elt){
            $classArray = array();
            foreach ($elt as $key => $el){
                $value = (string)$el;
                if(empty($classArray[$key])){
                    $classArray[$key] = $value;
                }else{
                    $classArray[$key] .= ',' . $value;
                }
            }
            $tableau[] = $classArray ;

            // On sort si jamais l'item en cours n'est plus à la date du jour
            if (isset($classArray['pubDate'])) {
                if (date('Ymd') != date('Ymd', strtotime($classArray['pubDate']))) {
                    break;
                }
            }

        }

        return $tableau;
    }

    function urlExists($url, $sslVersion=null) {
        if (function_exists('curl_init')){
            $ch = curl_init();
            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_AUTOREFERER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_ENCODING => 'gzip',
                //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
                //CURLOPT_SSL_CIPHER_LIST => 'RC4-SHA',
                CURLOPT_HTTPHEADER => array(
                    'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3',
                    'Accept-Encoding: gzip, deflate',
                    'DNT: 1',
                    'Connection: keep-alive',
                ),
            );

            if($sslVersion != null) {
                $options[CURLOPT_SSLVERSION] = $sslVersion;
            }

            curl_setopt_array($ch, $options);

            //print_r(curl_errno($ch));

            $data = curl_exec($ch);
            //print_r(curl_getinfo($ch));
            //print_r(curl_error($ch));
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
            //var_export($httpcode);
            if($httpcode>=200 && $httpcode<300){
                return true;
            }else{
                if($sslVersion == null){
                    return urlExists($url, 1);
                }
                if($sslVersion == 1){
                    return urlExists($url, 2);
                }
                if($sslVersion == 2){
                    return urlExists($url, 3);
                }
            }
        }
        if (function_exists ( 'get_headers')){
            $file_headers = get_headers($url);
            if(!isset($file_headers[0]) || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
                return false;
            }
            else {
                return true;
            }
        }
        return false;
    }

    /**
     * Return true if the rss is valid, else false
     */
    function is_valid_rss($url){
        if(!urlExists($url)){
            return false;
        }
        $content = getRss($url);

        $content = remove_utf8_bom($content);

        $xmlContent = getSimpleXMLElement($content);

        if($xmlContent !== false){
            $rssItems = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
            $firstItem = reset($rssItems);
            $link = $firstItem['link'];
            if(!isset($firstItem['pubDate'])){
                return false;
            }
            $rssTimestamp = strtotime($firstItem['pubDate']);
            if($rssTimestamp > 0){
                // Return the title
                $list = $xmlContent->xpath(XPATH_RSS_TITLE);
                return (string)$list[0];
            }
        }
        return false;
    }

    /**
     * Supprime le BOM du fichier
     *
     * @param string $text : le contenu du fichier
     *
     * @return string : le meme contenu sans BOM
     */
    function remove_utf8_bom($text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }

    /**
     * Fonction de création d'un objet SimpleXMLElement avec enregistrement des
     * espaces de nom à partir d'une chaine de caractères au format XML.
     *
     * @param string $xmlEntree le flux XML permettant de créer le SimpleXMLElement
     * @param string $namespaceParDefaut le namespace par défaut du flux XML (optionnel)
     * @param string $depuisFichier si vrai, alors $xmlEntree est le <strong>chemin ou d'accès</strong> au contenu à tranformer en SXE.
     * @return SimpleXMLElement L'objet SimpleXMLElement dont le contenu est $xmlEntree ou FALSE en cas d'erreur
     */
    function getSimpleXMLElement($xmlEntree, $namespaceParDefaut=false, $depuisFichier=false) {
        $boolDepuisFichier = chaineEnBooleen($depuisFichier);
        // Création de l'objet SimpleXMLElement
        try {
            if($namespaceParDefaut) {
                // un namespace par défaut a été fourni
                $xmlRetour = @(new SimpleXMLElement($xmlEntree, null, $boolDepuisFichier, $namespaceParDefaut, false));
            } else {
                // pas de namespace par défaut
                $xmlRetour = @(new SimpleXMLElement($xmlEntree, null, $boolDepuisFichier));
            }
        } catch (Exception $e) {
            return false;
        }
        // Enregistrement des espaces de noms
        registerDefaultXPathNamespaces($xmlRetour);
        return $xmlRetour;
    }

    /**
     * Fonction de transformation d'une chaine en booléen
     *
     * Pour PHP, le cast en booléen de la chaine "false" retourne TRUE,
     * ce qui n'est pas le comportement dont nous avons besoin.
     * Cette fonction retourne un booléen
     * - FALSE si le paramètre casté en booléen retourne faux, ou s'il s'agit de la
     *  chaine "false" ou "faux" (insensible à la casse).
     * - TRUE sinon
     * @param string $chaineTest la chaine à transformer
     * @return bool
     */
    function chaineEnBooleen($chaineTest) {
        if( !(bool)$chaineTest
            || !strncasecmp($chaineTest, 'false', 5)
            || !strncasecmp($chaineTest, 'faux', 4) ) {
            // le paramètre est casté en FALSE ou est une chaine "fausse"
            return false;
        } else {
            return true;
        }
    }


    /**
     * Enregistre la correspondance entre un prefixe et un namespace pour les requêtes XPATH
     * Agit sur les prefixe a (Atom), x (XHTML) et openSearch
     * @param SimpleXMLElement $xml
     */
    function registerDefaultXPathNamespaces(SimpleXMLElement $xml) {
        $xml->registerXPathNamespace(XPATH_PREFIX_ATOM, XPATH_NAMESPACE_ATOM);
        $xml->registerXPathNamespace(XPATH_PREFIX_XHTML, XPATH_NAMESPACE_XHTML);
        $xml->registerXPathNamespace(XPATH_PREFIX_OPEN_SEARCH, XPATH_NAMESPACE_OPEN_SEARCH);
        $xml->registerXPathNamespace(XPATH_PREFIX_PURL_CONTENT, XPATH_NAMESPACE_PURL_CONTENT);
    }


    function sanitize_output($buffer) {
        $search = array(
            '/\>[^\S ]+/s', //strip whitespaces after tags, except space
            '/[^\S ]+\</s', //strip whitespaces before tags, except space
            '/(\s)+/s'  // shorten multiple whitespace sequences
        );
        $replace = array(
            '>',
            '<',
            '\\1'
        );
        $buffer = preg_replace($search, $replace, $buffer);

        return $buffer;
    }



    /**
     * Get json representation of opml xml
     */
    function getAllGReaderFlux($pathFlux){
        $document = new DomDocument();
        $document->load($pathFlux);

        $xpath = new DomXPath($document);

        $allFlux = array();
        foreach($xpath->query("//opml/body/outline") as $row){
            $labelTmp = $xpath->query("@text",$row)->item(0)->nodeValue;
            $urlTmp = $xpath->query("@xmlUrl",$row)->item(0)->nodeValue;
            $allFlux[$labelTmp] = $urlTmp;
        }
        return $allFlux;
    }


    function synchroShaarli($url, $full=false, $avecMiniature=true, $nb = null) {
        $mysqli = shaarliMyConnect();
        if (is_null($nb)) {
            if (!$full) {
                $content = getRss($url . '?do=rss');
            } else {
                $content = getRss($url . '?do=rss&nb=all');
            }
        } else {
            $content = getRss($url . '?do=rss&nb=' . $nb);
        }

        $adebut = microtime(true);
        $shaarlistes = array();
        $articles = array();
        $dataDir = 'data';
        $fluxDir = 'flux';

        $urlRssSimplifiee = simplifieUrl($url);
        $fluxName = md5(($urlRssSimplifiee));
        $fluxFile = sprintf('%s/%s/%s.xml', $dataDir, $fluxDir, $fluxName);
        file_put_contents($fluxFile, $content);

        //Fri, 13 Mar 2015 16:09:22 +0400
        //if (strpos($content, date('D, d M Y')) === false && !isset($_GET['full'])) {
        //    echo 'zut';
        //    return;
        //}

        $xmlContent = getSimpleXMLElement($content);
        if($xmlContent === false){
            return false;
        }

        // Maj du titre du shaarli
        $list = $xmlContent->xpath(XPATH_RSS_TITLE);
        if(isset($list[0])) {
            $titre = (string)$list[0];
            updateRssTitre($mysqli, $fluxName, $titre);
        }



        //if (!isset($_GET['today_only'])) {
        //    $rssListArrayed = convertXmlToTableauAndStop($xmlContent, XPATH_RSS_ITEM);
        //} else {
        $rssListArrayed = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
        //}

        $maxArticlesParInsert = 100;
        foreach($rssListArrayed as $rssItem) {

            $link = $rssItem['link'];
            $rssTimestamp = strtotime($rssItem['pubDate']);
            $articleDateJour = date('Ymd', $rssTimestamp);
            //if($articleDateJour !== date('Ymd')
            //&& $articleDateJour !== '20141106' && !isset($_GET['full'])
            //) {
            //    break;
            //}
            $guid = $rssItem['guid'];
            if (preg_match('#^https://deleurme.net/#', $link)) {
                $link = str_replace('https://deleurme.net', 'http://deleurme.net', $link);
            }
            if (preg_match('#^https://www.mypersonnaldata.eu/#', $link)) {
                $link = str_replace('https://www.mypersonnaldata.eu/', 'http://www.mypersonnaldata.eu/', $link);
            }
            if (preg_match('#^http://deleurme.net/liens/index.php5/?\?[_a-zA-Z0-9\-]{6}$#', $link)) {
                $link = str_replace('index.php5/', '', $link);
                $link = str_replace('index.php5', '', $link);
            }
            if (preg_match('#^http://lehollandaisvolant.net/\?mode=links&id=[0-9]{14}$#', $guid)) {
                $guid = str_replace('mode=links&', '', $guid);
            }
            if (preg_match('#^http://lehollandaisvolant.net/\?mode=links&id=[0-9]{14}$#', $link)) {
                $link = str_replace('mode=links&', '', $link);
            }

            $guid = $rssItem['guid'];
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

            //http://lehollandaisvolant.net/?id=20150408205630

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
            if ($avecMiniature) {
                captureUrl($link, $idCommun, 200, 200, true);
                captureUrl($link, $idCommun, 256, 256, true);
                captureUrl($link, $idCommun, 450, 450, true);
            }
            $articleDate = date('YmdHis', $rssTimestamp);
            $articles[] = creerArticle($id, $idCommun, $link, $urlSimplifie, $title, $description, false, $articleDate, $guid, $fluxName, $idRssOrigin, $category);

            if (count($articles) > $maxArticlesParInsert) {
                insertArticles($mysqli, $articles);
                $articles = array();
            }
        }
        insertArticles($mysqli, $articles);

        shaarliMyDisconnect($mysqli);

        return true;
    }
}