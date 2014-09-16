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
        <a href="random.php">Aléatoire</a>
        <a href="my.php">My</a>
        <a href="opml.php?mod=opml">OPML</a>
        <a href="https://nexen.mkdir.fr/shaarli-river/" id="river">Shaarli River</a>
        <h1 id="top"><a href="./my.php">Espace My</a></h1> 
    </div> 
<div id="content">

    <div class="article shaarli-youm-org">
        <h2 class=" article-title ">Explication</h2>
        <div>
            L'idée : votre compte n'est pas hébergé sur shaarli.fr mais sur votre propre shaarli.<br/>
            shaarli.fr ne fait que lire la configuration de votre shaarli pour charger vos préférences. <br/>
            Et c'est tout, si vous souhaitez "supprimer votre compte", vous n'avez pas à passer par un administrateur ou à envoyer un courrier recommandé :
            vous avez juste à supprimer un lien de votre shaarli.
        </div>
    </div>
    <div class="article shaarli-youm-org">
        <h2 class=" article-title ">En pratique</h2>
        <div>
        Vous devez créer un nouveau message dans votre shaarli avec ces deux informations : 
            <ul>
                <li>Tag : 'shaarli.fr_configuration'</li>
                <li>Description : Ce que génère le formulaire de cette page via une clef de chiffrement</li>
            </ul>
        Lorsque vous vous identifiez sur le site, vous spécifiez : 
            <ul>
                <li>L'url de votre shaarli</li>
                <li>La clef qui permet de déchiffrer votre configuration</li>
            </ul>
        </div>
    </div>
    <div class="article shaarli-youm-org">
        <h2 class=" article-title ">Ma config actuelle</h2>
        <form action="getShaarliConfiguration.php" method="POST">
            <input type="text" name="shaarli" placeholder="L'url du shaarli" />
            <input type="text" name="password" placeholder="Clef secrete de dechiffrement" />
            <input type="submit" value="Charger la configuration" onClick="getShaarliConfiguration(this);return false;"/>
        </form>
    </div>
    
    <div class="article shaarli-youm-org">
        <form>
            <textarea>{"shaarli":"http:\/\/machin.chose?do=rss","subscriptions":{"machin":"http:\/\/machin.chose?do=rss"}}</textarea>
            <input type="text" placeholder="Ma clef secrete" />
            <input type="submit" value="Générer ma configuration"/>
        </form>
    </div>
    <div class="article shaarli-youm-org">
        <h2 class=" article-title ">Le message à copier dans votre shaarli avec le tag 'shaarli.fr_configuration'</h2>
        <div>
            WFDCGhO+kCcmiqSqGUH+kMQ2NRPG23I7YM0ZKy497eUndzXaWQXqHiHqqfHwql3ELRxOwVbVnRaHS6tkb9vE3lVEVu5xay+9/cLh2t5hvruBPmdCpa06QAQcKjLiuM0hdpZ0B6hYpMjuuZJB+jWG7XJOP1wK95cJhl41iev7GZo==
        </div>
    </div>
    <div class="article shaarli-youm-org">
        <h2 class=" article-title ">Le même message en json</h2>
        <div>
            WFDCGhO+kCcmiqSqGUH+kMQ2NRPG23I7YM0ZKy497eUndzXaWQXqHiHqqfHwql3ELRxOwVbVnRaHS6tkb9vE3lVEVu5xay+9/cLh2t5hvruBPmdCpa06QAQcKjLiuM0hdpZ0B6hYpMjuuZJB+jWG7XJOP1wK95cJhl41iev7GZo==
        </div>
    </div>
    
    <div class="article shaarli-youm-org">
        <h2 class=" article-title ">Le code qui chiffre votre message</h2>
        <div>
            Parce qu'il est plus prudent de créer sa configuration sur un autre site web. Voici le code PHP qui chiffre et déchiffre votre message.
            <br/>
            <code>
                $sSecretKey = 'votre_password';<br/>
                $sValue = 'votre_configuration_en_json';<br/>
                <br/>
                function fnEncrypt($sValue, $sSecretKey) {<br/>
                    return rtrim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $sSecretKey, $sValue, MCRYPT_MODE_CBC)), "\0\3");<br/>
                }<br/>
                <br/>
                function fnDecrypt($sValue, $sSecretKey) {<br/>
                    return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $sSecretKey, base64_decode($sValue), MCRYPT_MODE_CBC), "\0\3");<br/>
                }<br/>
            </code>

        </div>
    </div>
    
<script>
    function getShaarliConfiguration(element) {
        var form = element.form;
        var kvpairs = [];
        for ( var i = 0; i < form.elements.length; i++ ) {
           var e = form.elements[i];
           kvpairs.push(encodeURIComponent(e.name) + "=" + encodeURIComponent(e.value));
        }
        var queryString = kvpairs.join("&");
        console.log(queryString);

        var r = XMLHttpRequest ? new XMLHttpRequest() : 
                             new ActiveXObject("Microsoft.XMLHTTP"); 
        r.open("POST", "getShaarliConfiguration.php", true);
        r.setRequestHeader( "Content-type", "application/x-www-form-urlencoded" ); 
        r.onreadystatechange = function () {          
          if (r.readyState == 4) {
            if (r.status == 200) {
                // Succes
                alert('ok');
            } else {
                // Echec
            }
          }
        };
        r.send(queryString); // for POST requests
    }
</script>
</body>
</html>


