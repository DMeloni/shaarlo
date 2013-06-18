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
	$rssList = json_decode(file_get_contents($rssListFile), true);
}

$potentialShaarlis = array();
$potentialShaarlisListFile = 'data/potential_shaarli.txt';
if(is_file($potentialShaarlisListFile)){
	$potentialShaarlis = json_decode(file_get_contents($potentialShaarlisListFile), true);
}	

$disabledRssList = array();
$disabledRssListFile = 'data/disabled_shaarli.txt';							
if(is_file($disabledRssListFile)){
	$disabledRssList = json_decode(file_get_contents($disabledRssListFile), true);
}

$deletedRssList = array();
$deletedRssListFile = 'data/deleted_shaarli.txt';							
if(is_file($deletedRssListFile)){
	$deletedRssList = json_decode(file_get_contents($deletedRssListFile), true);
}


/*
* RSS Deletion
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

if(!empty($_POST) && !empty($_POST['supprimer'])){
	foreach($_POST['rssKey'] as $key => $rssKey){
		if(isset($rssList[$rssKey])){
			$deletedRssList[$rssKey] = $rssList[$rssKey];
			unset($rssList[$rssKey]);
		}
	}
	file_put_contents($rssListFile, json_encode($rssList));
	file_put_contents($deletedRssListFile, json_encode($deletedRssList));
}	


/*
* Add a new rss
*/
$flippeddisabledRssList = array_flip($disabledRssList);
if(!empty($_POST) && $_POST['action'] == 'add'){

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
						// Valid Shaarli ? 
						if(is_valid_rss($url)){
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
							file_put_contents($potentialShaarlisListFile, json_encode($potentialShaarlis));					
					}					
				}
			}	
		}		
	}
}	


?><!DOCTYPE html>
<html lang="fr"> 
	<head> 
		<title>Shaarlo</title>
		<meta charset="utf-8"> 
		<meta name="description" content=""> 
		<meta name="author" content=""> 
		<link rel="shortcut icon" href="favicon.ico"> 
		<link rel="stylesheet" href="css/style.css" type="text/css" media="screen"> 
		<link rel="alternate" type="application/rss+xml" href="http://shaarli.fr/rss" title="Shaarlo Feed" /> 
	</head> 
	<body>
		<div id="header"> 
			<h1 id="top"><a href="./index.php">Les discussions de Shaarli (17/06/2013)</a></h1> 
		</div> 
		<div id="content">
			<?php if (!empty($serverMsg)) { ?>
			<div class="article shaarli-youm-org">
				<h2 class="article-title "><?php echo $serverMsg;?></h2>				
			</div>			
			<?php } ?>	


			<div class="article shaarli-youm-org">
				<h2 class="article-title ">
				<a title="Go to original place" href="">Ajouter un flux</a>
				</h2>
				<div class="article-content">
					<form action="admin.php" method="POST">				
							<label for="label">Titre du flux</label>
							<input type="text" name="label[]"></input>
							<label for="url">Url du flux</label>
							<input type="text" name="url[]"></input>				
							<input type="hidden" name="action" value="add"></input>
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
					<?php 
					foreach($potentialShaarlis as $rssKey => $rssUrl){?>							
								<input type="checkbox" checked name="rssKey[]" id="<?php echo $rssUrl; ?>" value="<?php echo $rssUrl; ?>" />
								<input type="text" style="width:20em;" name="label[]" value="<?php echo $rssKey;?>"></input>
								<input type="text" style="width:30em;" name="url[]" value="<?php echo $rssUrl;?>"></input>				
								<input type="hidden" name="action" value="add"></input>
								<br/>
					<?php }?>	
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
							<input type="checkbox" name="rssKey[]" id="<?php echo $rssKey; ?>" value="<?php echo $rssKey; ?>" />
							<label for="<?php echo $rssKey; ?>"><?php echo $rssKey .' ('.$rssUrl. ')'; ?></label>
							<br/>
						<?php }?>					
							<input type="hidden" name="action" value="disable"></input>
							<input type="submit" value="Desactiver" class="bigbutton"/>
							<input type="submit" name="supprimer" value="Supprimer à VIE" class="bigbutton"/>
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
				<a title="Go to original place" href="">Flux désactifs</a>
				</h2>

				<div class="article-content">
					<form action="admin.php" method="POST">				
				<?php
						foreach($disabledRssList as $rssKey => $rssUrl){?>		
							<input type="checkbox" name="rssKey[]" id="<?php echo $rssUrl; ?>" value="<?php echo $rssUrl; ?>" />
							<label for="<?php echo $rssUrl; ?>"><?php echo $rssKey .' ('.$rssUrl. ')'; ?></label>
							<br/>
						<?php }?>					
							<input type="hidden" name="action" value="add"></input>
							<input type="submit" value="Rendre actif" class="bigbutton"/>
							</form>
				</div>												
			</div>		
			<?php
				}
			?>

		</div>
		<div id="footer"> <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments</p> </div>
	</body>
</html>