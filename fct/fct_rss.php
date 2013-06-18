<?php


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



/*
 * Get a RSS 
 * 
 * @param $ur
 * @return atom
 *  
 */
function getRss($url){
	return file_get_contents($url);
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

function urlExists($url=NULL)  
{  
    if($url == NULL) return false;  
    $ch = curl_init($url);  
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 50);  
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 50);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
    $data = curl_exec($ch);  
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  
    curl_close($ch);  
    if($httpcode>=200 && $httpcode<300){  
        return true;  
    } else {  
        return false;  
    }  
}

/**
* Return true if the rss is valid, else false 
*/
function is_valid_rss($url){
	if(urlExists($url)){
		return false;
	}
	$content = getRss($url);
	$xmlContent = getSimpleXMLElement($content);
	if($xmlContent !== false){
		define('XPATH_RSS_ITEM', '/rss/channel/item');
		$rssItems = convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);
		$firstItem = reset($rssItems);
		$link = $firstItem['link'];
		$rssTimestamp = strtotime($firstItem['pubDate']);
		if(filter_var($link, FILTER_VALIDATE_URL)  && $rssTimestamp > 0){

			// Return the title
			define('XPATH_RSS_TITLE', '/rss/channel/title');
			$list = $xmlContent->xpath(XPATH_RSS_TITLE);
			return (string)$list[0];
		}
	}
	return false;	
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





