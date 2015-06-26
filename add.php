<?php
require_once 'config.php';
require_once 'fct/fct_session.php';
require_once 'fct/fct_mysql.php';
/*
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
*/

if(isset($_POST['do']) && $_POST['do'] == 'ireadit') {
    if(!isset($_SESSION['ireadit'])) {
        $_SESSION['ireadit'] = array();
        $_SESSION['ireadit']['id'] = array();
    }

    if(!isset($_SESSION['ireadit']['id'][$_POST['id']])) {
        $lienClic = creerLiensClic($_POST['id']);
        
        $mysqli = shaarliMyConnect();
        insertEntite($mysqli, 'liens_clic', $lienClic);

        $shaarlieurLienClic = creerShaarlieurLiensClic($_POST['id'], getUtilisateurId());
        insertEntite($mysqli, 'shaarlieur_liens_clic', $shaarlieurLienClic);
        shaarliMyDisconnect($mysqli);

        $_SESSION['ireadit']['id'][$_POST['id']] = $_POST['id'];
    }

    header('HTTP/1.1 200 OK', true, 200);
    return;
} 

/*
 * Ignorage d'un article
*/
if(isset($_POST['do']) && $_POST['do'] == 'ignoreit' && isset($_POST['id'])) {  
    $mysqli = shaarliMyConnect();
    $shaarlieurLienIgnore = creerShaarlieurLiensIgnore($_POST['id'], getUtilisateurId());
    insertEntite($mysqli, 'shaarlieur_liens_ignore', $shaarlieurLienIgnore);
    shaarliMyDisconnect($mysqli);

    $_SESSION['ireadit']['id'][$_POST['id']] = $_POST['id'];

    header('HTTP/1.1 200 OK', true, 200);
    return;
}

/*
 * Enregistrement lock du menu
 */
if(isset($_POST['do']) && $_POST['do'] == 'lock') {
    $session = getSession();

    if(!isset($session['shaarlieur_data']['lock'])) {
        $session['shaarlieur_data']['lock'] = 'open';
    }
    
    $states = array('open', 'lock');

    if (isset($_POST['state']) 
     && in_array($_POST['state'], $states)
    ) {
        $session['shaarlieur_data']['lock'] = $_POST['state'];
        setSession($session);
    }

    header('HTTP/1.1 200 OK', true, 200);
    return;
}

/*
 * Enregistrement affichage des shaarlistes non suivis
 */
if(isset($_POST['do']) && $_POST['do'] == 'display_shaarlistes_non_suivis' && isset($_POST['value'])) {
    if($_POST['value'] == 'oui') {
        $session = getSession();
        $session['shaarlieur_data']['display_shaarlistes_non_suivis'] = true;
        setSession($session);
    } 
    if($_POST['value'] == 'non') {
        $session = getSession();
        $session['shaarlieur_data']['display_shaarlistes_non_suivis'] = false;
        setSession($session);

    }   

    header('HTTP/1.1 200 OK', true, 200);
    return;
}

/*
 * Enregistrement affichage du bloc en ce moment
 */
if(isset($_POST['do']) && $_POST['do'] == 'display_best_article' && isset($_POST['value'])) {
    if($_POST['value'] == 'oui') {
        $session = getSession();
        $session['shaarlieur_data']['display_best_article'] = true;
        setSession($session);
    } 
    if($_POST['value'] == 'non') {
        $session = getSession();
        $session['shaarlieur_data']['display_best_article'] = false;
        setSession($session);

    }   

    header('HTTP/1.1 200 OK', true, 200);
    return;
}

/*
 * Enregistrement inscription auto aux shaarlistes
 */
if(isset($_POST['do']) && $_POST['do'] == 'inscription_auto' && isset($_POST['value'])) {
    if($_POST['value'] == 'oui') {
        majInscriptionAuto(true);
    } 
    if($_POST['value'] == 'non') {
        majInscriptionAuto(false);
    }   

    header('HTTP/1.1 200 OK', true, 200);
    return;
}

/*
 * Modification du badge actif
 */
if(isset($_POST['do']) && $_POST['do'] == 'badge' && isset($_POST['value'])) {
    updateCurrentBadge($_POST['value']);

    header('HTTP/1.1 200 OK', true, 200);
    return;
}



$optionsAutorisees = array('extend', 'mode_river', 'display_empty_description', 
    'use_elevator', 'use_useless_options','use_dotsies',
    'use_top_buttons',
    'use_refresh_button',
    'display_rss_button',
    'display_bloc_conversation',
    'use_scroll_infini',
    'display_only_new_articles',
    'use_tipeee',
);

if(isset($_POST['do']) && in_array($_POST['do'], $optionsAutorisees) && isset($_POST['value'])) {
    if($_POST['value'] == 'oui') {
        $session = getSession();
        $session['shaarlieur_data'][$_POST['do']] = true;
        setSession($session);
    } 
    if($_POST['value'] == 'non') {
        $session = getSession();
        $session['shaarlieur_data'][$_POST['do']] = false;
        setSession($session);

    }   

    header('HTTP/1.1 200 OK', true, 200);
    return;
}


$username = null;
if (isset($_SESSION['username'])) {
    if (getUtilisateurId() === '') {
        header('HTTP/1.1 401 Unauthorized', true, 401);
        return;
    }
    //Ajoute un shaarli
    if($_POST['id']) {
        $mysqli = shaarliMyConnect();
        
        if(isset($_POST['do']) && $_POST['do'] == 'delete') {
            deleteRss($mysqli, $_SESSION['username'], $_POST['id']);
        }elseif(isset($_POST['do']) && $_POST['do'] == 'add') {
            $monRss = creerMonRss($_SESSION['username'], $_POST['id'], $_SESSION['username']);
            insertEntite($mysqli, 'mes_rss', $monRss);
        }
        
        shaarliMyDisconnect($mysqli);
        header('HTTP/1.1 200 OK', true, 200);
        return;
    }
}

if (isset($_SESSION['shaarlieur_id'])) {
    if (getUtilisateurId() === '') {
        header('HTTP/1.1 401 Unauthorized', true, 401);
        return;
    }
    //Ajoute un shaarli
    if($_POST['id']) {
        $mysqli = shaarliMyConnect();
        
        if(isset($_POST['do']) && $_POST['do'] == 'delete') {
            // On est autorisé uniquement si on a plus d'un abonnement encore
            $abonnements = getAbonnements();
            $nbAbonnements = count($abonnements);
            if ($nbAbonnements > 1) {
                deleteRss($mysqli, $_SESSION['shaarlieur_id'], $_POST['id']);
            } else {
                header('HTTP/1.1 202 Accepted', true, 202);
                return;
            }
        }elseif(isset($_POST['do']) && $_POST['do'] == 'add') {
            $monRss = creerMonRss($_SESSION['shaarlieur_id'], $_POST['id'], $_SESSION['shaarlieur_id']);
            insertEntite($mysqli, 'mes_rss', $monRss);
        }
        
        shaarliMyDisconnect($mysqli);
        header('HTTP/1.1 200 OK', true, 200);
        return;
    }
}else{
    header('HTTP/1.1 401 Unauthorized', true, 401);
    return;
}


header('HTTP/1.1 401 Bad Request', true, 400);
return;
