<?php 

function storeCache($cacheFile, $fileContent){
	return file_put_contents($cacheFile, $fileContent);
}

/*
 * Indicate if the file is already cache
 * @param $cacheFile : 'cache/rss/test.xml'
 * @param expireTime : 1000 // 1000 seconds
 * @return true/false
 */
function isCached($cacheFile, $expireTime){
	if(file_exists($cacheFile) && filemtime($cacheFile) > $expireTime){
		return true;
	}
	return false;
}

/*
 * Return the content of the cached file
 * @cacheFile : 'cache/rss/test.xml'
 * @return string
 */
function getCachedContent($cacheFile){
	return @file_get_contents($cacheFile);
}


