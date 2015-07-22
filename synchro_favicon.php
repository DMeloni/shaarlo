<?php
require_once 'config.php';
require_once 'fct/fct_session.php';
//ini_set('display_errors',1);
//ini_set('display_startup_errors',1);
//error_reporting(-1);

if (!getIdOkRss() || !getShaarliUrl()) {
    header('HTTP/1.1 401 Unauthorized', true, 401);
    return;
}

//https://stackoverflow.com/questions/5701593/how-to-get-a-websites-favicon-with-php
$url = getShaarliUrl();
$doc = new DOMDocument();
$doc->strictErrorChecking = FALSE;
$content = getRss($url);

@$doc->loadHTML($content);
//var_dump($doc);
$xml = @simplexml_import_dom($doc);
$arr = $xml->xpath('//link[@rel="shortcut icon"]');


/**
 * Permet de trier des tableaux qui ont des attributs sizes
 * 
 * sizes : "64x64" est convertie en "64" par un simple (int)
 * et on regarde le plus grand des deux
 */
function usortByIconSize($a, $b)
{
    if ((int)$a['sizes'] == (int)$b['sizes']) {
        return 0;
    }
    
    return ((int)$a['sizes'] < (int)$b['sizes']) ? -1 : 1;
}

// Si shortcut icon non trouvé, on essai via rel icon
if (!isset($arr[0]['href'])) {
    
    //<link rel="icon" type="image/png" sizes="64x64" href="images/favicon_64.png" />
    $arr = $xml->xpath('//link[@rel="icon"]');
    
    // On trouve au moins une image d'icone \o/
    if (is_array($arr)) {
        // On commence à trier en fonction des sizes
        usort($arr, "usortByIconSize");
        
        // Puis on prend le dernier
        $arr = array(end($arr));
    }
}

// Si toujours rien, on sort en 404
if (!isset($arr[0]['href'])) {
    header('HTTP/1.1 404 Not found', true, 404);
    return;
}


$faviconUrl = sprintf('%s/%s', $url, $arr[0]['href']);

$favicon = getRss($faviconUrl);
if ($favicon) {
    $faviconPath = sprintf('img/favicon/%s.ico', getIdOkRss());
    $putContents = file_put_contents($faviconPath, $favicon);
    if (false !== $putContents) {
        header('HTTP/1.1 200 OK', true, 200);
        return;
    }
}

header('HTTP/1.1 500 Internal Server Error', true, 500);
return;
