<?php
ini_set('session.save_path', $_SERVER['DOCUMENT_ROOT'].'/sessions');
ini_set('session.use_cookies', 1);       // Use cookies to store session.
ini_set('session.use_only_cookies', 1);  // Force cookies for session (phpsessionID forbidden in URL)
ini_set('session.use_trans_sid', false); // Prevent php to use sessionID in URL if cookies are disabled.
ini_set('session.cookie_domain', '.shaarli.fr');
session_name('shaarli');
session_start();
require_once('fct/fct_rss.php');
require_once 'fct/fct_mysql.php';

/** 
* Get the directory size 
* @param directory $directory 
* @return integer 
*/ 
function dirSize($directory) { 
    $size = 0; 
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){ 
        $size+=$file->getSize(); 
    } 
    return $size; 
} 

function compareDeuxDates($a, $b)
{
    if ($a['pubdateiso'] == $b['pubdateiso']) {
        return 0;
    }
    return ($a['pubdateiso'] < $b['pubdateiso']) ? -1 : 1;
}


$myPath = 'my';
$myDir = scandir($myPath);
$pattern = 'data-7987213-';

$dirDateTime = new DateTime();
$listeDeShaarlistes = array();

$infoAbout = json_decode(remove_utf8_bom(file_get_contents('https://www.shaarli.fr/api.php?do=getInfoAboutAll')), true);
$pubDate = $infoAbout['pubdate'];
$infoAbout = $infoAbout['stat'];
usort($infoAbout , "compareDeuxDates");


