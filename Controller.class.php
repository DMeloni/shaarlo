<?php

ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);

require_once('config.php');

require_once('fct/fct_rss.php');
require_once('fct/fct_session.php');
require_once('fct/fct_url.php');
require_once('fct/fct_mail.php');
require_once('lang/Fr.php');
require_once('lang/En.php');

class Controller
{
    private $langInterface = null;

    public static function renderHead($rssUrl=null)
    {
        ?>
            <head>
                <meta charset="utf-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                <meta name="description" content="La communauté partage ses liens" />

                <meta property="og:title" content="Shaarli.fr" />
                <meta property="og:description" content="shaarli.fr est un nouveau réseau social où les gens partagent leurs liens web" />
                <meta property="og:url" content="https://www.shaarli.fr" />
                <meta property="og:image" content="css/img/logo_shaarlo_og.png" />
                <meta property="keywords" content="Reséau social, shaarli, liens, url, partage, communauté, shaarlistes">
                <title>Shaarli.fr</title>
                <link rel="stylesheet" href="css/foundation.min.css" />
                <link rel="stylesheet" href="css/foundation-overload.css?v=5" />
                <link rel="stylesheet" href="css/style-light.css?v=20" />
                
                <link rel="apple-touch-icon" sizes="57x57" href="img/apple-icon-57x57.png">
                <link rel="apple-touch-icon" sizes="60x60" href="img/apple-icon-60x60.png">
                <link rel="apple-touch-icon" sizes="72x72" href="img/apple-icon-72x72.png">
                <link rel="apple-touch-icon" sizes="76x76" href="img/apple-icon-76x76.png">
                <link rel="apple-touch-icon" sizes="114x114" href="img/apple-icon-114x114.png">
                <link rel="apple-touch-icon" sizes="120x120" href="img/apple-icon-120x120.png">
                <link rel="apple-touch-icon" sizes="144x144" href="img/apple-icon-144x144.png">
                <link rel="apple-touch-icon" sizes="152x152" href="img/apple-icon-152x152.png">
                <link rel="apple-touch-icon" sizes="180x180" href="img/apple-icon-180x180.png">
                <!--<link rel="icon" type="image/png" sizes="192x192"  href="img/android-icon-192x192.png">
                <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
                <link rel="icon" type="image/png" sizes="96x96" href="img/favicon-96x96.png">
                <link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16x16.png">-->
                <link rel="manifest" href="/manifest.json">
                <meta name="msapplication-TileColor" content="#000000">
                <meta name="msapplication-TileImage" content="img/ms-icon-144x144.png">
                <meta name="theme-color" content="#000000">

        <?php
        if (useDotsies()) {
            ?>
                <link rel="stylesheet" href="css/dotsies.css" type="text/css" media="screen"/>
                
                <style>
                *,h1, h2, h3, h4, h5, h6, input,button, .button {font-family: Dotsies;}
                </style>
            <?php
        }

        if (!empty($rssUrl)) {
            ?>
            <link rel="alternate" type="application/rss+xml" href="<?php echo htmlentities($rssUrl); ?>" title="Shaarlo Feed" />
            <?php
        }
        ?>
            </head>

        <?php
    }

