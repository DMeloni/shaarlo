<?php

require_once('config.php');
require_once('fct/fct_rss.php');

ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);

function compareDeuxDates($a, $b)
{
    if ($a['pubdateiso'] == $b['pubdateiso']) {
        return 0;
    }
    return ($a['pubdateiso'] < $b['pubdateiso']) ? -1 : 1;
}

// Affichage
?><!DOCTYPE html>
<html lang="fr"> 
        <head>
            <title>Shaarlo : Messagerie</title>
            <meta charset="utf-8"/>
            <meta name="description" content="" />
            <meta name="author" content="" />
            <meta name="viewport" content="width=device-width, user-scalable=yes" />
            <link rel="apple-touch-icon" href="favicon.png" />
            <meta name="apple-mobile-web-app-capable" content="yes" />
            <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
            <link rel="shortcut icon" href="favicon.ico" />
            <link rel="stylesheet" href="css/style.css" type="text/css" media="screen"/>
            <link rel="alternate" type="application/rss+xml" href="http://shaarli.fr/rss" title="Shaarlo Feed" />
        </head> 
<body>
    <div id="header">
        <a href="index.php">Accueil</a>
        <a href="random.php">Aléatoire</a>
        <a href="my.php">My</a>
        <h1 id="top"><a href="./my.php">Messagerie</a></h1> 
    </div> 
<div id="content">
    <div class="article shaarli-youm-org">
        <?php 
            $urlEntities = '';
            $limite = 10;
            if(!empty($_GET['limit'])) {
                $limite = $_GET['limit']; 
            }
            global $SHAARLO_DOMAIN;
            if(!empty($_GET['url'])) {
               $urlEntities = htmlentities($_GET['url']);
               echo sprintf('http://%s/api.php?do=getMessagerieAboutUrl&url=%s&limit=%s', $SHAARLO_DOMAIN, urlencode($_GET['url']), $limite);
               $messagerie = json_decode(remove_utf8_bom(file_get_contents(sprintf('http://%s/api.php?do=getMessagerieAboutUrl&url=%s&limit=%s', $SHAARLO_DOMAIN, urlencode($_GET['url']), $limite))), true);
           }
        ?>
        <h2 class=" article-title ">Afficher la messagerie autour d'un shaarli</h2>
        Cette page permet de retrouver tous les messages en rapport avec les derniers shaarlinks
        <form action="" method="GET">
            <input name="url" placeholder="http://shaarli.fr/shaarli" value="<?php echo $urlEntities; ?>" />
            <input name="action" value="messagerie" type="hidden" />
            <input name="limit" value="5" type="hidden" />
            <input type="submit" value="Continuer" />
        </form>     

    </div>
    <div class="article shaarli-youm-org">
        <?php 
        if (!empty($messagerie)) {
            foreach($messagerie as $key => $itemAboutDiscussions) {
                $premierPassage = true;
                if (!empty($itemAboutDiscussions)) {
                    $nbItems = count($itemAboutDiscussions);
                    $firstItem = reset($itemAboutDiscussions);
                    ?><li><a href="#link<?php echo $key; ?>"><?php 
                    
                    echo sprintf('%s [%s]', $firstItem['title'], $nbItems); 
                    ?></a></li><?php
                }
            }
       }
    ?>
    </div>
    
    <?php 
    if (!empty($messagerie)) {
        foreach($messagerie as $key => $itemAboutDiscussions) {
            ?><div id="link<?php echo $key;?>" class="article shaarli-youm-org"><?php
            $premierPassage = true;
            if (!empty($itemAboutDiscussions)) {
                usort($itemAboutDiscussions , "compareDeuxDates");

                $nbItems = count($itemAboutDiscussions);
                foreach($itemAboutDiscussions as $item) {
                    if($premierPassage) {
                        ?><h2 class="article-title <?php if($nbItems>1) echo 'toptopic'; ?>" ><a href="<?php echo $item['link']; ?>"><?php echo $item['title']; echo sprintf(' [%s]', $nbItems);?></a></h2><?php
                        $premierPassage = false;
                    }
                    
                    if($item['origin']) {
                        echo '<div class="important">';
                    }
                    $date = new DateTime($item['pubdateiso']);
                    ?><b><?php echo $item['rss_title']; ?></b>, le <?php echo $date->format('d/m/Y'); ?> à <?php echo $date->format('H:i'); ?><br/>
                    <?php echo $item['description']; ?><br/><br/><?php
                    if($item['origin']) {
                        echo '</div>';
                    }
                }
            }else{
                echo 'pas de resultat - fonction expérimentale';
            }
            ?></div><?php
        }
    }
    ?>
</div>
</body>
</html>


