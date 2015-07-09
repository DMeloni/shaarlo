<?php
require_once('fct/fct_rss.php');
require_once('fct/fct_crypt.php');
require_once('fct/fct_http.php');
require_once('fct/fct_markdown.php');
require_once('fct/fct_mysql.php');
require_once('fct/Favicon/DataAccess.php');
require_once('fct/Favicon/Favicon.php');
require_once('fct/PasswordHashing/password_hashing_PBKDF2.php');

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
    $urlShaarlisteSimplifiee = simplifieUrl($url);
    $pseudo = md5($urlShaarlisteSimplifiee);
    
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
    
    $username = md5($urlShaarlisteSimplifiee);
        
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
        $configuration[$clefAbonnement][] = array('key' => $pseudo, 'value' => $urlShaarlisteSimplifiee);
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
        $_SESSION = array();
        $_SESSION['username'] = $username;
        $_SESSION['pseudo'] = $pseudo;
        $_SESSION['myshaarli'] = $url;
    }
}

function getIdRssFromUrl($url) {
    return md5(simplifieUrl($url));
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

function getShaarlieur($shaarlieurId) {
    $mysqli = shaarliMyConnect();
    $shaarlieur = selectShaarlieur($mysqli, $_SESSION['shaarlieur_id']);
    shaarliMyDisConnect($mysqli);
    
    return $shaarlieur;
}

function getSession($sessionId = null, $connexion = false, $password = '') {
    global $SESSION_CHARGEE;
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!empty($SESSION_CHARGEE) && is_null($sessionId)) {
        return $_SESSION;
    }

    if ($sessionId == null && !empty($_COOKIE['shaarlieur']) && is_null($_SESSION['shaarlieur_id'])) {
        $sessionId = $_COOKIE['shaarlieur'];
        $connexion = true;
    }

    if (!isset($_SESSION['shaarlieur_id']) && is_null($sessionId)) {
        $consonnes = array('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l',
            'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'x', 'z');
        $voyelles = array('a', 'e', 'i', 'o', 'u', 'y');
        
        $_SESSION['shaarlieur_id'] = $consonnes[array_rand($consonnes)]
        . $voyelles[array_rand($voyelles)]
        . $consonnes[array_rand($consonnes)]
        . $voyelles[array_rand($voyelles)]
        . $consonnes[array_rand($consonnes)]
        . $voyelles[array_rand($voyelles)];
        $sessionId = $_SESSION['shaarlieur_id'];
        //$_SESSION['shaarlieur_id'] = '';
    }

    if (is_null($sessionId) && !is_null($_SESSION['shaarlieur_id'])) {
        $sessionId = $_SESSION['shaarlieur_id'];
    }

    // récupération du compte en bdd
    $mysqli = shaarliMyConnect();
    $shaarlieurSqlData = selectShaarlieur($mysqli, $sessionId);

    // Création du shaarlieur en bdd
    if (is_null($shaarlieurSqlData)) {
        $data = array('abonnements' => array(), 'display_shaarlistes_non_suivis' => true);
        $passwordHash = '';
        if (!empty($password)) {
            $passwordHash = createShaarlieurHash($shaarlieurId, $password);
        }
        $shaarlieurEntite = creerShaarlieur($sessionId, $passwordHash, json_encode($data));
        insertEntite($mysqli, 'shaarlieur', $shaarlieurEntite);

        $shaarlieurSqlData = selectShaarlieur($mysqli, $sessionId);

    } else {
        // Si le compte existe et qu'il y a un password
        if (!empty($shaarlieurSqlData['pwd'])) {
            // S'il s'agit d'une connexion, on regarde l"entrée utilisateur
            if ($connexion) {
                // On regarde dans le cookie
                if (!empty($_COOKIE["shaarlieur_hash"])) {
                    if (true !== validate_password($shaarlieurId . $shaarlieurSqlData['pwd'], $_COOKIE["shaarlieur_hash"])) {
                        return 401;
                    }
                } else {
                    // Sinon dans le password donné
                    if (true !== validate_password($shaarlieurId . $password, $shaarlieurSqlData["pwd"])) {
                        return 401;
                    }
                    // Si la connexion avec la bdd a marché, on met un cookie
                    setcookie('shaarlieur_hash', createShaarlieurHash($shaarlieurId, $shaarlieurSqlData["pwd"]), time()+31536000, '.shaarli.fr');
                }
            }
        } else {
            // Si le compte existe mais qu'aucun password n'est encore enregistré et qu'un password est entré
            if (!empty($password)) {
                $passwordHash = createShaarlieurHash($shaarlieurId, $password);
                majPasswordHash($passwordHash);
            }
        }
    }

    if (!is_null($sessionId)) {
        $_SESSION['shaarlieur_id'] = $sessionId;
    }

    $SESSION_CHARGEE = 'oui';
    
    if (!empty($shaarlieurSqlData['pwd'])) {
        $_SESSION['shaarlieur_pwd'] = true;
    } else {
        $_SESSION['shaarlieur_pwd'] = false;
    }
    $_SESSION['shaarlieur_shaarli_ok'] = $shaarlieurSqlData['shaarli_ok'];
    $_SESSION['shaarlieur_nb_connexion'] = $shaarlieurSqlData['nb_connexion'];
    $_SESSION['shaarlieur_inscription_auto'] =  $shaarlieurSqlData['inscription_auto'];
    $_SESSION['shaarlieur_date_insert'] = $shaarlieurSqlData['date_insert'];
    $_SESSION['shaarlieur_data']  = json_decode($shaarlieurSqlData['data'], true);
    $_SESSION['shaarlieur_data']['abonnements'] = getAllAbonnementsId($mysqli, $_SESSION['shaarlieur_id']);
    $_SESSION['shaarlieur_shaarli_url'] = $shaarlieurSqlData['shaarli_url'];
    $_SESSION['shaarlieur_shaarli_private'] = $shaarlieurSqlData['shaarli_private'];
    $_SESSION['shaarlieur_id_rss'] = $shaarlieurSqlData['id_rss'];
    $_SESSION['shaarlieur_shaarli_url_id_ok'] = $shaarlieurSqlData['shaarli_url_id_ok'];



    if (!is_null($sessionId) && $connexion) {
        majDerniereConnexion($mysqli, $sessionId);
    }

    return $_SESSION;
}

/**
 * Enregistre un password pour le compte demandé
 * 
 * @string $sessionId : le pseudo de l'utilisateur
 * @string $password  : le mot de passe
 * 
 * @return bool true|false
 */
function updatePassword($sessionId, $password) {
    // récupération du compte en bdd
    $mysqli = shaarliMyConnect();
    $shaarlieurSqlData = selectShaarlieur($mysqli, $sessionId);

    // Création du shaarlieur en bdd
    if (!is_null($shaarlieurSqlData)) {
        // Si le compte existe qu'un password est entré
        if (!empty($password)) {
            $passwordHash = createShaarlieurHash($shaarlieurId, $password);
            majPasswordHash($passwordHash);
            $_SESSION['shaarlieur_pwd'] = true;
            return true;
        }
    }

    return false;
}

function setSession($session) {
    $_SESSION = $session;
    $mysqli = shaarliMyConnect();
    updateShaarlieurData($mysqli, $session['shaarlieur_id'], json_encode($session['shaarlieur_data']));
    shaarliMyDisConnect($mysqli);
}

// Met à jour la liste d'abonnement d'un shaarlieur
function majAbonnements($abonnements) {
    $session = getSession();
    $session['shaarlieur_data']['abonnements'] = $abonnements;
    setSession($session);
    $mysqli = shaarliMyConnect();
    updateShaarlieurData($mysqli, $session['shaarlieur_id'], json_encode($session['shaarlieur_data']));

    // Suppression des anciens abonnements 
    deleteMesRss($mysqli, $session['shaarlieur_id']);

    // Création de l'abonnement
    foreach ($abonnements as $shaarlisteId) {
        $monRss = creerMonRss($session['shaarlieur_id'], $shaarlisteId, '', '');
        insertEntite($mysqli, 'mes_rss', $monRss);
    }

    shaarliMyDisConnect($mysqli);
}

// Met à jour la liste d'abonnement d'un shaarlieur
function majInscriptionAuto($inscriptionAuto) {
    $session = getSession();
    $session['shaarlieur_inscription_auto'] = $inscriptionAuto;
    $mysqli = shaarliMyConnect();
    updateShaarlieurInscriptionAuto($mysqli, $session['shaarlieur_id'], $inscriptionAuto);
    setSession($session);
    shaarliMyDisConnect($mysqli);
}


// Met à jour le hash du pwd
function majPasswordHash($pwdHash) {
    $session = getSession();
    $mysqli = shaarliMyConnect();
    updateShaarlieurPassword($mysqli, $session['shaarlieur_id'], $pwdHash);
    setSession($session);
    shaarliMyDisConnect($mysqli);
}

// Met à jour l'url de son shaarli
function majShaarliUrl($shaarliUrl, $isShaarliPrivate) {
    $session = getSession();
    $session['shaarlieur_shaarli_url'] = $shaarliUrl;
    $session['shaarlieur_shaarli_private'] = $isShaarliPrivate;
    $mysqli = shaarliMyConnect();
    updateShaarlieurShaarliUrl($mysqli, $session['shaarlieur_id'], $shaarliUrl, $isShaarliPrivate);
    setSession($session);
    shaarliMyDisConnect($mysqli);
}

/**
 * Retourne le nombre de liens de l'utilisateur
 * 
 * @return int c : le nombre de lien
 */
function getNombreDeClics() {
    $session = getSession();
    $mysqli = shaarliMyConnect();
    $nbClics =  getNombreDeClicsFromShaarlieurId($mysqli, $session['shaarlieur_id']);
    shaarliMyDisConnect($mysqli);
    
    return $nbClics;
}

/**
 * Retourne le nombre de liens de l'utilisateur
 * 
 * @return int : la position du shaarlieur
 */
function getShaarlieurPositionTop() {
    $session = getSession();
    $mysqli = shaarliMyConnect();
    $topShaarlieur =  getTopShaarlieurFromShaarlieurId($mysqli, $session['shaarlieur_id']);
    shaarliMyDisConnect($mysqli);
    
    if (!empty($topShaarlieur['row_number'])) {
        return $topShaarlieur['row_number'] - 1;
    }
    
    return null;
}



// Retourne l'url du shaarli de l'utilisateur
function getShaarliUrl() {
    $session = getSession();
    return $session['shaarlieur_shaarli_url'];
}

// Indique si le shaarli de l'utilisateur est privé ou pas
function isShaarliPrivate() {
    $session = getSession();
    return $session['shaarlieur_shaarli_private'];
}

// Indique si le shaarlieur est shaarliste
function isShaarliste() {
    $session = getSession();
    return '1' == $session['shaarlieur_shaarli_ok'];
}

// Retourne la liste des abonnements de l'utilisateur
function getAbonnements($sessionId=null) {
    $session = getSession($sessionId);
    return $session['shaarlieur_data']['abonnements'];
}

// Retourne l'id du flux rss du site
function getIdRss() {
    $session = getSession();
    return $session['shaarlieur_id_rss'];
}

// Retourne l'id du flux rss du site
function getIdOkRss() {
    $session = getSession();
    return $session['shaarlieur_shaarli_url_id_ok'];
}

// Un shaarlieur sérieux s'est connecté plus de x fois et a un compte ancien
function isSerieux() {
    $session = getSession();
    
    if ('' === getUtilisateurId()) {
        return false;
    }

    if (isset($session['shaarlieur_nb_connexion']) && $session['shaarlieur_nb_connexion'] >= 4) {
        $dateDuJour = new DateTime();
        $dateDuJour->modify('-4 days');
        $dateLastWeek = $dateDuJour->format('YmdHis');
        if (isset($session['shaarlieur_date_insert']) && $session['shaarlieur_date_insert'] < $dateLastWeek) {
            return true;
        }
    }

    return false;
}

// Indique si l'utilisateur est abonné au flux
function estAbonne($idRss) {
    $abonnements = getAbonnements();
    return in_array($idRss, $abonnements);
}

//Indique si l'utilisateur est connecté
function isConnected() {
    $session = getSession();
    return isset($session['shaarlieur_id']);
}

//Indique si l'utilisateur a un pwd
function isPassword() {
    $session = getSession();
    if (isset($session['shaarlieur_pwd']) && $session['shaarlieur_pwd'] === true) {
        return true;
    }
    
    return false;
}

//Retourne l'id de l'utilisateur
function getUtilisateurId() {
    $session = getSession();
    return $session['shaarlieur_id'];
}

function displayShaarlistesNonSuivis() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['display_shaarlistes_non_suivis']) && $session['shaarlieur_data']['display_shaarlistes_non_suivis'] === false) {
        return false;
    }

    return true;
}

