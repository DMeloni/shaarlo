<?php 
/**
 * Renvoie une variable qu'elle soit en POST ou GET
 */
function get($name) {
    if (isset($_POST[$name])) {
        return $_POST[$name];
    }
    if (isset($_GET[$name])) {
        return $_GET[$name];
    }
    
    return null;
}



