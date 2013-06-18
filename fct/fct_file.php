<?php 
/*
 * Enregistre une donnée $datas dans un fichier $file
*/
function store($file,$datas){
	return file_put_contents($file,gzdeflate(json_encode($datas)));
}

/*
 * Charge une donnée PHP d'un fichier $file
* @param $file : le nom du fichier
* @return mixed : la donnée stockée avec la fonction store
*/
function unstore($file){
	return json_decode(gzinflate(file_get_contents($file)),true);
}