function displayBestArticle() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['display_best_article']) && $session['shaarlieur_data']['display_best_article'] === true) {
        return true;
    }

    return false;
}

function displayEmptyDescription() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['display_empty_description']) && $session['shaarlieur_data']['display_empty_description'] === false) {
        return false;
    }

    return true;
}

// Indique s'il faut regrouper les liens ou pas
function isModeRiver() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['mode_river']) && $session['shaarlieur_data']['mode_river'] === true) {
        return true;
    }

    return false;
}

function isMenuLocked() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['lock']) && $session['shaarlieur_data']['lock'] === 'lock') {
        return true;
    }

    return false;
}


function isExtended() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['extend']) && $session['shaarlieur_data']['extend'] === false) {
        return false;
    }

    return true;
}

function isInscriptionAuto() {
    $session = getSession();
    if (isset($session['shaarlieur_inscription_auto']) && $session['shaarlieur_inscription_auto'] == true) {
        return true;
    }

    return false;
}

function useElevator() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['use_elevator']) && $session['shaarlieur_data']['use_elevator'] === true) {
        return true;
    }

    return false;
}

function useUselessOptions() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['use_useless_options']) && $session['shaarlieur_data']['use_useless_options'] === true) {
        return true;
    }

    return false;
}

function useDotsies() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['use_dotsies']) && $session['shaarlieur_data']['use_dotsies'] === true) {
        return true;
    }

    return false;
}

