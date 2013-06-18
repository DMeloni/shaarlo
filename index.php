<!DOCTYPE html><?php 
include 'fct/fct_xsl.php';
include 'fct/fct_rss.php';

error_reporting(0);

$rssFilePath = 'rss.xml';
$date = date('Ymd');

if(isset($_GET['date']) && is_file('archive/rss_'.$_GET['date'] . '.xml')){
	$rssFilePath = 'archive/rss_'.$_GET['date'] . '.xml';
	$date = $_GET['date'];
}

header('Content-Type: text/html; charset=utf-8');

$indexFile = 'cache/index_'.$date.'.html';

$rssFile = file_get_contents($rssFilePath);

$expire = time() - 1000 ;

if(!(file_exists($indexFile) && filemtime($indexFile) > $expire)) {
	$index = parseXsl('xsl/index.xsl', $rssFile);
// 	$index = sanitize_output($index);	
// 	file_put_contents($indexFile, $index);
}
echo $index;
// readfile($indexFile);
