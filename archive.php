<?php
include 'fct/fct_rss.php';
include 'fct/fct_cache.php';
include 'fct/fct_file.php';
include 'fct/fct_sort.php';
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');

$archiveFile = 'cache/archive.html';
$expire = time() - 1000 ;
if(file_exists($archiveFile) && filemtime($archiveFile) > $expire) {
	echo file_get_contents($archiveFile);
}else{
	
	$archiveDir = 'archive/';
	$rssFileList = array();
	
	// $actualTime = time() ;
	// for($i = 0; $i < 8000; $i++){
	// 	$actualTime = $actualTime - (24 * 3600); 
	// // 	echo $archiveDir . 'rss_' . date('Ymd', $actualTime) . '.xml';
	// 	file_put_contents($archiveDir . 'rss_' . date('Ymd', $actualTime) . '.xml', 'bonjour');
	// }
	
	
	if ($handle = opendir($archiveDir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				sscanf($file, 'rss_%4s%2s%2s.xml', $years, $months, $days);
				$rssFileList[$years]["$months"]["$days"] =  $days;
			}
		}
		closedir($handle);
	}
	// rsort($rssFileList);
	// $rssFileList = array_unique($rssFileList);
	
	$assocMonth = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
	
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
				<h1 id="top"><a href="./archive.php">Archives des discussions de Shaarli</a></h1> 
			</div> 
			<div id="content">
				<?php 
					krsort($rssFileList);
					foreach($rssFileList as $year => $rssMonth){
						ksort($rssMonth);
					?>		
				<div class="article shaarli-youm-org">
					<h2 class="article-title ">Archives de l'année <?php echo $year;?></h2>
						<?php 
						foreach($rssMonth as $month => $rssDay){
						?>				
							<div style="float:left;">
								<h3 class="article-title "><?php echo $assocMonth[(int)$month - 1];?></h3>
								<div class="article-content">
									<table border="1">
										<?php 
											asort($rssDay);
											$rssCollumnsDay = array_chunk($rssDay, 7, true);
											foreach($rssCollumnsDay as $rowDays){?>
											<tr>
												<?php foreach($rowDays as $day){?>
													<td><a href="index.php?date=<?php echo $year.$month.$day;?>" ><?php echo (int)$day;?></a></td>
												<?php } ?>
											</tr>
										<?php } ?>
									</table>
								</div>	
							</div>
						<?php 
						}
						?>																
				</div>		
				<?php 
				}
				?>			
			</div>
			<div id="footer"> <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments</p> </div>
		</body>
	</html><?php 
	$page = ob_get_contents();
	ob_end_clean(); 
	$page = sanitize_output($page);

	$index = sanitize_output($page);
	file_put_contents($archiveFile, $page);
	echo $page;
}
