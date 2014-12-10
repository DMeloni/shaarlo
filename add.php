<?php
require_once 'config.php';
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
        shaarliMyDisconnect($mysqli);
        $_SESSION['ireadit']['id'][$_POST['id']] = $_POST['id'];
    }

    header('HTTP/1.1 200 OK', true, 200);
    return;
} 

/*
 * Enregistrement lock du menu
 */
if(isset($_POST['do']) && $_POST['do'] == 'lock') {
    if(!isset($_SESSION['lock'])) {
        $_SESSION['lock'] = array();
        $_SESSION['lock']['state'] = 'open';
    }
    
    $states = array('open', 'lock');

    if (isset($_POST['state']) 
     && in_array($_POST['state'], $states)
    ) {
        $_SESSION['lock']['state'] = $_POST['state'];
    }

    header('HTTP/1.1 200 OK', true, 200);
    return;
}

$username = null;
if (isset($_SESSION['username'])) {
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
}else{
    header('HTTP/1.1 401 Unauthorized', true, 401);
    return;
}
header('HTTP/1.1 401 Bad Request', true, 400);
return;
