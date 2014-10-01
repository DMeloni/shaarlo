<?php

require_once('config.php');
require_once('fct/fct_rss.php');
require_once('fct/fct_crypt.php');
require_once('fct/fct_http.php');
require_once('fct/fct_markdown.php');
require_once('fct/fct_mysql.php');


$password = get('password');
$shaarli = get('shaarli');

$clefProfil = 'Mon Profil';
$clefAbonnement = 'Mes abonnements';
$clefsAutorises = array($clefProfil, $clefAbonnement);

// Récupération du message dans shaarli
$url = $shaarli;
$tagConfiguration = 'shaarlo_configuration_v1';

// Ajout des paramètres récupérant le bon message
$urlConfiguration = sprintf('%s?do=rss&searchtags=%s', $url, $tagConfiguration);
echo $urlConfiguration;

$rss = getRss($urlConfiguration);


$xmlContent = getSimpleXMLElement($rss);
if( ! $xmlContent instanceof SimpleXMLElement){
    header('HTTP/1.1 404 Not Found', true, 404);
 
    exit(1);
}

$rssListArrayed= convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);

$firstLink = reset($rssListArrayed);



// Suppression de <br>(<a href="https://stuper.info/shaarli2//?q1R1_w">Permalink</a>)
$description = $firstLink['description'];
if(strpos($description, '<br>') !== false) {
    $description = explode('<br>', $firstLink['description']);
    $description = reset($description);
}

$configuration = markdownToArray($description, $clefsAutorises);

var_dump($configuration);
// Maj sql
if(is_array($configuration)) {
    
    $mysqli = shaarliMyConnect();

    $username = md5($url);
    $pseudo = null;
    
    foreach($configuration[$clefProfil] as $param) {
        if($param['key'] == 'pseudo') {
            $pseudo = $param['value'];
        }
    }
    
    $configuration[$clefAbonnement][] = array('key' => $pseudo, 'value' => $url);
    foreach($configuration[$clefAbonnement] as $param) {
        $idRss = md5($param['value']);
        
        $urlSimplifiee = str_replace('https://', '', $param['value']);
        $urlSimplifiee = str_replace('http://', '', $urlSimplifiee);
        
        // L'id rss n'existe pas encore
        if (null === ($idRss = idRssExists($mysqli, $urlSimplifiee))) {
            $idRss =  md5($urlSimplifiee);
            echo 'creation de ' . $idRss;
            $rss = creerRss($idRss, 'sans titre', $param['value'], 1);
        }

        // Création de l'abonnement
        $monRss = creerMonRss($username, $idRss, $pseudo, $param['key']);
        insertEntite($mysqli, 'mes_rss', $monRss);
    }

    shaarliMyDisconnect($mysqli);
}

var_dump($description);

/*
 Cas où la config est cryptée

// 32 byte binary blob
$aes256Key = hash("SHA256", $password, true);
$shaarliConfiguration = fnDecrypt($description, $aes256Key);
$shaarliConfigurationArrayed = json_decode($shaarliConfiguration, true);

if (!is_null($shaarliConfigurationArrayed)) {
    var_dump($shaarliConfigurationArrayed);
}
*/