    public static function renderElevatorButton() 
    {
        if (useElevator()) {
            ?>
            <div class="row">
                <div class="column text-center large-12">
                    <div class="elevator pointer">
                        <svg class="sweet-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve" height="100px" width="100px">
                            <path d="M70,47.5H30c-1.4,0-2.5,1.1-2.5,2.5v40c0,1.4,1.1,2.5,2.5,2.5h40c1.4,0,2.5-1.1,2.5-2.5V50C72.5,48.6,71.4,47.5,70,47.5z   M47.5,87.5h-5v-25h5V87.5z M57.5,87.5h-5v-25h5V87.5z M67.5,87.5h-5V60c0-1.4-1.1-2.5-2.5-2.5H40c-1.4,0-2.5,1.1-2.5,2.5v27.5h-5  v-35h35V87.5z"/>
                            <path d="M50,42.5c1.4,0,2.5-1.1,2.5-2.5V16l5.7,5.7c0.5,0.5,1.1,0.7,1.8,0.7s1.3-0.2,1.8-0.7c1-1,1-2.6,0-3.5l-10-10  c-1-1-2.6-1-3.5,0l-10,10c-1,1-1,2.6,0,3.5c1,1,2.6,1,3.5,0l5.7-5.7v24C47.5,41.4,48.6,42.5,50,42.5z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <script src="js/vendor/elevator.min.js"></script>
            <script>
                    // Simple elevator usage.
                    var elementButton = document.querySelector('.elevator');
                    var elevator = new Elevator({
                    element: elementButton,
                    mainAudio: './music/elevator-music.mp3', // Music from http://www.bensound.com/
                    endAudio:  './music/ding.mp3'
                    });
            </script>
            <?php
        }
    }

    public function renderListeShaarlistes($params) 
    {
    ?>
    
        <div class="row">
            <div class="column large-3">
                <?php if (empty($params['creation'])) { ?>
                <a href="index.php" id="a-voir-river" class="button success hidden">Voir la river</a>
                <?php } ?>
            </div>
            
            <div class="column large-9 text-right">
                <input id="button-tous" type="button" class="button " value="Tout cocher" />
                <?php if (!empty($params['abonnements'])) { ?>
                    <input id="button-personne" type="button" class="button " value="Tout décocher" />
                <?php } ?>
                <input type="hidden" value="null" name="null" />
            </div>
        </div>
        <?php
        $dateDuJour = new DateTime();
        $dateDuJour->modify('-2 days');
        $dateLastWeek = $dateDuJour->format('YmdHis');

        foreach ($params['infoAboutAllDecodedChunked'] as $shaarlistes) {
            ?><div class="row" data-equalizer><?php
            foreach ($shaarlistes as $shaarliste) {
                $faviconIcoPath = sprintf('img/favicon/%s.ico', $shaarliste['id']);
                if(!is_file($faviconIcoPath)) {
                   $faviconIcoPath = sprintf('img/favicon/%s.ico', '7280d5cfd1c82734436f0e19cb14a913'); 
                }
                ?>
            <div class="column large-3"  >
                <div class="panel shaarliste-selection <?php if(in_array($shaarliste['id'], $params['abonnements'])) { echo 'selected'; }?>" id="shaarliste-selection-<?php echo $shaarliste['id'];?>" data-equalizer-watch data-shaarliste-id="<?php echo $shaarliste['id'];?>">
                    <?php if (displayImages()) { ?>
                    <div class="row text-center">
                        <img class="entete-avatar" width="64" height="64" src="<?php eh($faviconIcoPath); ?>" />
                    </div>
                    <?php } ?>
                    <div class="row" >
                        <input style="display:none;" <?php if(in_array($shaarliste['id'], $params['abonnements'])) { echo 'checked="checked"'; }?> type="checkbox" name="shaarlistes[]" value="<?php echo $shaarliste['id'];?>" id="<?php echo $shaarliste['id'];?>" class="checkbox-shaarliste" />
                        <div class="column large-12 text-center">
                            <div class="row">
                                <div class="column large-12">
                                    <?php 
                                    if ($shaarliste['createdateiso'] > $dateLastWeek) {
                                        ?><span class="button alert tiny">NEW</span><?php
                                    }
                                    ?> 
                                </div>
                            </div>
                            <p>
                            <?php
                                if ('1' == $shaarliste['is_dead'] ) {
                                    ?>
                                    <div class="text-center"><?php echo $shaarliste['title'];?></div>
                                    <div class="text-center">(_~,,~_)</div>
                            <?php } else { ?>
                                    <a onclick="event.stopPropagation();" target="_blank" href="<?php echo $shaarliste['link'];?>"><?php echo $shaarliste['title'];?></a>
                            <?php } ?>
                                <br/><span class="tiny"><?php echo $shaarliste['nb_items'];?> liens sur le site</span>
                                <br/><span class="tiny"><?php echo $shaarliste['nb_followers'];?> abonnés</span>
                            </p>

                        </div>
                    </div>
                </div>
            </div>
            <?php
            }
            ?>
            </div>
        <?php
        }
        ?>
    <?php
    }

