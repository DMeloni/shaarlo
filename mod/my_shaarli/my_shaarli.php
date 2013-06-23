<?php 
/**
 * Mod : my_shaarli
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


global $MOD, $MY_SHAARLI_FILE_NAME; // Super variable (linked with shaarlo pages)


$myShaarli = array();
$myShaarliFile = sprintf('%s/%s', $DATA_DIR, $MY_SHAARLI_FILE_NAME);
if(is_file($myShaarliFile)){
	$myShaarli = json_decode(file_get_contents($myShaarliFile), true);
}

/*
 * Catch user submit here
 */
if(isset($_POST['mod']) && $_POST['mod'] === 'my_shaarli' && !empty($_POST['shaarli_url'])){	
	if (is_writable($myShaarliFile) && filter_var($_POST['shaarli_url'], FILTER_VALIDATE_URL) && urlExists($_POST['shaarli_url'])) {
		$myShaarli = array('me' => $_POST['shaarli_url']);
		$myShaarliJson = json_encode($myShaarli);
		file_put_contents($myShaarliFile, $myShaarliJson);
	}
}

if(is_array($myShaarli)){
	$myShaarliUrl = reset($myShaarli);
}

/*
 * Show html for user
 */
$MOD['admin.php_top'] .= sprintf('<div class="article shaarli-youm-org">
		<h2 class="article-title ">
		<a title="Go to original place" href="">Mon shaarli</a>
		</h2>
		<div class="article-content">
			<form action="" method="POST">
				<input name="mod" type="hidden" value="my_shaarli" />
				<input name="shaarli_url" type="text" value="%s" />
				<input type="submit" value="Mettre à jour" />
			</form>
		</div>
	</div>', $myShaarliUrl);	

