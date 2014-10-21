<?php
require_once('fct/fct_rss.php');
require_once('fct/fct_crypt.php');
require_once('fct/fct_http.php');
require_once('fct/fct_markdown.php');
require_once('fct/fct_mysql.php');
require_once('fct/Favicon/DataAccess.php');
require_once('fct/Favicon/Favicon.php');

/**
 * Retourne le nombre de sessions ouvertes (OVH)
 * @return int
 */
function countNbSessions() {
    $dir_name = ini_get("session.save_path");

    $dir = opendir($dir_name);
    $i = 0;
    $max_time = ini_get("session.gc_maxlifetime");
    while ($file_name = readdir($dir)) {
        if($file_name == '.htaccess'){
            continue;
        }
        $file = $dir_name . "/" . $file_name;
        $lastvisit = @filemtime($file);
        $difference = mktime() - $lastvisit;

        
        if (is_file($file)) {
            if (($difference < $max_time)) {
                $i++;
            }elseif (($difference < (24 * 60 * 60 * 2))){
                @unlink($file);
            }
        }        
    }
    closedir($dir);

    return $i;
}

// Charge la configuration 
// Du shaarliste et le connecte
function loadConfiguration($url) {
    $clefProfil = 'Mon Profil';
    $clefAbonnement = 'Mes abonnements';
    $clefsAutorises = array($clefProfil, $clefAbonnement);
    $pseudo = md5($url);
    
    // Récupération du message dans shaarli
    $tagConfiguration = 'shaarlo_configuration_v1';

    // Ajout des paramètres récupérant le bon message
    $urlConfiguration = sprintf('%s?do=rss&searchtags=%s', $url, $tagConfiguration);

    $rss = getRss($urlConfiguration);

    $xmlContent = getSimpleXMLElement($rss);
    if( ! $xmlContent instanceof SimpleXMLElement){
        return null;
    }

    $rssListArrayed= convertXmlToTableau($xmlContent, XPATH_RSS_ITEM);

    $list = $xmlContent->xpath(XPATH_RSS_TITLE);
    if(isset($list[0])) {
        $pseudo = (string)$list[0];
    }
    
    
    $firstLink = reset($rssListArrayed);

    // Suppression de <br>(<a href="https://stuper.info/shaarli2//?q1R1_w">Permalink</a>)
    $description = $firstLink['description'];
    if(strpos($description, '<br>') !== false) {
        $description = explode('<br>', $firstLink['description']);
        $description = reset($description);
    }

    $configuration = markdownToArray($description, $clefsAutorises);
    
    $username = md5($url);
        
    // Maj sql
    if(!empty($configuration)) {
        foreach($configuration[$clefProfil] as $param) {
            if($param['key'] == 'pseudo') {
                $pseudo = $param['value'];
            }
        }
    }
    $favicon = new \Favicon\Favicon();
    $mysqli = shaarliMyConnect();
    
    // Création en base du shaarliste
    $shaarliste = creerShaarliste($username, $pseudo, $url);
    insertEntite($mysqli, 'shaarliste', $shaarliste);
        
    // Maj sql
    if(!empty($configuration[$clefAbonnement])) {
        deleteMesRss($mysqli, $username);
        $configuration[$clefAbonnement][] = array('key' => $pseudo, 'value' => $url);
        foreach($configuration[$clefAbonnement] as $param) {
            $urlSimplifiee = str_replace('https://', '', $param['value']);
            $urlSimplifiee = str_replace('http://', '', $urlSimplifiee);
            $urlSimplifiee = str_replace('my.shaarli.fr/', 'shaarli.fr/my/', $urlSimplifiee);
            $idRss = md5($urlSimplifiee);
            
            // L'id rss n'existe pas encore
            if (null === ($idRss = idRssExists($mysqli, $urlSimplifiee))) {
                $idRss =  md5($urlSimplifiee);

                // Telechargement de l'icone et sauvegarde
                $pathIco = sprintf('img/favicon/%s.gif', $idRss);
                $pathGif = sprintf('img/favicon/%s.ico', $idRss);
                if(!is_file($pathIco) && !is_file($pathGif)) {
                    $urlFavicon = $favicon->get($param['value']);
                    if (false !== ($favico = @file_get_contents($urlFavicon))) {
                        $tmpIco = sprintf('img/favicon/%s', 'tmp');
                        file_put_contents($tmpIco, $favico);
                        if (exif_imagetype($tmpIco) == IMAGETYPE_GIF 
                        ) {
                            rename($tmpIco,sprintf('img/favicon/%s.gif', $idRss));
                        }
                        elseif (exif_imagetype($tmpIco) == IMAGETYPE_ICO 
                        ) {
                            rename($tmpIco,sprintf('img/favicon/%s.ico', $idRss));
                        }
                    } else {
                        //Fichier foireux
                        $faviconPath = 'img/favicon/63a61a22845f07c89e415e5d98d5a0f5.ico';
                        copy($faviconPath, sprintf('img/favicon/%s.ico', $idRss));
                    }
                        
                }
                
                $rss = creerRss($idRss, 'sans titre', $param['value'], $urlSimplifiee, 1);
                
                insertEntite($mysqli, 'rss', $rss);
            }

            // Création de l'abonnement
            $monRss = creerMonRss($username, $idRss, $pseudo, $param['key']);
            insertEntite($mysqli, 'mes_rss', $monRss);
        }

        shaarliMyDisconnect($mysqli);
    }
    
    if (!empty($pseudo)) {
        $_SESSION['username'] = $username;
        $_SESSION['pseudo'] = $pseudo;
        $_SESSION['myshaarli'] = $url;
    }
}

function simplifieUrl($url) {
    $urlSimplifiee = str_replace('https://', '', $url);
    $urlSimplifiee = str_replace('http://', '', $urlSimplifiee);
    $urlSimplifiee = str_replace('my.shaarli.fr/', 'shaarli.fr/my/', $urlSimplifiee);

    return $urlSimplifiee;
}

function isAdmin() {
    global $ID_ADMIN;
    if(!empty($ID_ADMIN)
    && isset($_SESSION['username']) && $_SESSION['username'] === $ID_ADMIN){
        return true;
    }
    
    return false;
}
