<?php

require_once('fct/fct_rss.php');
require_once('fct/fct_crypt.php');

if (isset($_POST['password'])) {
    $password = $_POST['password'];
}
if (isset($_POST['shaarli'])) {
    $shaarli = $_POST['shaarli'];
}


// Récupération du message dans shaarli
$url = $shaarli;
$tagConfiguration = 'shaarli.fr_configuration';

// Ajout des paramètres récupérant le bon message
$urlConfiguration = sprintf('%s?do=rss&searchtags=%s', $url, $tagConfiguration);


$rss = getRss($urlConfiguration );


$xmlContent = getSimpleXMLElement($rss);
if( ! $xmlContent instanceof SimpleXMLElement){
    header('HTTP/1.1 404 Not Found', true, 404);
 
    exit(1);
}

$rssListArrayed= convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);

$firstLink = reset($rssListArrayed);

// 32 byte binary blob
$aes256Key = hash("SHA256", $password, true);


// Suppression de <br>(<a href="https://stuper.info/shaarli2//?q1R1_w">Permalink</a>)
$description = $firstLink['description'];
if(strpos($description, '<br>') !== false) {
    $description = explode('<br>', $firstLink['description']);
    $description = reset($description);
}



var_dump($description);

$shaarliConfiguration = fnDecrypt($description, $aes256Key);
$shaarliConfigurationArrayed = json_decode($shaarliConfiguration, true);

if (!is_null($shaarliConfigurationArrayed)) {
    var_dump($shaarliConfigurationArrayed);
}


