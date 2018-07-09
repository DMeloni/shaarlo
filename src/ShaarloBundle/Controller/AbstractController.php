<?php

namespace ShaarloBundle\Controller;

use ShaarloBundle\Lang\EnLang;
use ShaarloBundle\Lang\FrLang;
use ShaarloBundle\Lang\LangInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractController extends Controller
{
    private $langInterface = null;

    public function getGlobalTemplateParameters()
    {
        $userOptionsUtils = $this->get('shaarlo.user_options_utils');


        $hrefTopJour = null;
        $hrefTopHier = null;
        $hrefTopSemaine = null;
        $hrefTopMois = null;
        if ($userOptionsUtils->useTopButtons()) {
            $dateDuJour = new \DateTime();
            $dateTopHier = new \DateTime('-1 day');
            $dateMoinsUneSemaine = new \DateTime('-7 days');
            $dateMoinsUnMois = new \DateTime(date('Ym00'));

            $hrefTopJour = sprintf(
                    '?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc',
                    $dateDuJour->format('Ymd'),
                    $dateDuJour->format('Ymd')
            );
            $hrefTopHier = sprintf(
                    '?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc',
                    $dateTopHier->format('Ymd'),
                    $dateTopHier->format('Ymd')
            );
            $hrefTopSemaine = sprintf(
                    '?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc',
                    $dateMoinsUneSemaine->format('Ymd'),
                    $dateDuJour->format('Ymd')
            );
            $hrefTopMois = sprintf(
                    '?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc',
                    $dateMoinsUnMois->format('Ymd'),
                    $dateDuJour->format('Ymd')
            );
        }

        $shaarliste = null;
        return [
            'domain' => $this->getParameter('domain'),
            'use_dotsies' => $userOptionsUtils->useDotsies(),
            'shaarli_url' => $userOptionsUtils->getShaarliUrlOk(),
            'rss_url' => null,
            'display_elevator' => $userOptionsUtils->useElevator(),
            'menu_locked' => $userOptionsUtils->isMenuLocked(),
            'is_shaarliste' => $userOptionsUtils->isShaarliste(),
            'user_id' => $userOptionsUtils->getUtilisateurId(),
            'use_top_buttons' => $userOptionsUtils->useTopButtons(),
            'is_serieux' => $userOptionsUtils->isSerieux(),
            'non_en_attente_de_moderation' => !$userOptionsUtils->isEnAttenteDeModeration(),
            'top_day_href' => $hrefTopJour,
            'top_yesterday_href' => $hrefTopHier,
            'top_week_href' => $hrefTopSemaine,
            'top_month_href' => $hrefTopMois,
        ];
    }

    public function renderHead($rssUrl=null)
    {
    }

    public static function renderElevatorButton()
    {
        if (useElevator()) {
            ?>

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
        $dateDuJour = new \DateTime();
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
                            <?php
                                } elseif ('1' == $shaarliste['erreur'] ) {
                                    ?>
                                    <div class="text-center"><?php echo $shaarliste['title'];?></div>
                                    <div class="text-center" title="Pb : <?php echo $shaarliste['erreur_message'];?>">(_-_-_)</div>
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
     * Set the locale.
     *
     * @param string $locale
     */
    public function setLocale($locale = 'fr')
    {
        switch ($locale) {
            default:
                $langInterface = new FrLang();
            break;
            case 'en':
                $langInterface = new EnLang();
            break;
        }

        $this->setLangInterface($langInterface);
    }

    /**
     * Set l'interface de langue à utiliser pour l'affichage
     *
     * @param LangInterface $langInterface
     */
    protected function setLangInterface(LangInterface $langInterface)
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


    public static function renderScript($params = array())
    {
        ?>

        <?php
    }

    /**
     * Ajoute des br si menu fixe
     */
    public function addBr() {
        $userOptionsUtils = $this->container->get('shaarlo.user_options_utils');

        if ($userOptionsUtils->isMenuLocked()) {
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

    /**
     * Render du super menu
     *
     */
    public function renderMenu($titre = 'Shaarlo', $rssUrl = '')
    {
        $userOptionsUtils = $this->container->get('shaarlo.user_options_utils');
        $urlUtils = $this->container->get('shaarlo.url_utils');

        $class = '';
        global $SHAARLO_DOMAIN, $API_TRANSFER_PROTOCOL;
        if ($userOptionsUtils->isMenuLocked()) {
                $class = 'top-bar-fixed';
        }
        ?>

        <nav class="top-bar <?php echo $class; ?>" data-options="mobile_show_parent_link: false;back_text:Retour;" data-topbar role="navigation">
            <!--<a href="/"><img class="logo hidden-on-smartphone" src="img/logo.png" height="40" width="36" /></a>-->
          <ul class="title-area">
            <li class="name">
              <h1><a href="index.php">Shaarlo</a></h1>
            </li>
             <!-- Remove the class "menu-icon" to get rid of menu icon. Take out "Menu" to just have icon alone -->
            <li class="toggle-topbar menu-icon"><a href="#"><span>Menu</span></a></li>
          </ul>

          <section class="top-bar-section">
            <!-- Right Nav Section -->
            <ul class="right">
                <li><a href="index.php">River</a></li>
                <?php
                if ($userOptionsUtils->isShaarliste()) {
                    ?><li><a href="<?php $urlUtils->eh($userOptionsUtils->getShaarliUrlOk()); ?>">Shaarli</a></li><?php
                }
                ?>
                <?php
                if ($userOptionsUtils->getUtilisateurId() !== '') {
                ?>
                <li><a href="dashboard.php">Profil</a></li>

                <?php
                }
                ?>
                <li><a href="index.php?sortBy=rand&amp;from=2000-09-16">Aléatoire</a></li>

                <?php
                if ($userOptionsUtils->useTopButtons()) {
                    $dateDuJour = new \DateTime();
                    $dateTopHier = new \DateTime('-1 day');
                    $dateMoinsUneSemaine = new \DateTime('-7 days');
                    $dateMoinsUnMois = new \DateTime(date('Ym00'));

                    $hrefTopJour = sprintf('?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc', $dateDuJour->format('Ymd'), $dateDuJour->format('Ymd'));
                    $hrefTopHier = sprintf('?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc', $dateTopHier->format('Ymd'), $dateTopHier->format('Ymd'));
                    $hrefTopSemaine = sprintf('?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc', $dateMoinsUneSemaine->format('Ymd'), $dateDuJour->format('Ymd'));
                    $hrefTopMois = sprintf('?q=&pop=0&limit=50&from=%s&to=%s&sortBy=pop&sort=desc', $dateMoinsUnMois->format('Ymd'), $dateDuJour->format('Ymd'));

                ?>
                    <li class="has-dropdown">
                        <a href="#">Voir les Tops</a>
                        <ul class="dropdown">
                            <li><a href="index.php<?php $urlUtils->eh($hrefTopJour); ?>">Top du jour</a></li>
                            <li><a href="index.php<?php $urlUtils->eh($hrefTopHier); ?>">Top d'hier</a></li>
                            <li><a href="index.php<?php $urlUtils->eh($hrefTopSemaine); ?>">Top des 7 derniers jours</a></li>
                            <li><a href="index.php<?php $urlUtils->eh($hrefTopMois); ?>">Top du mois</a></li>
                        </ul>
                    </li>
                <?php
                }
                ?>

                <li class="has-dropdown">
                    <a href="#">Plus</a>
                    <ul class="dropdown">
                        <li><a href="https://www.tipeee.com/shaarlo">Soutenir le projet</a></li>
                        <li><a href="opml.php">Télécharger l'OPML</a></li>
                        <?php if ($rssUrl) { ?>
                            <li><a href="<?php $urlUtils->eh($rssUrl); ?>">Flux RSS</a></li>
                        <?php } ?>
                        <li><a href="about.php">A propos</a></li>
                    </ul>
                </li>
                <li><a href="./index.php?do=logout">Déconnexion</a></li>
            </ul>

            <!-- Left Nav Section -->
            <ul class="left">

            </ul>
          </section>
        </nav>
        <?php
        if ($userOptionsUtils->isMenuLocked()) {
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
            r.open("POST", "add", true);
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

        function lienAddAbo(that, id, action) {
            var r = new XMLHttpRequest();
            var params = "do="+action+"&id=" + id;
            r.open("POST", "add", true);
            r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            r.onreadystatechange = function () {
                if (r.readyState == 4) {
                    if(r.status == 200){
                        if(action == 'add') {
                            that.text = 'Se désabonner';
                            that.innerHTML = 'Se désabonner';
                            that.onclick = function () { lienAddAbo(that, id, 'delete'); return false; };
                        }else {
                            that.text = 'Suivre';
                            that.innerHTML = 'Suivre';
                            that.onclicrenderMenuk = function () { lienAddAbo(that, id, 'add'); return false; };
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
        <?php
    }

    public function renderOldMenu($titre = 'Shaarlo.fr', $rssUrl = '')
    {
        $urlUtils = $this->container->get('shaarlo.url_utils');
        $userOptionsUtils = $this->container->get('shaarlo.user_options_utils');

        $class = 'menu';
        $onclick = '';
        if (isMenuLocked()) {
                $class = 'menu position-fixed';
                $onclick = ' onclick="scroll(0, 0);"';
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
                if (isShaarliste()) {
                    ?><li><a href="<?php $urlUtils->eh($userOptionsUtils->getShaarliUrlOk()); ?>">Shaarli</a></li><?php
                } else {
                    if (getUtilisateurId() !== '') {
                        ?><li><a href="my.php">My</a></li><?php
                    }
                }
                ?>
                <?php
                if (getUtilisateurId() !== '') {
                ?>
                <li><a href="dashboard.php">Profil</a></li>
                <?php
                }
                ?>

                <li><a href="index.php?sortBy=rand&amp;from=2000-09-16">Aléatoire</a></li>
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
                if (isShaarliste()) {
                    ?><li><a href="<?php eh($userOptionsUtils->getShaarliUrlOk()); ?>">Shaarli</a></li><?php
                } else {
                    ?><li><a href="my.php">My</a></li><?php
                }
                ?>
                <?php
                if (getUtilisateurId() !== '') {
                ?>
                <li><a href="dashboard.php">Profil</a></li>
                <?php
                }
                ?>

                <li><a href="index.php?sortBy=rand&amp;from=2000-09-16">Aléa</a></li>

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
            r.open("POST", "add", true);
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
