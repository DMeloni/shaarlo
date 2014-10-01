<?php
require_once 'config.php';
require_once 'fct/fct_mysql.php';
/*
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
*/

$username = null;
if (isset($_SESSION['username'])) {
    //Ajoute un shaarli
    if($_POST['id']) {
        $mysqli = shaarliMyConnect();
        var_export($_POST);
        
        if(isset($_POST['do']) && $_POST['do'] == 'bloquer') {
            bloquerRss($mysqli, $_POST['id']);
        }elseif(isset($_POST['do']) && $_POST['do'] == 'valider') {
            validerRss($mysqli, $_POST['id']);
        }elseif(isset($_POST['do']) && $_POST['do'] == 'bloquerLien') {
            bloquerLien($mysqli, $_POST['id']);
        }elseif(isset($_POST['do']) && $_POST['do'] == 'validerLien') {
            validerLien($mysqli, $_POST['id']);
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