    /*
     * Méthode permettant d'htmlentiter tous les paramètres d'un render
     * 
     * @param array $params
     * @param array $clefsIgnorees : les éléments à ignorer
     */
    public function htmlspecialchars($params, $clefsIgnorees = array())
    {
        foreach ($params as $k => $param) {
            if (in_array($k, $clefsIgnorees)) {
                continue;
            }

            if (!is_array($param)) {
                $params[$k] = htmlspecialchars($param);
            } else {
                $params[$k] = $this->htmlspecialchars($param);
            }
        }
        
        return $params;
    }
    
    /**
     * Indique quelle est la langue à utiliser
     * 
     */
    public function setLocale($locale = 'fr')
    {
        switch ($locale) {
            default:
                $langInterface = new Fr();
            break;
            case 'en': 
                $langInterface = new En();
            break;
        }
        
        $this->setLangInterface($langInterface);
    }
    
    /**
     * Set l'interface de langue à utiliser pour l'affichage
     * 
     * @param LangInterface $langInterface
     */
    protected function setLangInterface($langInterface)
    {
        $this->langInterface = $langInterface;
    }

    /**
     * Retourne l'interface de langue à utiliser pour l'affichage
     * 
     * @return LangInterface $langInterface
     */
    protected function getLangInterface()
    {
        if (is_null($this->langInterface)) {
            $this->setLocale('fr');
        }

        return $this->langInterface;
    }

    /*
     * Récupère la variable $_POST
     * de manière saine
     * 
     * @param string $nom : le nom du paramètre
     * 
     * @return mixed : valeur du paramètre
     */
    public function post($nom)
    {
        if (isset($_POST[$nom])) {
            return $_POST[$nom];
        }
        
        return null;
    }

    /*
     * Récupère la variable $_GET
     * de manière saine
     * 
     * @param string $nom : le nom du paramètre
     * 
     * @return mixed : valeur du paramètre
     */
    public function get($nom)
    {
        if (isset($_GET[$nom])) {
            return $_GET[$nom];
        }
        
        return null;
    }
    
    public static function renderScript($params = array())
    {
        ?>
        <script src="js/jquery-modernizr-foundation.min.js"></script>
        <?php
    }
    
    /** 
     * Ajoute des br si menu fixe
     */
    public function addBr() {
        if (isMenuLocked()) {
            echo '<br/><br/><br/>';
                
        }
    }
    
    /**
     * Affiche le message demandé dans la bonne langue
     * 
     * @param $code : 'profil_mot_de_passe'
     */
    public function t($code) {
        $langInterface = $this->getLangInterface();
        echo $langInterface->trans($code);
    }

