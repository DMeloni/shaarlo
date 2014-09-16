<?php

require_once('fct/fct_rss.php');

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
            <title>Shaarlo : My</title>
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
        <a href="admin.php">Administration</a>
        <a href="random.php">Aléatoire</a>
        <a href="my.php">My</a>
        <a href="opml.php?mod=opml">OPML</a>
        <a href="https://nexen.mkdir.fr/shaarli-river/" id="river">Shaarli River</a>
        <h1 id="top"><a href="./my.php">Discussion</a></h1> 
    </div> 
<div id="content">
    <div class="article shaarli-youm-org">
        <?php 
            $urlEntities = '';
            if(!empty($_GET['url'])) {
               $urlEntities = htmlentities($_GET['url']);
               $itemAboutDiscussions = @json_decode(remove_utf8_bom(@file_get_contents(sprintf('http://shaarli.fr/api.php?do=getDiscussionAboutUrl&url=%s', urlencode($_GET['url'])))), true);
            }
            
        ?>
        <h2 class=" article-title ">Afficher la discussion autour d'une url</h2>
        Cette page permet de retrouver tous les shaarlinks en rapport avec une URL
        <form action="" method="GET">
            <input name="url" placeholder="http://machin" value="<?php echo $urlEntities; ?>" />
            <input name="action" value="discussion" type="hidden" />
            <input type="submit" value="Continuer" />
        </form>     

    </div>
    <div class="article shaarli-youm-org">
        <?php 
            if (!empty($itemAboutDiscussions)) {
                usort($itemAboutDiscussions , "compareDeuxDates");
                $premierPassage = true;
                $nbItems = count($itemAboutDiscussions);
                foreach($itemAboutDiscussions as $item) {
                    if($premierPassage) {
                        ?><h2 class="article-title toptopic" ><a href="<?php echo $item['link']; ?>"><?php echo $item['title']; echo sprintf(' [%s]', $nbItems);?></a></h2><?php
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
        ?>
    </div>

</div>
</body>
</html>


