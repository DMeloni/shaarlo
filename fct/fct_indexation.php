<?php

require_once 'fct_rss.php';

/**
 * Retourne un csv d'index d'un répertoire de fichiers d'archive
 */
function buildIndexationFromScratch($archiveDir)
{
    $rssListFile = scandir($archiveDir);
    $csv = '';
    foreach ($rssListFile as $rssFile) {
//Traitement du fichier d'archive
        $csv .= buildIndexation(sprintf('%s/%s', $archiveDir, $rssFile), $rssFile);
    }

    return $csv;
}

/**
 * Créer un csv d'index d'un fichier d'archive
 * @param $file
 * @return string
 */
function buildIndexation($filePath, $file)
{
    $csv = '';

    if (is_file($filePath)) {
        $contenu = file_get_contents($filePath);
        $xml = getSimpleXMLElement($contenu);
        if ($xml === false) {
            return $csv;
        }
        $tableauDArticles = convertXmlToTableau($xml, XPATH_RSS_ITEM);
        foreach ($tableauDArticles as $article) {
            $date = new DateTime($article['pubDate']);
            $count = substr_count($article['description'], "Permalink");
            if ($count > 0) {
                $csv .= sprintf('"%s";"%s";"%s";"%s";"%s"' . "\n", $file, $date->format('Ymd'), $date->format('YmdHis'), $count, $article['link']);
            }
        }
    }
    return $csv;
}