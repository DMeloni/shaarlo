<?php
include 'config.php';
require_once 'fct/fct_rss.php';
require_once 'fct/fct_cache.php';
require_once 'fct/fct_file.php';
require_once 'fct/fct_sort.php';
require_once 'fct/fct_valid.php';
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');

unRequestMagicQuote();

$serverMsg = '';

global $DATA_DIR, $CACHE_DIR_NAME , $SHAARLIS_FILE_NAME, $POTENTIAL_SHAARLIS_FILE_NAME, $DISABLED_SHAARLIS_FILE_NAME, $SECURE_ADMIN, $MOD; 

ob_start();
?><!DOCTYPE html>
<html lang="fr"> 
	<head>
		<title>Shaarlo</title>
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
            <a href="archive.php">Archive</a>
            <a href="random.php">Aléatoire</a>
            <a href="activite.php">Activité</a>
            <a href="jappix-1.0.7/?r=shaarli@conference.dukgo.com" id="articuler">Articuler</a>
            <a href="opml.php?mod=opml">OPML</a>
			<h1 id="top"><a href="./activite.php">Activité</a></h1> 
		</div>	
			 
		<div id="content">
			<?php if (!empty($serverMsg)) { ?>
			<div class="article shaarli-youm-org">
				<h2 class="article-title "><?php echo $serverMsg;?></h2>				
			</div>			
			<?php } ?>	
			
			<?php 
			echo $MOD[basename($_SERVER['PHP_SELF']).'_top']; ?>
		</div>
		<div id="footer"> <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments - <a href="https://github.com/DMeloni/shaarlo">sources on github</a></p></div>
	</body>
</html><?php 
$page = ob_get_contents();
ob_end_clean(); 
$page = sanitize_output($page);
echo $page;
