<?php
include 'auto_restrict.php';
include 'config.php';
include 'fct/fct_rss.php';
include 'fct/fct_cache.php';
include 'fct/fct_file.php';
include 'fct/fct_sort.php';
include 'fct/fct_valid.php';
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');

unRequestMagicQuote();

$serverMsg = '';

global $DATA_DIR, $SHAARLIS_FILE_NAME, $POTENTIAL_SHAARLIS_FILE_NAME, $DISABLED_SHAARLIS_FILE_NAME;

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
			<div id="right">&nbsp;Vous etes <em>connecté!</em>&nbsp;
				Durée d'inactivité avant déconnexion:<em> <?php echo (!isset($_COOKIE[$auto_restrict['cookie_name']])) ? $auto_restrict['session_expiration_delay'].' min' : $auto_restrict['cookie_expiration_delay'].' jour(s)'; ?>.</em>&nbsp;
				<a href='admin.php' title="Recharger cette page" ><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAYAAADEtGw7AAAEO0lEQVR4Xs3TcUyUZRwH8O/z3vvee3ceB3cHiCAQeiKRliitTDQtpwOTxOklUxR3UUA1RSmrLWXoDET+UFqlbpoOqMbMwkQiBs4aEqYxEBHE4ADl6Di4Ozjujnvvfaq5+c+hxvynz/b89du+e/bdvvhfKDhejU+P1aDo+Hmm49RLoGfwUAymYJwEAYpQad+oeu3eW7lRW3oasHih7smDRUYGIuEC+qzS7BG333bG3sbPWH4Qk2HxEHd6TAhQsiS/vF9rNDl0tjFnRG3jn5xGJQ+yOGULQGRPc5KwK92axIuYBMEk3v38NlbFqZTVTeY1t4zWTbaxiUVuwavxil4JQ6joJRIZw8kZrT+qtTJrJqXU+MP+5EcHn7tuASshmt87x3Ou3x5722iyBbrcTgICUIaAEvF+gRIWCgXvDFSJhzUKW8GERzoOf4rKN1/x7fh0iw00QCpvHxGzTS4xx8sJfrxcMFLRcU3w2K4S1tPDaKYJVKsE1cgxrpTKhyVs2sCIc+Fo1HIMXG307bjohgMXG4yYOz/kxcEhW+qQqbdhvLf7e7fZ0uwcHjdLBI83YnXiRrNStU+gYKngFkT7cP/wvd6fbKaOIY8mAdKuG/Dx8R925LWOctvLr320Ou+bfc+s3DYTAJGwHHIuD6LWOMomVfYXRJ+85Yo49Etr6M7S4sA1uQnyiHglHmV3XTeyKpqkyZ8ci49duTlAt2T9g5v+q0bsrmhWvfzZpcKw90sPaNfteV4eukBBKcVj6XcdwaupOeAVvh9YnLYXKTtKJPPW7QxW6BL4Ofvb8URyG4dQdXeM3XnJGLP26IV5IQlbmPjsww/uVxruoaa+h+w4eolNyTuP9EM1eKTIMjPKWgfk2fWmRa9XD+YtOdvd9GzhuUwAZNaGd/CvsiYLLt+hzMELHcvSi79bykcvJ0m5Jb7LO9HYjRa7AtF+bkWX3RX3bSdSBl1IsnpI9IRjdIAOWzpi6yjt++BDNNxsI813HdquETHZbJ/YYBkxF9d11NOMxM2+wT+2dyGGryJVd1O3WdyaPVaXGOb2UBYiAesVvCrV3Oem32hTxBoMyhO/8XN4nltKiGuhY3DgrKW98VrBLhtuVpf7Lk+XdxRdqvcQZa/YJNLZJUTgA6lXABEBQhmRZeDmQD2cRMop5TI+aBqIv9hbZ79dm1P8ZX7rLEImnzS/GJiecBIeZ6dKKl22nxFmZFGPyMHrAURyf6SUAKIIBee1hsiGfpbZrhZWnc6/DoDiccKzjvzzSmKeyqz/NcrQTCPTLlpD9adbwvRft4VvrGiKSikrj15TlB7zwoYQAAj2Y/GfaFKnA+uBiIzSTVEZDX9FbD5TqV6hj1OvMMwPTHhLFxybqFZICQmcGYMpC8/4AmEZB6ZFGk4dDn/jwG51NJjQpK14YjMMegSvzkKIfuvsoFWvzeUAqBfFYyr+Bvk2whLdALgAAAAAAElFTkSuQmCC"/></a>&nbsp;
				<a href='?deconnexion=ok' class="deco" title="DECONNEXION"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAYAAADEtGw7AAAFCUlEQVR4XoWVa2wU1xmGn5ld79W7a4xstDh4G6jBik1cx1sugoRc3FAKUiCK8qNQ5aJIEUT5UalARSFtqrYWQSVKRaoqRhCqVApSCVISAkooCkQhwXGgKTY3Y+PY4CveZe317s5tvx6PhGpFbvtJj+boO0fPeUc6M0cTEWaqt6uSGlADLHWBJUAeuDCNzmdvtTvMUDOKlbQBeAeoD2pCyAOxsghF22EimyNfhIKiiNYOPKfkHXyn9O8IdcV2hLY5QU9988ubWPteKz/+6gQrvjjOg18e50enjvD4/j08tHEdlV6SCF+rIDsU3hkTuxNw3K/TvKj2e9zX+kd8VXOxb49RLBiggdg2UjCRooPm81Po6qZn95+43jtCQTgJrFbpiwDTd9kW0Ghe9tN1JH6/k6JpYg2PUjKnEr1k+jJwLIvcpatuf8GuXxD+ywH+2X6l2SiyFdgNgIhwcG5T/cF4k3G++UlxDEOMwWGxs1mZKse2Zbi7Ry6f/sxFjd3eVE3035TRw0dl6K1DcrZupUw5lKtBREANPIqvjiWSkrvapaRDYo2lXGlqaEjeb3lNTmzbKW2vtsjFlr3u8/PX98lYX78rH/5Xhwzua5XuLVvlWOIBUa6LCk0H6nQkWbfxCfe17VQGb/kssuk0/2jZQyKToy4SY/7ieuKLaojrXqqzeQb2/5Xs6G0qF9eRK4/hi1cSjwYB6oEaXYPGoA6xVSvIdV7GWzEbgLOH/sYCC2K2A5pG+fq1LuOGgZMZJ5IrMPTuEQBKG+8nn0rji0UIaYIGS706NIZ08N87j7G/f0C46QdYhQL5M2eJzq7ANjPokVLl1gAYzk64iQE0tYGt1s5KJOgZGSEaCBD2gGErsUejsTQcxE7dwRoaweP3c7vjEpGCiXMnA0CxLMbdsicnVX+CuzXZ20esdiFmwO9uHtYho7HEq2sEPSUect90YnRdByCgRL7JHLblAOCZ+I+omMtjuxvi4o9GAFSYTqKZHKChnHndA225iRzGt/0Yvf1M9t8kdk8Vhm3jpDNu6mI2N12M20+lEcsmMDdOpq8ffTTlJjYVHrigK/s5U0BMy/2axtu/AaB6ywuI7SCWg9k/gGOa2IbJ5M0B3L4jzNn8PAC32r4mGgih+QNYbmIuaEfnNS0Eri5+fCWFaz04fh81h1sJzCrj6q9+h/XJGQDSc2bzbSFHxUiKKn+QsjWPcc+r2ymk73B47VM0UIJu2vSO3kGgQSWmS9Fx69wFfFVxPIZB9+43AKj9wy7iLTuJLP8hFbbwgCdA7cMPUb37Feb99pcAfPqbFhJFnUBpKRnDRNNoV75ORIQPq5saFMb5VevlypPPSufD6+XMiz+X8YFB+S/lzh19brO8l3xErmx4Rs4vXyNTDkW9iOCKFXyUaNr+8feXyaU1G6Vr00ty+Scb5bPVT8sXr/9Zrn98SjI3b03hjj/f+6YcWLlaTj+2Qbp+9pJcUms/qVkuyrHjrs877ce8p2hZzQPdN5ornWrCiSrihkn+dBuDx05xzTKYtG3CJSVEQ2GWJRbgD4XIqtM0eqMPxzRP6vDajDfIyXuTXmAb8OtIZYWv/L5FlEQjaD4fOA4g4PEihoE1kSV9+RrjwyMm8Aqwp/lGe/F/Xk2n5ifrgYPoWtIXDOELh/BHy8CrY6RSmNlJzHweRDqATY/2uGf0/995AKfnJz1AHdA4jSDQBpxzga5VPe0zCv4NGfzEC3rbCQIAAAAASUVORK5CYII="/></a>
			</div>
			<h1 id="top"><a href="./admin.php">Administration</a></h1> 
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
			?>
			<?php 
				$date = date('Ymd');
				$currentRss = sprintf('%s/%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME, 'rss_'.date('Ymd'). '.xml');
				if(is_file($currentRss)){
					$mtimeLastReload = date('d/m/Y \à H\h i\m s\s', filemtime($currentRss));
				}else{
					$mtimeLastReload = str_replace(array('rss_','.xml'), '', readMyDir($DATA_DIR.DIRECTORY_SEPARATOR.$ARCHIVE_DIR_NAME));
					$mtimeLastReload = preg_replace_callback(
								"|([0-9]{4})([0-9]{2})([0-9]{2})|",
					            function ($matches) {return $matches[3].'/'.$matches[2].'/'.$matches[1];},
					            $mtimeLastReload
					);
				}
				?>
				<div class="article shaarli-youm-org">
					<h2 class="article-title ">
					<a title="Go to original place" href="">Info sur le dernier reload</a>
					</h2>
					<div class="article-content">
						<span>Dernier reload le : <?php echo $mtimeLastReload;?></span>
						<br/>
						<a href="refresh.php?oneshoot=true">Forcer un reload</a>		
					</div>	
				</div>
		</div>
		<div id="footer"> <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments - <a href="https://github.com/DMeloni/shaarlo">sources on github</a></p></div>
	</body>
</html><?php 
$page = ob_get_contents();
ob_end_clean(); 
$page = sanitize_output($page);
echo $page;