// Si demande OPML 
if (!empty($_POST)) {
    if($_POST['action'] == 'export') {
        $subscription = '<?xml version="1.0" encoding="UTF-8"?>
            <opml version="1.0">
                <head>
                    <title>shaarlis</title>
                </head>
                <body>';

        foreach($infoAbout as $shaarliste){
            if(!isset($_POST['checked']) ||  in_array(md5($shaarliste['link']), $_POST['checked'])) {
                $rssLabel = htmlspecialchars($shaarliste['title']);
                $rssUrl = htmlspecialchars(sprintf('%s%s', $shaarliste['link'], '?do=rss'));
                $subscription .=  sprintf('<outline text="%s" title="%s" type="rss" xmlUrl="%s" htmlUrl="%s"/>', $rssLabel, $rssLabel, $rssUrl, $rssUrl);
            }
        }
        $subscription .= '</body></opml>';
        
        $rand = rand(1, 500);
        $opmlFileTmp = sprintf('data/my-%s', $rand);
        file_put_contents($opmlFileTmp, remove_utf8_bom($subscription));
        
        header("Content-disposition: attachment; filename=my.xml");
        header("Content-Type: application/xml; charset=utf-8");
        header("Content-Transfer-Encoding: xml\n"); // Surtout ne pas enlever le \n
        //header('Content-Transfer-Encoding: binary');
        header("Content-Length: ".(filesize($opmlFileTmp) + 10));
        header("Pragma: no-cache");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
        header("Expires: 0");
        readfile($opmlFileTmp);
        
        unlink($opmlFileTmp);
        exit(0);
    }
    
    if($_POST['action'] == 'new') {
        header(sprintf('Location: http://my.shaarli.fr/%s/?do=login', $_POST['pseudo']));
    }
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
            <script>
                /* Merci Eric Marcus (Aout 2006)*/
                function GereChkbox() {
                    var ChckboxAll = document.getElementById("ChckboxAll");
                    var ChckboxList = document.getElementsByClassName("ChckboxShaarliste");
                    for (var i = 0; i < ChckboxList.length; i++) {
                        ChckboxList[i].checked=ChckboxAll.checked;
                    }
                }
            </script>
        </head> 
<body>
    <div id="header">
        <a href="index.php">Accueil</a>
        <a href="random.php">Aléatoire</a>
        <a href="my.php">My</a>
        <h1 id="top"><a href="./my.php">Espace My</a></h1> 
    </div> 
<div id="content">
<?php 
    $username = null;
    $mesAbonnements = null;
    $mesAbonnementsApresAction = null;
    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
        $mysqli = shaarliMyConnect();
        $mesAbonnements = getAllAbonnements($mysqli, $username);
        shaarliMyDisconnect($mysqli);
        //Ajoute un shaarli
        if(isset($_GET['id'])) {
            $mysqli = shaarliMyConnect();
            
            if(isset($_GET['do']) && $_GET['do'] == 'delete') {
                deleteRss($mysqli, $_SESSION['username'], $_GET['id']);
            }elseif(isset($_GET['do']) && $_GET['do'] == 'add') {
                $monRss = creerMonRss($_SESSION['username'], $_GET['id']);
                insertEntite($mysqli, 'mes_rss', $monRss);
            }
            $mesAbonnementsApresAction = getAllAbonnements($mysqli, $username);
            shaarliMyDisconnect($mysqli);
        }

        if(isset($_GET['id'])) {
            if(isset($_GET['do']) && $_GET['do'] == 'delete') {
                if(!isset($mesAbonnementsApresAction[$_GET['id']])) {
                ?>
                <div class="article shaarli-youm-org">
                    <h2 class="article-title ">Vous ne suivez plus  
                        <a href="<?php echo $mesAbonnements[$_GET['id']]['url'];?>"><?php echo htmlentities($mesAbonnements[$_GET['id']]['rss_titre']);?></h2>
                    </h2>
                </div>                
                <?php
                }
            }elseif(isset($_GET['do']) && $_GET['do'] == 'add') {
                if(isset($mesAbonnementsApresAction[$_GET['id']])) {
                ?>
                <div class="article shaarli-youm-org">
                    <h2 class="article-title ">Vous suivez désormais 
                    <a href="<?php echo $mesAbonnementsApresAction[$_GET['id']]['url'];?>"><?php echo htmlentities($mesAbonnementsApresAction[$_GET['id']]['rss_titre']);?></h2>
                </div>                
                <?php
                }
            }
            
            $mesAbonnements = $mesAbonnementsApresAction;
        }
        ?>
        <div class="article shaarli-youm-org">
            <h2 class=" article-title ">Connecté en tant que '<?php echo htmlentities($_SESSION['username']);?>'</h2>
            <ul>
                <li><a href="<?php echo sprintf("http://my.shaarli.fr/%s/", htmlentities($_SESSION['username'])); ?>">Accéder à mon shaarli</a> </li>
                <li><a href="<?php echo sprintf("/messagerie.php?url=%s", urlencode(sprintf('http://my.shaarli.fr/%s/', htmlentities($_SESSION['username'])))); ?>">Accéder à ma messagerie</a></li> 
                <li>Astuce : l'icone ☆ permet de copier un lien directement dans son shaarli</li>
            </ul>
        </div><?php
    }else{
        if(isset($_GET['do']) && ($_GET['do'] == 'delete' || $_GET['do'] == 'add')) {?>
            <div class="article shaarli-youm-org">
                <h2 class="article-title ">Cette fonction nécéssite d'être connecté via un shaarli interne</div> 
            <?php
        }
    
    ?>
        <div class="article shaarli-youm-org">
            <h2 class=" article-title ">Créer un shaarli en deux secondes</h2>
            <form action="" method="POST">
                Mon pseudo : 
                <input name="pseudo" value="" placeholder="robocop, batman"/>
                <input name="action" value="new" type="hidden" />
                <input type="submit" value="Continuer" />
            </form>
            <ul>
                <li><span>Gratuit</span></li>
                <li><span>Hébergement mutualisé (OVH) (<a href="http://www.ovh.com/fr/support/contrats/Conditions-generales-hebergement-mutualise.pdf">CG</a>)</span></li>
            </ul>        
        </div>
    <?php } ?>
    <div class="article shaarli-youm-org"> 
        <h2 class=" article-title ">Autour du site</h2>
        <div>
            Vous pouvez accéder à l'<a href="api.php">API</a> du site (compétences techniques)
        </div>
        <div>
            Ou accéder aux <a href="activite.php">top tags </a>
        </div>
        <div>
            Ou accéder aux <a href="archive.php">archives</a>
        </div>
        <div>
            Ou retrouver l'<a href="discussion.php">origine d'un lien</a>
        </div>
        <div>
            Ou consulter la <a href="messagerie.php">messagerie</a> d'un shaarli
        </div>
        <div>
            Ou participer à la  <a href="jappix-1.0.7/?r=shaarli@conference.dukgo.com" >discussion en ligne</a>
        </div>
        <div>
            Ou proposer une idée par mail à <a href="mailto:contact@shaarli.fr">contact@shaarli.fr</a>
        </div>
        <div>
            Ou accéder au <a href="https://github.com/DMeloni/shaarlo">code source de shaarlo</a><br />
            Ou celui du <a href="https://github.com/DMeloni/shaarli">shaarli multicomptes</a><br />
        </div>
    </div>
