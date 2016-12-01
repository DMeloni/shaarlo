<?php
//ini_set("display_errors", 1);
//ini_set("track_errors", 1);
//ini_set("html_errors", 1);
//error_reporting(E_ALL);
// include 'auto_restrict.php';
require_once 'config.php';
require_once 'fct/fct_rss.php';
require_once 'fct/fct_mysql.php';
require_once 'fct/fct_http.php';
require_once 'fct/fct_session.php';
include_once('fct/fct_capture.php');

set_time_limit(5);



// Récupération du shaarli de l'utilisateur
$url = getShaarliUrlOk();
if (empty($url)) {
    die;
}

$retourSynchro = synchroShaarli($url, false, false, 10);

if ($retourSynchro === true) {
        sleep(1);
        header('HTTP/1.1 200 OK', true, 200);
        return;
}

sleep(1);
header('HTTP/1.1 206 Partial Content', true, 206);


        
        
        
