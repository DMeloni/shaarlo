<?php
error_reporting(0);
include 'fct/fct_rss.php';
include 'fct/fct_cache.php';
include 'fct/fct_file.php';
include 'fct/fct_sort.php';
include 'fct/fct_valid.php';

if(!is_file('config.php')){
    $serverMsg = "Le fichier config.php n'existe pas ! Renommez config.php.sample en config.php et éditez-le !";
}else{

    include 'config.php';
    header('Content-Type: text/html; charset=utf-8');

    $serverMsg = '';
    $rssList = array();
    global $DATA_DIR, $SHAARLIS_FILE_NAME;
    $rssListFile = sprintf('%s/%s', $DATA_DIR, $SHAARLIS_FILE_NAME);

    $rssList = json_decode(file_get_contents($rssListFile), true);

    $indexFile = sprintf('%s/%s/%s', $DATA_DIR, $CACHE_DIR_NAME, 'index.html');
    if(checkInstall() || is_file($indexFile)){
        header('Location: index.php');
    }
    /*
     * Rights validation
     */
    if(!is_dir($DATA_DIR)){
        if(!mkdir($DATA_DIR)){
            $serverMsg = "Le dossier $DATA_DIR ne peut pas être créé !";
        }
    }
    include 'config.php';
    if(!is_writable($DATA_DIR)){
        $serverMsg = "Le dossier $DATA_DIR n'est pas accessible en écriture !";
    }else{
        if(!is_file($rssListFile) && !file_put_contents($rssListFile, json_encode(array()))){
            $serverMsg = "Le fichier $rssListFile n'est pas modifiable !";
        }
        if(!is_writable($rssListFile)){
            $serverMsg = "Le fichier $rssListFile n'est pas accessible en écriture !";
        }
        $cacheDir  = sprintf('%s/%s', $DATA_DIR, $CACHE_DIR_NAME);
        if(!is_dir($cacheDir)){
            if(!mkdir($cacheDir)){
                $serverMsg = "Le dossier $cacheDir ne peut pas être créé !";
            }
        }
        $archiveDir  = sprintf('%s/%s', $DATA_DIR, $ARCHIVE_DIR_NAME);
        if(!is_dir($archiveDir)){
            if(!mkdir($archiveDir)){
                $serverMsg = "Le dossier $archiveDir ne peut pas être créé !";
            }
        }
    }

    $mods = get_loaded_extensions();
    if (!in_array('xsl',$mods)){
        $serverMsg = "Le module PHP xsl est obligatoire !";
    }
    if (!in_array('mbstring',$mods)){
        $serverMsg = "Le module PHP mbstring est obligatoire !";
    }
}

/*
* Add a new rss
*/
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
					$serverMsg = "Le nom du flux n'est pas valide !";
			}
			else
			{
				if (filter_var($url, FILTER_VALIDATE_URL)) { // Vérifie si la chaine ressemble à une URL
					// Url shaarli format
					$url = explode('?', $url);

					// Posted link is eg : http://xxx/?azerty or http://xxx/
					$url = $url[0] . '?do=rss';

					// Valid Shaarli ?
					if(is_valid_rss($url) !== false){
						$rssList[$label] = $url;
						file_put_contents($rssListFile, json_encode($rssList));
						header("Location: api.php?buildAllRss=true");
						return;
					}else{
						$serverMsg = "Le flux est injoignable !";
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
			<h1 id="top"><a href="./boot.php">Installation</a></h1>
		</div>
		<div id="content">
			<?php if (!empty($serverMsg)) { ?>
			<div class="article shaarli-youm-org">
				<h2 class="article-title "><?php echo $serverMsg;?></h2>
			</div>
			<?php }else{ ?>
			<div class="article shaarli-youm-org">
				<h2 class="article-title ">
				<a title="Go to original place" href="">Débuter avec un flux Shaarli</a>
				</h2>
				<div class="article-content">
					<form action="boot.php" method="POST">
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
