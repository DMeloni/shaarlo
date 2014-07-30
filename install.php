<?php
include 'fct/fct_rss.php';
include 'fct/fct_cache.php';
include 'fct/fct_file.php';
include 'fct/fct_sort.php';
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');

$serverMsg = '';

$rssList = array();
$rssListFile = 'data/shaarli.txt';									
if(is_file($rssListFile)){
	header('Location: index.php');
	return;
}

$rssList = json_decode(file_get_contents($rssListFile), true);

/*
 * Rights validation
 */

if(!is_writable('cache')){
	$serverMsg = "Le dossier cache n'est pas accessible en écriture !";
}

if(!is_writable('data')){
	$serverMsg = "Le dossier data n'est pas accessible en écriture !";
}

if(!is_writable('archive')){
	$serverMsg = "Le dossier archive n'est pas accessible en écriture !";
}

/*
* Add a new rss
*/
$flippeddisabledRssList = array_flip($disabledRssList);
if(!empty($_POST) && $_POST['action'] == 'add' && empty($_POST['supprimer'])){

 	$assocUrlLabel = array_combine ($_POST['url'], $_POST['label']);

 	$rssKeys = array();
 	if(!empty($_POST['rssKey'])){
 		$rssKeys = $_POST['rssKey'];
 	}else{
 		$rssKeys[reset($_POST['label'])] = reset($_POST['url']);
 	}
	if(!empty($rssKeys)){
		foreach($rssKeys as $key => $url){
			$label = '';
			if(!empty($assocUrlLabel)){
				$label = $assocUrlLabel[$url];
			}
			if(empty($label)){
					$serverMsg = "Le nom du flux n'est pas valide";
			}
			else
			{
				if (filter_var($url, FILTER_VALIDATE_URL)) { // Vérifie si la chaine ressemble à une URL	
					// Valid Shaarli ? 
					if(is_valid_rss($url)){
						$rssList[$label] = $url;
						file_put_contents($rssListFile, json_encode($rssList));						
						header("Location: refresh.php?oneshoot=true");
					}else{
						$serverMsg = "Le flux n'est pas valide !";
					}
				}else{
						$serverMsg = "L'URL n'est pas valide !";
				}
			}	
		} 
	}
}	

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
			<h1 id="top"><a href="./install.php">Installation</a></h1> 
		</div> 
		<div id="content">
			<?php if (!empty($serverMsg)) { ?>
			<div class="article shaarli-youm-org">
				<h2 class="article-title "><?php echo $serverMsg;?></h2>				
			</div>			
			<?php }else{ ?>	
			<div class="article shaarli-youm-org">
				<h2 class="article-title ">
				<a title="Go to original place" href="">Débuter avec un premier flux Shaarli</a>
				</h2>
				<div class="article-content">
					<form action="install.php" method="POST">				
							<label for="label">Nom du flux</label>
							<input type="text" name="label[]"></input>
							<br/>
							<label for="url">URL du flux</label>
							<input type="text" name="url[]" ></input>				
							<input type="hidden" name="action" value="add"></input>
							<br/>
							<input type="submit" value="Ajouter" class="bigbutton"/>					
					</form>			
				</div>	
			</div>	
			<?php }?>
		</div>
		<div id="footer"> <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments</p> </div>
	</body>
</html><?php 
$page = ob_get_contents();
ob_end_clean(); 
$page = sanitize_output($page);
echo $page;
