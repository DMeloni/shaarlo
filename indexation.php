<?php
// include 'auto_restrict.php';
require_once 'config.php';
require_once 'fct/fct_indexation.php';





$mode = 'today';
if(isset($_GET['mode'])) {
    $mode = $_GET['mode'];
}

$indexationFile = sprintf('%s/%s', $DATA_DIR, $INDEXATION_FILE);

switch($mode) {
    case 'today':
        /*
         * Création de l'index du jour
         */
        $dateDuJour = date('Ymd');
        $rssFile = sprintf('%s/%s/rss_%s.xml', $DATA_DIR, $ARCHIVE_DIR_NAME, $dateDuJour);
        $csv = buildIndexation($rssFile, sprintf('rss_%s.xml', $dateDuJour));
        if($csv != ''){
            file_put_contents($indexationFile, $csv, FILE_APPEND);
        }
        break;
    case 'scratch':
        /*
         * Création de l'index from scratch
         */
        $rssListFile = sprintf('%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME);
        $csv = buildIndexationFromScratch($rssListFile);
        if($csv != ''){
            file_put_contents($indexationFile, $csv);
        }
        break;
    default:
        echo 'mauvais parametre mode : "today" ou "scratch"';
        exit(1);
        break;
}

/*
 * Enregistrement du fichier d'index
 */
//echo $csv;


