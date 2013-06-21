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
$indexFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'index.html'); 

// Autoredirect on boot.php
if(!checkInstall() && !is_file($indexFile) ){
	header('Location: boot.php');
	return;
}


$rssList = array();
$rssListFile = sprintf('%s/%s', $DATA_DIR, $SHAARLIS_FILE_NAME);								
if(is_file($rssListFile)){
	$rssList = json_decode(file_get_contents($rssListFile), true);
}

$potentialShaarlis = array();
$potentialShaarlisListFile = sprintf('%s/%s', $DATA_DIR, $POTENTIAL_SHAARLIS_FILE_NAME);
if(is_file($potentialShaarlisListFile)){
	$potentialShaarlis = json_decode(file_get_contents($potentialShaarlisListFile), true);
}	

$disabledRssList = array();
$disabledRssListFile = sprintf('%s/%s', $DATA_DIR, $DISABLED_SHAARLIS_FILE_NAME);						
if(is_file($disabledRssListFile)){
	$disabledRssList = json_decode(file_get_contents($disabledRssListFile), true);
}

$deletedRssList = array();
$deletedRssListFile = sprintf('%s/%s', $DATA_DIR, $DELETED_SHAARLIS_FILE_NAME);						
if(is_file($deletedRssListFile)){
	$deletedRssList = json_decode(file_get_contents($deletedRssListFile), true);
}

/*
* RSS "Disablation"
*/
if(!empty($_POST) && $_POST['action'] == 'disable' && empty($_POST['supprimer'])){
	foreach($_POST['rssKey'] as $key => $rssKey){
		if(isset($rssList[$rssKey])){
			$disabledRssList[$rssKey] = $rssList[$rssKey];
			unset($rssList[$rssKey]);
		}
	}
	file_put_contents($rssListFile, json_encode($rssList));
	file_put_contents($disabledRssListFile, json_encode($disabledRssList));
}	

