<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">

    <xsl:output method="html" encoding="UTF-8"
        omit-xml-declaration="yes" indent="no"/>

    <xsl:param name="nb_sessions" />
    <xsl:param name="is_secure" />
    <xsl:param name="wot" />
    <xsl:param name="youtube" />
    <xsl:param name="my_shaarli" />
    <xsl:param name="my_respawn" />
    <xsl:param name="searchTerm" />
    <xsl:param name="mod_content_top" />
    <xsl:param name="mod_content_bottom" />
    <xsl:param name="rss_url" />
    <xsl:param name="next_previous" />
    <xsl:param name="date_demain" />
    <xsl:param name="date_hier" />
    <xsl:param name="when" />
    <xsl:param name="sort" />
    <xsl:param name="sortBy" />
    <xsl:param name="date_from" />
    <xsl:param name="date_to" />
    <xsl:param name="isToday" />

    <xsl:param name="max_date_to" />
    <xsl:param name="date_actual" />
    <xsl:param name="filtre_popularite" />
    <xsl:param name="limit" />
    <xsl:param name="min_limit" />
    <xsl:param name="max_limit" />
    
    <xsl:param name="no_description" />
    <xsl:param name="filter_on" />
    <xsl:param name="dotsies" />
    <xsl:param name="username" />
    <xsl:param name="token" />
    <xsl:param name="afficher_messagerie" />
    <xsl:param name="extended" />
    <xsl:param name="menu_locked" />
    
    
    <xsl:template match="/">
        <html lang="fr">
            <head>
                <title>Shaarlo - shaarli.fr</title>
                <meta charset="utf-8"/>
                <meta name="description" content="Site d'actualité alternatif par la communauté via shaarli." />
                <meta property="og:description" content="Site d'actualité alternatif par la communauté via shaarli." />
                <meta name="author" content="" />
                <meta name="viewport" content="width=device-width, user-scalable=yes" />
                <link rel="apple-touch-icon" href="favicon.png" />
                <meta name="apple-mobile-web-app-capable" content="yes" />
                <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
                <link rel="shortcut icon" href="favicon.ico" />
                <link rel="stylesheet" href="css/style.css?v=13" type="text/css" media="screen"/>
                
                <xsl:if test="$dotsies = 'yes'">
                    <link rel="stylesheet" href="css/dotsies.css" type="text/css" media="screen"/>
                    <style>
                    * {font-family: Dotsies;}
                    </style>
                </xsl:if>
                
                <xsl:if test="not($username)">
                    <link rel="alternate" type="application/rss+xml" href="{$rss_url}?do=rss" title="Shaarlo Feed" />
                </xsl:if>
                <xsl:if test="$username">
                    <link rel="alternate" type="application/rss+xml" href="{$rss_url}?do=rss&amp;u={$username}" title="Shaarlo Feed" />
                </xsl:if>
                </head>
            <body>
                <div id="menu-top">
                    <xsl:if test="$menu_locked">
                        <xsl:attribute name="class">menu position-fixed</xsl:attribute>
                        <xsl:attribute name="onclick">scroll(0, 0);</xsl:attribute>
                    </xsl:if>
                    <xsl:if test="not($menu_locked)">
                        <xsl:attribute name="class">menu</xsl:attribute>
                    </xsl:if>
                    <h1>
                        <a href="/"><img class="logo hidden-on-smartphone" src="img/logo.png" height="40" width="36" /></a>
                        <a href="./index.php"><xsl:value-of select="/rss/channel/title"/></a>
                    </h1>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="index.php?sortBy=rand&amp;from=2000-09-16">Aléatoire</a></li>
                        <li><a href="my.php">My</a></li>
                        <li><a href="opml.php?mod=opml">OPML</a></li>

                        <!--<li><a href="https://nexen.mkdir.fr/shaarli-river/" id="river">Shaarli River</a></li>-->
                        
                        <xsl:if test="$menu_locked">
                            <li><span onclick="open_menu(this, 'menu-top');" class="pointer display-inline-block-text-bottom icon-lock" /></li>
                        </xsl:if>
                        <xsl:if test="not($menu_locked)">
                            <li><span onclick="lock_menu(this, 'menu-top');" class="pointer display-inline-block-text-bottom icon-open" /></li>
                        </xsl:if>
                    </ul>
                </div>
                <div class="clear"/>
                <div id="header">
                    <xsl:if test="$menu_locked">
                        <xsl:attribute name="class">add-padding-top-8</xsl:attribute>
                    </xsl:if>
                    <form method="GET" action="index.php" id="searchform">
                        <xsl:if test="$filter_on = 'yes'">
                            <xsl:attribute name="class">hidden</xsl:attribute>
                        </xsl:if>
                        <input id="searchbar" type="text" name="q" placeholder="Rechercher un article" value="{$searchTerm}"/>
                        <input name="from" type="hidden" value="20130000"></input>
                        <input name="to" type="hidden" value="90130000"></input>
                    </form>
                    <xsl:if test="not($afficher_messagerie)">
                        <xsl:if test="$filter_on = ''">
                            <div class="options-extend">
                                <button onclick="option_extend(this)">(+)</button>
                            </div>
                        </xsl:if>
                        <div class="pagination">
                            <div id="bloc-filtre">
                                <xsl:if test="$filter_on = ''">
                                    <xsl:attribute name="class">hidden</xsl:attribute>
                                </xsl:if>
                                <form action="index.php" method="GET">
                                    <fieldset id="fielset-filtrer">
                                        <legend>Filtrer les articles :</legend>
                                        <label for="sortBy">Mot clef</label>
                                        <input type="text" name="q" placeholder="shaarli,linux,..." value="{$searchTerm}"/>
                                        <label for="from">Du</label>
                                        <input id="from" name="from" type="date" value="{$date_from}"></input>
                                        <label for="to">Au</label>
                                        <input id="to" name="to" type="date" value="{$date_to}" max="{$max_date_to}"></input>
                                        <label for="pop">Popularité</label>
                                        <input id="pop" name="pop" type="number" value="{$filtre_popularite}" min="0"></input>
                                        <label for="limit">Limite</label>
                                        <input id="limit" name="limit" type="number" min="0" max="{$max_limit}">
                                            <xsl:if test="$limit !=''">
                                                <xsl:attribute name="value"><xsl:value-of select="$limit" /></xsl:attribute>
                                            </xsl:if>
                                            <xsl:if test="$limit =''">
                                                <xsl:attribute name="value"><xsl:value-of select="$min_limit" /></xsl:attribute>
                                            </xsl:if>
                                        </input>
                                    </fieldset>
                                    <fieldset>
                                        <legend>Options de tri :</legend>
                                        <label for="sortBy">Trier par</label>
                                        <select id="sortBy" name="sortBy">
                                            <option value="date">
                                                <xsl:if test="$sortBy='date'">
                                                    <xsl:attribute name="selected">
                                                        selected
                                                    </xsl:attribute>
                                                </xsl:if>
                                                Date</option>
                                            <option value="pop">
                                                <xsl:if test="$sortBy='pop'">
                                                    <xsl:attribute name="selected">
                                                        selected
                                                    </xsl:attribute>
                                                </xsl:if>
                                                Popularité</option>
                                        </select>
                                        <label for="sort">Par ordre</label>
                                        <select name="sort">
                                            <option value="desc">
                                                <xsl:if test="$sort='desc'">
                                                    <xsl:attribute name="selected">
                                                        selected
                                                    </xsl:attribute>
                                                </xsl:if>
                                                Décroissant</option>
                                            <option value="asc">
                                                <xsl:if test="$sort='asc'">
                                                    <xsl:attribute name="selected">
                                                        selected
                                                    </xsl:attribute>
                                                </xsl:if>
                                                Croissant</option>
                                        </select>
                                    </fieldset>
                                    <input id="valider" type="submit" value="Valider" />
                                </form>
                            </div>
                            <div class="clear"/>
                            <div class="liens">
                                <xsl:if test="$date_hier">
                                    <a href="?from={$date_hier}000000&amp;to={$date_hier}235959">Jour précédent</a>
                                </xsl:if>
                                <xsl:if test="$date_demain">
                                    <a href="?from={$date_demain}000000&amp;to={$date_demain}235959"> / Jour suivant</a>
                                </xsl:if>
                            </div>
                        </div>
                    </xsl:if>
                    <div class="clear"/>
                </div>
                
                <div id="dashboard_icon" >
                    <xsl:if test="$username">
                        <a href="#" class="connected" onclick="showDashboard()">@</a>
                    </xsl:if>
                    <xsl:if test="not($username)">
                        <a href="#" onclick="showDashboard()">@</a>
                    </xsl:if>
                </div>
                <div id="dashboard">
                <xsl:if test="$username">
                        <div>
                            <h3>
                                <a href="?u={$username}">@<xsl:value-of select="$pseudo" disable-output-escaping="yes"/></a>
                            </h3>
                         </div>
                         <div>    
                            <ul>
                                <li><a href="{$my_shaarli}/">Mon shaarli</a></li>
                                <li><a href="?u=499f443cfd5481cc0a29db210ca208a5">Flux de @Shaarlo</a></li>
                            </ul>
                         </div>
                        <div>
                            <ul>
                                <li><a href="?do=logout">Se déconnecter</a></li>
                            </ul>
                        </div>
                </xsl:if>
                <xsl:if test="not($username)">
                    <h4>Connecter mon shaarli</h4>
 
                    <form  method="POST" action="?" name="loadform" id="loadform">
                        <input id="shaarli" name="shaarli" tabindex="1" type="text"  value="" placeholder="http://domain.ext/shaarli"/>
                        <br/>
                        <input type="submit" value="Charger ma conf"/>
                    </form>
                </xsl:if>
                    <div>
                        <small><a href="#" onclick="hideDashboard()">x Fermer cette vilaine fenêtre</a></small>
                    </div>
                </div>
                <xsl:if test="count(/rss/channel/best) != 0">
                    <div id="panel-best" class="panel panel-best add-box-shadow">
                        <xsl:apply-templates select="/rss/channel/best"/>
                    </div>
                </xsl:if>
                
                
                <xsl:if test="$afficher_messagerie">
                    <xsl:if test="count(/rss/channel/item) != 0">
                        <div class="panel messagerie">
                            <xsl:apply-templates select="/rss/channel/item" mode="messagerie" />
                        </div>
                    </xsl:if>
                    <div class="clear"/>
                </xsl:if>
                <xsl:if test="not($afficher_messagerie)">
                    <div id="content">
                        <xsl:value-of select="$mod_content_top" disable-output-escaping="yes"/>
                        <xsl:if test="count(/rss/channel/item) = 0">
                            <div class="article add-box-shadow">
                                <h2 class=" article-title toptopic">Seul au monde</h2>
                                Pas de nouveaux shaarliens :(
                            </div>
                        </xsl:if>
                        <xsl:if test="count(/rss/channel/item) != 0">
                            <xsl:apply-templates select="/rss/channel/item" mode="standard"/>
                        </xsl:if>
                        <xsl:value-of select="$mod_content_bottom" disable-output-escaping="yes"/>                  
                    </div>
                    <div class="clear"/>
                </xsl:if>
                <div id="footer">
                    <xsl:if test="not($afficher_messagerie)"> 
                        <div class="pagination">
                            <div class="liens">
                                <xsl:if test="$date_hier">
                                    <a href="?from={$date_hier}000000&amp;to={$date_hier}235959">Jour précédent</a>
                                </xsl:if>
                                <xsl:if test="$date_demain">
                                    <a href="?from={$date_demain}000000&amp;to={$date_demain}235959"> / Jour suivant</a>
                                </xsl:if>
                            </div>
                            <div class="clear"/>
                        </div>
                    </xsl:if>
                    <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments - <a href="https://github.com/DMeloni/shaarlo">sources on github</a>. Ce site utilise vos cookies...</p></div>
                
                <xsl:if test="$is_secure = 'no' and $wot = 'yes'">
                    <script type="text/javascript">
                    var wot_rating_options = {
                    selector: ".wot"
                    };
                    </script>               
                    <script type="text/javascript" src="http://api.mywot.com/widgets/ratings.js"></script>
                </xsl:if>

                <script>
                    function getMy(){
                        document.forms["loginform"].action = "https://www.shaarli.fr/my/" + document.getElementById('pseudo').value + "/";
                        document.forms["loginform"].submit();
                    }       
                    function showDashboard(){
                        document.getElementById('content').className = 'dashboarded';
                        addClass(document.getElementById('panel-best'), 'dashboarded');
                        document.getElementById("dashboard_icon").style.display="none";
                        document.getElementById("dashboard").style.display="block";
                    }
                    function hideDashboard(){
                        document.getElementById('content').className = '';
                        removeClass(document.getElementById('panel-best'), 'dashboarded');
                        document.getElementById("dashboard_icon").style.display="block";
                        document.getElementById("dashboard").style.display="none";
                    }                    
                    function extend(him) {
                        removeClass(him.parentNode.parentNode.childNodes[2], 'extended');
                        him.innerHTML = '-';
                        him.onclick =  function(){ shorten(him); } ;
                    }
                    function shorten(him) {
                        addClass(him.parentNode.parentNode.childNodes[2], 'extended');
                        him.innerHTML = '+';
                        him.onclick =  function(){ extend(him); } ;
                    }
                    function option_extend(him) {
                        removeClass(document.getElementById('bloc-filtre'), 'hidden');
                        addClass(document.getElementById('searchform'), 'hidden');
                        addClass(him, 'hidden');
                    }
                    function removeClass(el, name)
                    {
                        if (hasClass(el, name)) {
                            el.className=el.className.replace(new RegExp('(\\s|^)'+name+'(\\s|$)'),' ').replace(/^\s+|\s+$/g, '');
                        }
                    }
                    function hasClass(el, name) {
                        return new RegExp('(\\s|^)'+name+'(\\s|$)').test(el.className);
                    }

                    function addClass(el, name)
                    {
                        if (!hasClass(el, name)) { el.className += (el.className ? ' ' : '') +name; }
                    }

                    function getChar(event) {
                        if (event.which == null) {
                            return event.keyCode;
                        } else if (event.which!=0 &amp;&amp; event.charCode!=0) {
                            return event.which;
                        } else {
                            return null;
                        }
                    }

                    function ireadit(him, id) 
                    {
                        var r = new XMLHttpRequest(); 
                        var params = "do=ireadit&amp;id=" + id;
                        r.open("POST", "add.php", true); 
                        r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                        r.onreadystatechange = function () {
                            if (r.readyState == 4) {
                                if(r.status == 200){
                                    var blocArticle = him.parentNode.parentNode;
                                    removeClass(blocArticle, 'not-read');
                                    addClass(blocArticle, 'read');
                                }
                                return true; 
                            }
                        };
                        r.send(params);
                    }
                    
                    function save_lock(state) 
                    {
                        var r = new XMLHttpRequest(); 
                        var params = "do=lock&amp;state=" + state;
                        r.open("POST", "add.php", true); 
                        r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                        r.onreadystatechange = function () {
                            if (r.readyState == 4) {
                                return true; 
                            }
                        };
                        r.send(params);
                    }
                    
                    function lock_menu(him, elementId)
                    {
                        addClass(document.getElementById('header'), 'add-padding-top-8');
                        addClass(document.getElementById(elementId), 'position-fixed');
                        addClass(him, 'icon-lock');
                        removeClass(him, 'icon-open');
                        document.getElementById(elementId).onclick = function () {scroll(0, 0);};
                        him.onclick = function () {open_menu(him, elementId);};
                        save_lock('lock');
                    }

                    function open_menu(him, elementId)
                    {
                        removeClass(document.getElementById('header'), 'add-padding-top-8');
                        removeClass(document.getElementById(elementId), 'position-fixed');
                        removeClass(him, 'icon-lock');
                        addClass(him, 'icon-open');
                        document.getElementById(elementId).onclick = function () {return true;};
                        him.onclick = function () {lock_menu(him, elementId);};
                        save_lock('open');
                    }   
                    
                    document.onkeypress = function(event) {
                        var char = getChar(event);
                        if(char == '339') {
                            var els = document.getElementsByClassName("button-extend");
                            Array.prototype.forEach.call(els, function(el) {
                                extend(el);
                            });
                        }
                        return true;
                    }
                    
 

                </script>
                <xsl:if test="$username">
                    <script>
                        function addAbo(that, id, action) {
                            var r = new XMLHttpRequest(); 
                            var params = "do="+action+"&amp;id=" + id;
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
                        
                        function validerLien(that, id, action) {
                            var r = new XMLHttpRequest(); 
                            var params = "do="+action+"&amp;id=" + id;
                            r.open("POST", "valide.php", true); 
                            r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                            r.onreadystatechange = function () {
                                if (r.readyState == 4) {
                                    if(r.status == 200){
                                        if(action == 'valider') {
                                            that.text = 'Bloquer';
                                            that.innerHTML = 'Bloquer';
                                            that.onclick = function () { validerLien(that, id, 'bloquerLien'); return false; };
                                        }else {
                                            that.text = 'Valider';
                                            that.innerHTML = 'Valider';
                                            that.onclick = function () { validerLien(that, id, 'validerLien'); return false; };
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
                </xsl:if>
            </body>
        </html>
    </xsl:template>
    
    <xsl:template match="best">
        <xsl:if test="not(url_image = '')">
            <div class="article-image">
                <a title="Go to original place" href="{link}"><img src="{url_image}" width="450"/></a>
            </div>
        </xsl:if>
        <div class="article">
            <div class="article-mini-titre">
                <b>
                    <xsl:if test="$isToday">
                        En ce moment sur la shaarlisphère
                    </xsl:if>
                    <xsl:if test="not($isToday)">
                        A ce moment sur la shaarlisphère
                    </xsl:if>
                </b>
            </div>
            <h2 class=" article-title">
                <a title="Go to original place" href="{link}">
                    <xsl:value-of select="title" />
                </a> 
            </h2>
            <div class="mini hidden-on-smartphone visible-on-hover color-blue"><xsl:value-of select="link" /></div>
            <h4><xsl:value-of select="avatar" disable-output-escaping="yes"/> 
            <span class="entete-pseudo"><b><a href="{rss_url}"><xsl:value-of select="rss_titre" /></a></b></span>
            </h4>
            
            <div class="article-content"><xsl:value-of select="description"  disable-output-escaping="yes"/></div>
        </div>
    </xsl:template>
    
    <xsl:template match="item" mode="messagerie">
        <a href="?q=id:{id_commun}" class="add-padding-left-1 add-padding-bottom-1 add-padding-top-1 {read_class}">
            <div class="truncate display-inline-block-middle no-margin no-padding width-20">
                <img class="add-margin-right-1 entete-avatar" width="16" height="16" src="{dernier_auteur_favicon}"/>
                <span class="add-margin-right-2"><xsl:value-of select="dernier_auteur" /> (<xsl:value-of select="popularity" />)</span></div>
            <span class="float-right"><xsl:value-of select="derniere_date_maj" /></span>
            <div class="truncate display-inline-block-middle no-margin no-padding max-width-60"><span class="add-margin-right-2"><xsl:value-of select="title" /></span></div>
            
        </a>
        <hr class="no-margin"/>
    </xsl:template>
    
    <xsl:template match="item" mode="standard">
        <xsl:variable name="toptopic">
            <xsl:call-template name="substring-count">
                <xsl:with-param name="string" select="description" />
                <xsl:with-param name="substr" select="'Permalink'" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:if test="$toptopic >= $filtre_popularite">
            <div class="article {read_class} add-box-shadow ">
                <xsl:if test="$next_previous = 'yes'">
                    <div style="font-size:2em;">
                        <a id="link{position()}" style="display:hidden;" href="#link{position()}" />
                        <xsl:if test="(position()-1) &gt; 0">
                            <a title="Lien précédent" style="text-decoration:none;" href="#link{position()-1}">&#171;</a>
                        </xsl:if>
                        <xsl:if test="(position()-1) &lt;= 0">
                            <a title="Aller au dernier lien" style="text-decoration:none;" href="#link{count(/rss/channel/item)}">&#171;</a>
                        </xsl:if>
                        <xsl:if test="(position()+1) &lt;= count(/rss/channel/item)">
                            <a title="Lien suivant" style="float:right;text-decoration:none;" href="#link{position()+1}">&#187;</a>
                        </xsl:if>
                        <xsl:if test="(position()+1) &gt; count(/rss/channel/item)">
                            <a title="Aller au premier lien" style="float:right;text-decoration:none;" href="#link1">&#187;</a>
                        </xsl:if>
                    </div>
                </xsl:if>
                <h2>
                    <xsl:attribute name="class">
                        article-title
                        <xsl:if test="$toptopic &gt; 1"> toptopic</xsl:if>
                    </xsl:attribute>

                    <xsl:variable name="titrestring">
                         <xsl:value-of select="title" />
                    </xsl:variable>

                    <xsl:variable name="titleencoded">
                         <xsl:value-of select="php:function('urlencode', $titrestring)" />
                    </xsl:variable>

                    <xsl:if test="$my_shaarli != ''" >
                        <xsl:variable name="favourite">
                            <xsl:call-template name="substring-count">
                              <xsl:with-param name="string" select="description" />
                              <xsl:with-param name="substr" select="$my_shaarli" />
                            </xsl:call-template>
                        </xsl:variable>
                        <a class="shaare" title="Copier ce lien" target="_blank" href='{$my_shaarli}/?post={link}&amp;source=bookmarklet&amp;title={$titleencoded}'>
                        <xsl:attribute name="class">
                            shaare
                            <xsl:if test="$favourite &gt;= 1"> favourite</xsl:if>
                        </xsl:attribute>
                        <xsl:if test="$favourite &gt;= 1"> ★</xsl:if>
                        <xsl:if test="$favourite = 0"> ☆</xsl:if>
                        </a>
                    </xsl:if> 
                    <xsl:if test="$my_respawn != ''" >
                        <a class="shaare" title="Sauvegarder" target="_blank" href='{$my_respawn}?q={link}'>☉</a>
                    </xsl:if>
                    <a title="Go to original place" href="{link}" class="wot" onmouseup="ireadit(this, '{id_commun}')"><xsl:value-of select="title" /><xsl:if test="$toptopic &gt; 1"> [<xsl:value-of select="$toptopic" />]</xsl:if></a>
                </h2>
                <div class="mini visible-on-hover color-blue"><xsl:value-of select="link" /></div>
                <div>
                    <xsl:if test="$extended and string-length(description) &gt;= 1500">
                        <xsl:attribute name="class">article-content extended</xsl:attribute>
                    </xsl:if>
                    <xsl:if test="not($extended) or string-length(description) &lt; 1500">
                        <xsl:attribute name="class">article-content</xsl:attribute>
                    </xsl:if>
                    
                    <!--<a title="Go to original place" href="{link}"><img src="./capture.php?url={link}" class="capture" width="240px" height="240px" /></a>-->
                    <xsl:if test="$is_secure = 'no' and $youtube = 'yes'">
                        <xsl:variable name="youtubevideoid">
                            <xsl:if test="substring-after(link, 'youtube.com') != ''" >
                                <xsl:if test="substring-before(substring-after(link, 'v='), '&amp;') != ''" >
                                    <xsl:value-of select="substring-before(substring-after(link, 'v='), '&amp;')" />
                                </xsl:if>
                                <xsl:if test="substring-after(link, 'v=') != ''" >
                                    <xsl:value-of select="substring-after(link, 'v=')" />
                                </xsl:if>
                                <xsl:if test="substring-after(link, 'v=') = ''" >
                                    <xsl:if test="substring-before(substring-after(link, 'embed/'), '?') != ''" >
                                        <xsl:value-of select="substring-before(substring-after(link, 'embed/'), '?')" />
                                    </xsl:if>
                                </xsl:if>
                            </xsl:if>
                        </xsl:variable>
                        <xsl:if test="$youtubevideoid != ''" >
                            <div class="wrapper">
                                <div class="h_iframe">
                                <!-- a transparent image is preferable -->
                                <img class="ratio" src="css/transparent.png"/>
                                <iframe src="http://www.youtube.com/embed/{$youtubevideoid}" frameborder="0" allowfullscreen="allowfullscreen"></iframe>
                                </div>
                            </div>
                            <br/>
                        </xsl:if>
                    </xsl:if>
                    <xsl:if test="$no_description = ''">
                        <xsl:value-of select="description" disable-output-escaping="yes"/>
                    </xsl:if>
                </div>
                <div class="article-thumbnail">
                    <xsl:if test="url_image != ''" >
                        <a title="Go to original place" href="{link}" onmouseup="ireadit(this, '{id_commun}')">
                            <div style="background:url('{url_image}'); width:100%;height:200px;background-repeat: no-repeat;background-position: right;"></div>
                        </a>
                    </xsl:if>
                </div>
                <div>
                    <xsl:if test="category != ''" >
                        <span class="article-tag">Tags : <xsl:apply-templates select="category"/></span>
                    </xsl:if>
                </div>
                <div class="clear"/>
                <xsl:if test="$extended and string-length(description) &gt;= 1500 and $no_description = ''">
                    <div class="action-extend">
                        <a class="button-extend display-inline-block" onclick="extend(this)">+</a>
                    </div>
                </xsl:if>
            </div>
        </xsl:if>
    </xsl:template>
    
    <xsl:template match="category">
        <xsl:call-template name="split">
            <xsl:with-param name="string">
                <xsl:value-of select="."/>
            </xsl:with-param>
        </xsl:call-template>
    </xsl:template>
        
    <xsl:template name="split">
        <xsl:param name="string"/>
        <xsl:choose>
            <xsl:when test="contains($string,',')">
                <a href="index.php?q={substring-before($string,',')}&amp;type=category&amp;from=20000000&amp;to=30000000"><xsl:value-of select="substring-before($string,',')"/></a>,
                <xsl:call-template name="split">
                    <xsl:with-param name="string">
                        <xsl:value-of select="substring-after($string,',')"/>
                    </xsl:with-param>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <a href="index.php?q={$string}&amp;type=category&amp;from=20000000&amp;to=30000000"><xsl:value-of select="$string"/></a>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
        
    <xsl:template name="substring-count">
      <xsl:param name="string"/>
      <xsl:param name="substr"/>
      <xsl:choose>
        <xsl:when test="contains($string, $substr) and $string and $substr">
          <xsl:variable name="rest">
            <xsl:call-template name="substring-count">
              <xsl:with-param name="string" select="substring-after($string, $substr)"/>
              <xsl:with-param name="substr" select="$substr"/>
            </xsl:call-template>
          </xsl:variable>
          <xsl:value-of select="$rest + 1"/>
        </xsl:when>
        <xsl:otherwise>0</xsl:otherwise>
      </xsl:choose>
    </xsl:template>
 
    <xsl:template name="urlencode">
        <xsl:param name="str" />
        <xsl:if test="$str">
            <xsl:variable name="first-char" select="substring($str,1,1)" />
            <xsl:choose>
                <xsl:when test="contains($safe,$first-char)">
                    <xsl:value-of select="$first-char" />
                </xsl:when>
                <xsl:otherwise>
                    <xsl:variable name="codepoint">
                        <xsl:choose>
                            <xsl:when test="contains($ascii,$first-char)">
                                <xsl:value-of
                                    select="string-length(substring-before($ascii,$first-char)) + 32" />
                            </xsl:when>
                            <xsl:when test="contains($latin1,$first-char)">
                                <xsl:value-of
                                    select="string-length(substring-before($latin1,$first-char)) + 160" />
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:message terminate="no">
                                    Warning: string contains a character that is out of range!
                                    Substituting "?".
                                </xsl:message>
                                <xsl:text>63</xsl:text>
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:variable>
                    <xsl:variable name="hex-digit1"
                        select="substring($hex,floor($codepoint div 16) + 1,1)" />
                    <xsl:variable name="hex-digit2"
                        select="substring($hex,$codepoint mod 16 + 1,1)" />
                    <xsl:value-of select="concat('%',$hex-digit1,$hex-digit2)" />
                </xsl:otherwise>
            </xsl:choose>
            <xsl:if test="string-length($str) > 1">
                <xsl:call-template name="url-encode">
                    <xsl:with-param name="str" select="substring($str,2)" />
                </xsl:call-template>
            </xsl:if>
        </xsl:if>
    </xsl:template>        
</xsl:stylesheet>