    public static function renderMenu($titre = 'Shaarli.fr', $rssUrl = '')
    {

        $class = 'menu';
        $onclick = '';
        if (isMenuLocked()) {
                $class = 'menu position-fixed';
                $onclick = ' onclick="scroll(0, 0);"';
        }

        // Liens vers page My
        $myHref = 'my.php';
        if (isset($_SESSION['username'])) {
            $myHref = sprintf("https://my.shaarli.fr/%s/", htmlentities($_SESSION['username']));
        }

        ?>
        <div id="menu-top" class="<?php echo $class.$onclick; ?>">
            <h1 class="show-for-medium-up">
                <a href="/"><img class="logo hidden-on-smartphone" src="img/logo.png" height="40" width="36" /></a>
                <a href="./index.php"><?php echo $titre ?></a>
                
                <?php if (useTipeee()) {?>
                <a style="display:inline-block" target="_blank" href="https://www.tipeee.com/shaarlo">
                    <img width="50" src="img/tipeee.png" />
                </a> 
                <span style="color: #BBB;font-size: 8px;cursor:help;" title="Wallet bitcoin">1EDwkGM6gCBnNyfvU3h7T98m6BwGjQsGfg</span>
                <?php } ?>
            </h1>
            <ul class="show-for-medium-up" >
                <li><a href="index.php">River</a></li>
                <?php
                if (getUtilisateurId() !== '') {
                ?>
                <li><a href="dashboard.php">Profil</a></li>
                <?php
                }
                ?>
                <li><a href="index.php?sortBy=rand&amp;from=2000-09-16">Aléatoire</a></li>
                <li><a href="<?php echo htmlentities($myHref); ?>">My</a></li>
                <li><a href="opml.php">OPML</a></li>
                <li><a href="about.php">A propos</a></li>

                <?php
                if (displayRssButton() && !empty($rssUrl)) {
                    ?>
                    <li><a href="<?php echo htmlentities($rssUrl); ?>"><img src="img/rss_iconZ.png" style="background:orange;" height="28" width="28" /></a></li>
                    <?php
                }
                ?>
                    <li><a href="./index.php?do=logout"><img src="img/logout_icon.png" height="28" width="28" /></a></li>
            </ul>
            <ul class="show-for-small-only" >
                <li><a href="index.php">River</a></li>
                <?php
                if (getUtilisateurId() !== '') {
                ?>
                <li><a href="dashboard.php">Profil</a></li>
                <?php
                }
                ?>
                <li><a href="index.php?sortBy=rand&amp;from=2000-09-16">Aléa</a></li>
                <li><a href="<?php echo htmlentities($myHref); ?>">My</a></li>
                <?php
                if (displayRssButton() && !empty($rssUrl)) {
                    ?>
                    <li><a style="vertical-align: baseline;" href="<?php echo htmlentities($rssUrl); ?>"><img src="img/rss_iconZ.png" style="background:orange;" height="14" width="14" /></a></li>
                    <?php
                }
                ?>
                    <li><a style="vertical-align: baseline;" href="./index.php?do=logout"><img src="img/logout_icon.png" height="14" width="14" /></a></li>
            </ul>
        </div>
        <?php
        if (isMenuLocked()) {
                ?>
                <br/><br/><br/>
                <?php
        }

        ?>
        <script>

        function synchroShaarli(articleId, ssiTextePresent) {
            var r = new XMLHttpRequest(); 
            var params = "";
            r.open("POST", "synchro_shaarli.php", true); 
            r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            r.onreadystatechange = function () {
                if (r.readyState == 4) {
                    if(r.status == 200){
                        if (typeof(articleId) != "undefined") {
                            refreshArticle(articleId, ssiTextePresent);
                        }
                        return ;
                    }
                    else {
                        return; 
                    }
                }
            }; 
            r.send(params);
        }

        function synchroShaarliLastArticle() {
            var r = new XMLHttpRequest(); 
            var params = "";
            r.open("POST", "synchro_shaarli.php", true); 
            r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            r.onreadystatechange = function () {
                if (r.readyState == 4) {
                    if(r.status == 200){
                        refreshLastArticle();
                        return ;
                    }
                    else {
                        return; 
                    }
                }
            }; 
            r.send(params);
        }
        
        function addOption(that, action, value) {
            var r = new XMLHttpRequest(); 
            var params = "do="+action + "&value="+value+ "&state="+value;
            r.open("POST", "add.php", true); 
            r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            r.onreadystatechange = function () {
                if (r.readyState == 4) {
                    if(r.status == 200){
                        that.attr('data-waiting', '');
                        return; 
                    }
                    else {
                        return; 
                    }
                }
            }; 
            r.send(params);
        }

        </script>
        <div class="clear"></div>
        <?php
    }
}
