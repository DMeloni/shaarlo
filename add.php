<?php
ini_set('session.save_path', $_SERVER['DOCUMENT_ROOT'].'/sessions');
ini_set('session.use_cookies', 1);       // Use cookies to store session.
ini_set('session.use_only_cookies', 1);  // Force cookies for session (phpsessionID forbidden in URL)
ini_set('session.use_trans_sid', false); // Prevent php to use sessionID in URL if cookies are disabled.
ini_set('session.cookie_domain', '.shaarli.fr');
session_name('shaarli');
session_start();

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
        
        if(isset($_POST['do']) && $_POST['do'] == 'delete') {
            deleteRss($mysqli, $_SESSION['username'], $_POST['id']);
        }elseif(isset($_POST['do']) && $_POST['do'] == 'add') {
            $monRss = creerMonRss($_SESSION['username'], $_POST['id']);
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