<div class="article shaarli-youm-org">
    <h2 class=" article-title ">Annuaire des shaarlis</h2>
    <div>Mise à jour : 
    <?php 
    $pubDateTime = new DateTime($pubDate);
    echo sprintf('Le %s à %s:%s', $pubDateTime->format('d/m/Y'), $pubDateTime->format('H'), $pubDateTime->format('i')); 
    ?>
    </div>
    <form action="" method="POST">
        <input name="action" value="export" type="hidden" />
        <table>
            <thead>
                <th><span>Dernière Maj</span></th>
                <th><input type="checkbox" id="ChckboxAll" value="" onClick="GereChkbox();"></th>
                <th><span>Titre</span></th>
                <th><span>Mots clefs</span></th>
                <th><span>Nb de liens</span></th>
                <th><span>Popularité</span></th>
                <th><span>Localisation</span></th>
                <th>
                <?php
                    if(!is_null($mesAbonnements)) {
                ?>
                    <span>Abonnement</span>
                <?php } else { ?>
                    <span>Annuaire</span>
                <?php
                    }
                ?>
                </th>
            </thead>
            <tbody id="div_chck_actif">
            <?php
                $nbExternalShaarlistes=0;
                $nbMyShaarlistes=0;
                $nbLiensMyShaarlistes = 0;
                $nbLiensExternalShaarlistes = 0;
                foreach ($infoAbout as $shaarliste) {
                    if($shaarliste['pubdateiso'] == '2011-09-14') {
                        continue;
                    }
                    if ($shaarliste['nb_items'] <= 1) {
                        continue;
                    }
                    if ($shaarliste['title'] == 'le hollandais volant') {
                        continue;
                    }
                    $pseudoShaarliste = null;
                    if($shaarliste['my']){   
                        $matches = array();
                        preg_match_all('#/([^/.]+)/$#', $shaarliste['link'], $matches);
                        if(isset($matches[1][0])){
                            $pseudoShaarliste = $matches[1][0];
                        }
                    }                    
                    ?><tr  <?php if(!is_null($username) && $pseudoShaarliste === $username) echo 'class="red"' ?> ><?php
                        ?><td><?php 
                            echo $shaarliste['pubdateiso'];
                        ?>
                        </td>
                        <td><input class="ChckboxShaarliste" name="checked[]" type="checkbox" value="<?php echo md5($shaarliste['link']);?>" /></td><?php   
                        ?><td><a href="<?php echo $shaarliste['link'];?>"><?php echo (htmlspecialchars($shaarliste['title']));?></a></td><?php    
                        ?><td><?php 
                            $tagsAvecUnderscore = array_keys($shaarliste['tags']);
                            $tags = array();
                            foreach($tagsAvecUnderscore as $tag){
                                $tags[] = substr($tag, 1, strlen($tag) - 1);
                            }
                            echo implode(', ', $tags);?></td><?php   
                        ?><td><?php 
                            if($shaarliste['my']){                        
                                $nbLiensMyShaarlistes += $shaarliste['nb_items'];
                            }else{
                                $nbLiensExternalShaarlistes += $shaarliste['nb_items'];
                            }                        
                            if($shaarliste['nb_items'] >= 10000) {
                                echo 'Maitre Shaarliste';
                            }
                            elseif($shaarliste['nb_items'] >= 1000) {
                                echo 'Gros Shaarliste';
                            }elseif($shaarliste['nb_items'] >= 500) {
                                echo 'Woooow';
                            }elseif($shaarliste['nb_items'] >= 50) {
                                echo 'Beaucoup';
                            }elseif($shaarliste['nb_items'] >= 20) {
                                echo 'Peu';
                            }else{
                                echo 'Débute';
                            }
                            
                            echo sprintf(' (~%s liens)', $shaarliste['nb_items']);
                        ?>
                        </td>
                        <td><span title="<?php echo implode("\n", $shaarliste['followers']); ?>" ><?php echo $shaarliste['nb_followers'];?></span></td>
                        <td><?php 
                        if($shaarliste['my']){
                            echo 'Interne (My)';
                            $nbMyShaarlistes++;
                        } else {
                            echo 'Externe';
                            $nbExternalShaarlistes ++;
                        }
                        
                        ?></td>
                        <td>
                        <?php
                            if(!is_null($mesAbonnements)) {
                                $idRss =md5($shaarliste['url']);
                                if(!isset($mesAbonnements[$idRss])) {
                                    ?>
                                    <a href="#" onclick="javascript:addAbo(this,'<?php echo md5($shaarliste['url']);?>', 'add');return false;">Suivre</a>
                                    <?php
                                } else {
                                    ?>
                                    <a href="#" onclick="javascript:addAbo(this,'<?php echo md5($shaarliste['url']);?>', 'delete');return false;">Se désabonner</a>
                                    <?php
                                }
                            } else {
                            if($shaarliste['shaarlifr']){
                                    echo 'shaarli.fr';
                                } else {
                                    echo 'global';
                                }
                            }
                        ?>
                        </td>
                    </tr>
            <?php }?>
            </tbody>
        </table>
        <div>Nombre de shaarli externes : <?php echo $nbExternalShaarlistes;?></div>
        <div>Nombre de shaarli internes (My) : <?php echo $nbMyShaarlistes;?></div>
        
        <div>Nombre de liens externes : <?php echo $nbLiensExternalShaarlistes;?></div>
        <div>Nombre de liens internes (My) : <?php echo $nbLiensMyShaarlistes;?></div>        
        <br/>
        <input type="submit" value="Créer un OPML" />
    </form>
</div>


</div>

<script>
    function addAbo(that, id, action) {
        var r = new XMLHttpRequest(); 
        var params = "do="+action+"&id=" + id;
        r.open("POST", "add.php", true); 
        r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        r.onreadystatechange = function () {
            if (r.readyState == 4) {
                if(r.status == 200){
                    if(action == 'add') {
                        that.text = 'Se désabonner';
                        that.innerHTML = 'Se désabonner';
                        that.onclick = function () { addAbo(that, id, 'delete'); return false; };
                    }else {
                        that.text = 'Suivre';
                        that.innerHTML = 'Suivre';
                        that.onclick = function () { addAbo(that, id, 'add'); return false; };
                    }
                    return; 
                }
                else {
                    that.text = '-Erreur-';
                    return; 
                }
            }
        }; 
        r.send(params);
    }
    
</script>
</body>
</html>


