<?php
require_once 'config.php';
require_once 'fct/fct_session.php';
require_once 'fct/fct_mysql.php';
/*
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
*/

if (getUtilisateurId() === '') {
    header('HTTP/1.1 304 Not modified', true, 304);
    return;
}

if(isset($_POST['do']) && $_POST['do'] == 'ireadit') {
    if(!isset($_SESSION['ireadit'])) {
        $_SESSION['ireadit'] = array();
        $_SESSION['ireadit']['id'] = array();
    }

    if(!isset($_SESSION['ireadit']['id'][$_POST['id']])) {
        $mysqli = shaarliMyConnect();
        
        $isLienDejaClic = isLienDejaClic($mysqli, $_POST['id'], getUtilisateurId());

        if (false === $isLienDejaClic) {
            $lienClic = creerLiensClic($_POST['id']);
            insertEntite($mysqli, 'liens_clic', $lienClic);
            $shaarlieurLienClic = creerShaarlieurLiensClic($_POST['id'], getUtilisateurId());
            insertEntite($mysqli, 'shaarlieur_liens_clic', $shaarlieurLienClic);
            shaarliMyDisconnect($mysqli);
            $_SESSION['ireadit']['id'][$_POST['id']] = $_POST['id'];
            
            header('HTTP/1.1 200 OK', true, 200);
            return;
        } else {
            header('HTTP/1.1 304 Not modified', true, 304);
            return;
        }
    }

    header('HTTP/1.1 200 OK', true, 200);
    return;
} 

// Don d'un poussin à un shaarlieur
if(isset($_POST['do']) && $_POST['do'] == 'poussin'
&& isset($_POST['shaarlieur'])
&& isset($_POST['id_lien'])
) {
    $shaarlieurCible = $_POST['shaarlieur'];
    $idLien = $_POST['id_lien'];
    
    $mysqli = shaarliMyConnect();
    $shaarlieurId = getUtilisateurId();

    // Pas possible de se donner à soi même
    if ($shaarlieurId == $shaarlieurCible) {
        header('HTTP/1.1 304 Not modified', true, 304);
        return;
    }

    $nbPoussinsLimite = getNbPoussinsLimiteByShaarlieurId($mysqli,  $shaarlieurId);
    
    $dateJour = date('Ymd');
    
    $nbPoussinsUtilises = getNbPoussinsUtilisesByShaarlieurId($mysqli,  $shaarlieurId, $dateJour);
    
    $nbPoussinsDisponibles = $nbPoussinsLimite - $nbPoussinsUtilises;
     
    // Plus de poussins disponibles
    if ($nbPoussinsDisponibles <= 0) {
        header('HTTP/1.1 304 Not modified', true, 304);
        return;
    }

    $transactionPoussin = creerTransactionPoussin($shaarlieurId, $shaarlieurCible, $dateJour, $idLien);
    $retourInsertion = insertEntite($mysqli, 'poussins_transactions', $transactionPoussin);

    if (true !== $retourInsertion) {
        header('HTTP/1.1 304 Not modified', true, 304);
        return;
    }

    $nbPoussinsUtilises = getNbPoussinsUtilisesByShaarlieurId($mysqli,  $shaarlieurId, $dateJour);

    // Transaction validée
    if ($nbPoussinsUtilises <= $nbPoussinsLimite) {
        // On ajoute un poussin au solde du shaarlieur cible
        $retourAjoutPoussin = addPoussinToShaarlieurByShaarlieurId($mysqli, $shaarlieurCible);
        
        if ($retourAjoutPoussin) {
            header('HTTP/1.1 200 OK', true, 200);
            return;
        }
    }
    
    // Probleme : l'utilisateur a surement essayer de spammer le don
    // Dans ce cas on annule la transaction
    deleteTransactionPoussin($mysqli, $shaarlieurSource, $shaarlieurCible, $dateJour);

    header('HTTP/1.1 304 Not modified', true, 304);
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



$optionsAutorisees = array('extend', 'mode_river', 
    'display_empty_description', 
    'use_elevator', 
    'use_useless_options',
    'use_dotsies',
    'use_top_buttons',
    'use_refresh_button',
    'display_rss_button',
    'display_bloc_conversation',
    'use_scroll_infini',
    'display_only_new_articles',
    'use_tipeee',
    'display_img',
    'display_only_unread',
    'display_discussions',
    'display_shaarlistes_non_suivis',
    'display_little_img',
    'display_poussins'
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

if(isset($_POST['do']) && 'on_abonnements' === $_POST['do'] && isset($_POST['value'])) {
    if($_POST['value'] == 'oui') {
        majShaarliOnAbonnements(true);
    } 
    if($_POST['value'] == 'non') {
        majShaarliOnAbonnements(false);
    }   
    
    header('HTTP/1.1 200 OK', true, 200);
    return;
}

if(isset($_POST['do']) && 'on_river' === $_POST['do'] && isset($_POST['value'])) {
    if($_POST['value'] == 'oui') {
        majShaarliOnRiver(true);
    } 
    if($_POST['value'] == 'non') {
        majShaarliOnRiver(false);
    }   
    
    header('HTTP/1.1 200 OK', true, 200);
    return;
}
if (isset($_SESSION['shaarlieur_id'])) {
    if (getUtilisateurId() === '') {
        header('HTTP/1.1 401 Unauthorized', true, 401);
        return;
    }
    //Ajoute un shaarli
    if(isset($_POST['id'])) {
        $mysqli = shaarliMyConnect();
        $abonnements = getAbonnements();
        if(isset($_POST['do']) && $_POST['do'] == 'delete') {
            // On est autorisé uniquement si on a plus d'un abonnement encore
            $nbAbonnements = count($abonnements);
            if ($nbAbonnements > 1) {
                foreach ($abonnements as $k => $abo) {
                    if ($abo === $_POST['id']) {
                        unset($abonnements[$k]);
                    }
                }
                majAbonnements($abonnements);
            } else {
                header('HTTP/1.1 202 Accepted', true, 202);
                return;
            }
        }elseif(isset($_POST['do']) && $_POST['do'] == 'add') {
            $abonnements[] = $_POST['id'];
            majAbonnements($abonnements);
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