function useTopButtons() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['use_top_buttons']) && $session['shaarlieur_data']['use_top_buttons'] === true) {
        return true;
    }

    return false;
}

function useRefreshButton() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['use_refresh_button']) && $session['shaarlieur_data']['use_refresh_button'] === false) {
        return false;
    }

    return true;
}

function displayRssButton() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['display_rss_button']) && $session['shaarlieur_data']['display_rss_button'] === true) {
        return true;
    }

    return false;
}

function displayOnlyUnreadArticles() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['display_only_unread']) && $session['shaarlieur_data']['display_only_unread'] === true) {
        return true;
    }

    return false;
}



function displayImages() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['display_img']) && $session['shaarlieur_data']['display_img'] === false) {
        return false;
    }

    return true;
}

function displayBlocConversation() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['display_bloc_conversation']) && $session['shaarlieur_data']['display_bloc_conversation'] === true) {
        return true;
    }

    return false;
}


function useScrollInfini() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['use_scroll_infini']) && $session['shaarlieur_data']['use_scroll_infini'] === true) {
        return true;
    }

    return false;
}


function useTipeee() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['use_tipeee']) && $session['shaarlieur_data']['use_tipeee'] === false) {
        return false;
    }

    return true;
}



function displayOnlyNewArticles() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['display_only_new_articles']) && $session['shaarlieur_data']['display_only_new_articles'] === true) {
        return true;
    }

    return false;
}

