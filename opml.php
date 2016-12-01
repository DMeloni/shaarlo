<?php 

require_once('Controller.class.php');

global $SHAARLO_DOMAIN;

$infoAboutAll = file_get_contents('http://'.$SHAARLO_DOMAIN.'/api.php?do=getInfoAboutAll');
$infoAboutAll = remove_utf8_bom($infoAboutAll);
$infoAboutAllDecoded = json_decode($infoAboutAll, true);

$abonnements = getAbonnements();

$subscription = '<?xml version="1.0" encoding="UTF-8"?>
    <opml version="1.0">
        <head>
            <title>Abonnements</title>
        </head>
        <body>';
foreach($infoAboutAllDecoded["stat"] as $rssLabel => $rss){
    if ('1' == $rss['is_dead'] ) {
        continue;
    }
    $rssUrl = htmlspecialchars($rss['link']) . '?do=rss';
    if ('le hollandais volant' == $rss['title']) {
        $rssUrl = htmlspecialchars($rss['link']);
    }
    $rssLabel = htmlspecialchars($rss['title']);
    
    // Export de ses shaarlis si connecté
    if(!empty($abonnements)) {
        if (in_array($rss['id'], $abonnements)) {
            $subscription .= sprintf('<outline text="%s" title="%s" type="rss" xmlUrl="%s" htmlUrl="%s"/>', $rssLabel, $rssLabel, $rssUrl, $rssUrl);
        }
    } else {
        $subscription .=  sprintf('<outline text="%s" title="%s" type="rss" xmlUrl="%s" htmlUrl="%s"/>', $rssLabel, $rssLabel, $rssUrl, $rssUrl);
    }
}
$subscription .= '</body></opml>';

$opmlFileTmp = sprintf(rand(0,10000).'opml.xml');
file_put_contents($opmlFileTmp, $subscription);

header("Content-disposition: attachment; filename=subscriptions.xml");
header("Content-Type: application/xml");
header("Content-Transfer-Encoding: xml\n"); // Surtout ne pas enlever le \n
//header('Content-Transfer-Encoding: binary');
header("Content-Length: ".(filesize($opmlFileTmp) + 10));
header("Pragma: no-cache");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
header("Expires: 0");
readfile($opmlFileTmp);

unlink($opmlFileTmp);


