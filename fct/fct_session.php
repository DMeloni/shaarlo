<?php
/**
 * Retourne le nombre de sessions ouvertes (OVH)
 * @return int
 */
function countNbSessions() {
    $dir_name = ini_get("session.save_path");

    $dir = opendir($dir_name);
    $i = 0;
    $max_time = ini_get("session.gc_maxlifetime");
    while ($file_name = readdir($dir)) {
        if($file_name == '.htaccess'){
            continue;
        }
        $file = $dir_name . "/" . $file_name;
        $lastvisit = @filemtime($file);
        $difference = mktime() - $lastvisit;

        
        if (is_file($file)) {
            if (($difference < $max_time)) {
                $i++;
            }elseif (($difference < (24 * 60 * 60 * 2))){
                @unlink($file);
            }
        }        
    }
    closedir($dir);

    return $i;
}
countNbSessions();