function getTags() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['tags']) && !empty($session['shaarlieur_data']['tags'])) {
        // A VIRER UN JOUR
        if (empty($session['shaarlieur_data']['tags'][0])) {
            return array();
        }
        return $session['shaarlieur_data']['tags'];
    }

    return array();
}

function getNotAllowedTags() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['not_allowed_tags']) && !empty($session['shaarlieur_data']['not_allowed_tags'])) {
        
        // Suppression des tags vides
        foreach ($session['shaarlieur_data']['not_allowed_tags'] as $k => $tag) {
            if (empty($tag)) {
                unset($session['shaarlieur_data']['not_allowed_tags'][$k]);
            }
        }
        return $session['shaarlieur_data']['not_allowed_tags'];
    }

    return array();
}


function getNotAllowedUrls() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['not_allowed_urls']) && !empty($session['shaarlieur_data']['not_allowed_urls'])) {
        
        // Suppression des links vides
        foreach ($session['shaarlieur_data']['not_allowed_urls'] as $k => $url) {
            if (empty($url)) {
                unset($session['shaarlieur_data']['not_allowed_urls'][$k]);
            }
        }
        return $session['shaarlieur_data']['not_allowed_urls'];
    }
    
    return array();
}

function updateTags($tags) {
    $session = getSession();
    if (!empty($tags)) {
        $tags = str_replace("\n", ' ', $tags);
        $tags = str_replace(',', ' ', $tags);
        $tags = explode(' ', trim($tags));
    } else {
        $tags = array();
    }
    $session['shaarlieur_data']['tags'] = $tags;
    setSession($session);
}

