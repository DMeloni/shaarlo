<?php 
/**
 * Mod : my_respawn
 * Modify your shaarli url
 * @author : DMeloni
 * no support : at your peril
 */
include_once 'config.php';
require_once 'fct/fct_rss.php';

/*
 * Mod filter
*/
$modActivedOnPages = array('admin.php'); // Where the module can run

/*
 * Module control
 */
$path = $_SERVER['PHP_SELF'];
$file = basename ($path);
if(!in_array($file, $modActivedOnPages)){ //
	return ;
}


global $MOD, $DATA_DIR, $MY_RESPAWN_FILE_NAME; // Super variable (linked with shaarlo pages)


$myRespawn = array();
$myRespawnFile = sprintf('%s/%s', $DATA_DIR, $MY_RESPAWN_FILE_NAME);
if(is_file($myRespawnFile)){
	$myRespawn = json_decode(file_get_contents($myRespawnFile), true);
}

/*
 * Catch user submit here
 */
if(isset($_POST['mod']) && $_POST['mod'] === 'my_respawn' && !empty($_POST['respawn_url'])){	
	if (filter_var($_POST['respawn_url'], FILTER_VALIDATE_URL) && urlExists($_POST['respawn_url'])) {
		$myRespawn = array('me' => $_POST['respawn_url']);
		$myRespawnJson = json_encode($myRespawn);
		file_put_contents($myRespawnFile, $myRespawnJson);
	}
}

if(is_array($myRespawn)){
	$myRespawnUrl = reset($myRespawn);
}

/*
 * Show html for user
 */
$MOD['admin.php_top'] .= sprintf('<div class="article shaarli-youm-org">
		<h2 class="article-title ">
		<a title="Go to original place" href="">Mon respawn</a>
		</h2>
		<div class="article-content">
			<form action="" method="POST">
				<input name="mod" type="hidden" value="my_respawn" />
				<input name="respawn_url" type="text" value="%s" />
				<input type="submit" value="Mettre à jour" />
			</form>
		</div>
	</div>', $myRespawnUrl);	