/*
* RSS Deletion
*/
$flippeddisabledRssList = array_flip($disabledRssList);
if(!empty($_POST) && !empty($_POST['supprimer'])){
	foreach($_POST['rssKey'] as $key => $rssKey){
		if(isset($flippeddisabledRssList[$rssKey])){
			$label = $flippeddisabledRssList[$rssKey];
			$deletedRssList[$label] = $rssKey;
			unset($disabledRssList[$flippeddisabledRssList[$rssKey]]);
		}
	}
	file_put_contents($disabledRssListFile, json_encode($disabledRssList));
	file_put_contents($deletedRssListFile, json_encode($deletedRssList));
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
			}else{
				if(isset($flippeddisabledRssList[$url])){
					$label = $flippeddisabledRssList[$url];
				}
			}
			if(empty($label)){
					$serverMsg = "Le nom du flux n'est pas valide";
			}else{
				if(array_key_exists($label, $rssList)){
					$serverMsg = "Le nom du flux existe deja";
				}else{
					if (filter_var($url, FILTER_VALIDATE_URL)) { // Vérifie si la chaine ressemble à une URL	
						// Url shaarli format 
			            $url = explode('?', $url);
			            
			            // Posted link is eg : http://xxx/?azerty or http://xxx/
			            $url = $url[0] . '?do=rss'; 
						// Valid Shaarli ? 
						if(is_valid_rss($url) !== false){
							$rssList[$label] = $url;
							file_put_contents($rssListFile, json_encode($rssList));						
							$serverMsg = "Flux validé ! ";

							if(isset($disabledRssList[$label])){
								unset($disabledRssList[$label]);
								$flippeddisabledRssList = array_flip($disabledRssList);
								file_put_contents($disabledRssListFile, json_encode($disabledRssList));
							}

						}else{
							$serverMsg = "Le flux est non valide";
						}
					}else{
							$serverMsg = "L'url est non valide";
					}
					$flippedPotentialShaarlis = array_flip($potentialShaarlis);
					if(in_array($url, $potentialShaarlis)){
							$labelTmp = $flippedPotentialShaarlis[$url];
							unset($potentialShaarlis[$labelTmp]);
							if(false == file_put_contents($potentialShaarlisListFile, json_encode($potentialShaarlis))){
				            	$serverMsg = "Problème d'enregistrement";
				            }				
					}					
				}
			}	
		}		
	}
}
function readMyDir($dir) {
   $dir = opendir($dir);
   while(($entry = readdir($dir)) !== false) {
       if($entry !== '.' && $entry !== '..') {
           break;
       }
   }
   closedir($dir);
   return $entry;
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
			<a href="index.php">Accueil</a>
			<a href="admin.php">Administration</a>
			<a href="archive.php">Archive</a>
			<h1 id="top"><a href="./admin.php">Administration</a></h1> 
		</div>	
			 
		<div id="content">
			<?php if (!empty($serverMsg)) { ?>
			<div class="article shaarli-youm-org">
				<h2 class="article-title "><?php echo $serverMsg;?></h2>				
			</div>			
			<?php } ?>	
			
			<?php 
			echo $MOD[basename($_SERVER['PHP_SELF']).'_top']; ?>

			<div class="article shaarli-youm-org">
				<h2 class="article-title ">
				<a title="Go to original place" href="">Ajouter un flux</a>
				</h2>
				<div class="article-content">
					<form action="admin.php" method="POST">				
							<label for="new_title">Titre du flux</label>
							<input type="text" id="new_title" name="label[]" />
							<br/>
							<label for="new_url">Url du flux</label>
							<input type="text" id="new_url" name="url[]" />				
							<input type="hidden" name="action" value="add" />
							<br/>
							<input type="submit" value="Ajouter" class="bigbutton"/>					
					</form>			
				</div>	
			</div>	
			<?php
			if(!empty($potentialShaarlis)){
			?>	
			<div class="article shaarli-youm-org">
				<h2 class="article-title ">
				<a title="Go to original place" href="">Proposition de flux</a>
				</h2>
				<div class="article-content">
					<form action="admin.php" method="POST">	
					<table style="width:90%;">
					<?php 
					foreach($potentialShaarlis as $rssKey => $rssUrl){?>
								<tr>
									<td  style="width:2%;" rowspan="2">
										<input style="float:left;" type="checkbox" checked name="rssKey[]" id="<?php echo $rssUrl; ?>" value="<?php echo $rssUrl; ?>" />
									</td>
									<td></td>
								</tr>
								<tr>
									<td>
										<input type="text" style="width:99%;" name="label[]" value="<?php echo $rssKey;?>" />
										<br/>
										<input type="text" style="width:99%;" name="url[]" readonly value="<?php echo $rssUrl;?>" />
									</td>
								</tr>
																						
					<?php }?>	
						</table>		
						<input type="hidden" name="action" value="add" />
						<input type="submit" value="Ajouter les selectionnés" class="bigbutton"/>					
					</form>
				</div>	
			</div>	
			<?php } ?>

			<?php
			if(!empty($rssList)){
			?>			
			<div class="article shaarli-youm-org">
				<h2 class="article-title ">
				<a title="Go to original place" href="">Flux actifs</a>
				</h2>

				<div class="article-content">
					<form action="admin.php" method="POST">				
				<?php
						foreach($rssList as $rssKey => $rssUrl){?>		
							<input type="checkbox" name="rssKey[]" id="<?php echo str_replace(' ', '-', $rssKey); ?>" value="<?php echo $rssKey; ?>" />
							<label for="<?php echo str_replace(' ', '-', $rssKey); ?>"><?php echo unMagicQuote($rssKey);?><span class="urlDetail"><?php echo '('.$rssUrl. ')'; ?></span></label>
							<br/>
						<?php }?>					
							<input type="hidden" name="action" value="disable" />
							<input type="submit" value="Desactiver" class="bigbutton"/>
							</form>
				</div>												
			</div>		
			<?php
				}
			?>
	

			<?php
			if(!empty($disabledRssList)){
			?>			
			<div class="article shaarli-youm-org">
				<h2 class="article-title ">
				<a title="Go to original place" href="">Flux neutralisés</a>
				</h2>

				<div class="article-content">
					<form action="admin.php" method="POST">				
				<?php
						foreach($disabledRssList as $rssKey => $rssUrl){?>		
							<input type="checkbox" name="rssKey[]" id="<?php echo $rssUrl; ?>" value="<?php echo $rssUrl; ?>" />
							<label for="<?php echo $rssUrl; ?>"><?php echo unMagicQuote($rssKey);?><span class="urlDetail"><?php echo '('.$rssUrl. ')'; ?></span></label>
							<br/>
						<?php }?>					
							<input type="hidden" name="action" value="add" />
							<input type="submit" value="Rendre actif" class="bigbutton"/>
							<input type="submit" name="supprimer" value="Supprimer à VIE" class="bigbutton"/>
							</form>
				</div>												
			</div>		
			<?php
				}
				echo $MOD[basename($_SERVER['PHP_SELF']).'_bottom'];
			?>
		</div>
		<div id="footer"> <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments - <a href="https://github.com/DMeloni/shaarlo">sources on github</a></p></div>
	</body>
</html><?php 
$page = ob_get_contents();
ob_end_clean(); 
$page = sanitize_output($page);
echo $page;