/**
 * Ajoute un tag à la liste des tags bloqués
 * 
 * @param string $tag : le tag à filtrer
 * 
 * @return true|false si maj 
 **/
function addNotAllowedTags($tag) {
    $actualsTags = getNotAllowedTags();
    if (!is_array($tag, $actualsTags)) {
        $actualsTags[] = $tag;
        updateNotAllowedTags($actualsTags);
        
        return true;
    }
    
    return false;
}

function updateNotAllowedTags($tags) {
    $session = getSession();
    if (is_string($tags)) {
        $tags = str_replace("\n", ' ', $tags);
        $tags = str_replace(',', ' ', $tags);
        $tags = explode(' ', trim($tags));
    }
    $session['shaarlieur_data']['not_allowed_tags'] = $tags;

    setSession($session);
}

function updateNotAllowedUrls($urls) {
    $session = getSession();
    $urls = str_replace("\n", ' ', $urls);
    $urls = str_replace(',', ' ', $urls);
    $urls = explode(' ', trim($urls));
    $session['shaarlieur_data']['not_allowed_urls'] = $urls;
    setSession($session);
}


function updateCurrentBadge($badge) {
    $session = getSession();
    $session['shaarlieur_data']['badge'] = $badge;
    setSession($session);
}

function getCurrentBadge() {
    $session = getSession();
    if (isset($session['shaarlieur_data']['badge']) && !empty($session['shaarlieur_data']['badge'])) {
        return $session['shaarlieur_data']['badge'];
    }

    return null;
}

function getHash($passwordString) {
    $salt = $GLOBALS['PWD_SALT'];
    echo $salt;

    return hash("sha256", $salt . $passwordString);
}

function createShaarlieurHash($shaarlieurId, $password) {
    return create_hash($shaarlieurId . $password);
}

function setShaarlieurHash($shaarlieurId, $password) {
    $session = getSession();
    $session['shaarlieur_hash'] = createShaarlieurHash($shaarlieurId, $password);
    setSession($session);
}

function getShaarlieurHash($shaarlieurId, $password) {
    $session = getSession();
    if (isset($session['shaarlieur_hash'])) {
        return $session['shaarlieur_hash'];
    }

    return null;
}

function getTopTagsFromTags($tags) {
    if ('' === getUtilisateurId()) {
        return false;
    }
    
    $mysqli = shaarliMyConnect();
    $topTags = getTopTagsFromShaarlieurIdAndTags($mysqli, getUtilisateurId(), $tags);
    
    return $topTags;
}

