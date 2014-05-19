<?php 
/**
 * Mod : test_mod
 * Show a sample MOD
 * @author : DMeloni
 * no support : at your peril
 */
include_once 'config.php';

/*
 * Mod filter
*/
$modActivedOnPages = array('admin.php'); // Where the module can be used

/*
 * Module control
 */
$path = $_SERVER['PHP_SELF'];
$file = basename ($path);
if(!in_array($file, $modActivedOnPages)){ //
	return ;
}


global $MOD; // Super variable (linked with shaarlo pages)

/*
 * Catch user submit here
 */
$text = '';
if(isset($_GET['mod']) && $_GET['mod'] === 'test_mod'){
	$text = '\o/';
}

/*
 * Show html for user
 */
$MOD['admin.php_top'] .= sprintf('<div class="article shaarli-youm-org">
		<h2 class="article-title ">
		<a title="Go to original place" href="">Module de test</a>
		</h2>
		<div class="article-content">
			<a href="admin.php?mod=test_mod" title="Module de test" ><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAYAAADEtGw7AAAEO0lEQVR4Xs3TcUyUZRwH8O/z3vvee3ceB3cHiCAQeiKRliitTDQtpwOTxOklUxR3UUA1RSmrLWXoDET+UFqlbpoOqMbMwkQiBs4aEqYxEBHE4ADl6Di4Ozjujnvvfaq5+c+hxvynz/b89du+e/bdvvhfKDhejU+P1aDo+Hmm49RLoGfwUAymYJwEAYpQad+oeu3eW7lRW3oasHih7smDRUYGIuEC+qzS7BG333bG3sbPWH4Qk2HxEHd6TAhQsiS/vF9rNDl0tjFnRG3jn5xGJQ+yOGULQGRPc5KwK92axIuYBMEk3v38NlbFqZTVTeY1t4zWTbaxiUVuwavxil4JQ6joJRIZw8kZrT+qtTJrJqXU+MP+5EcHn7tuASshmt87x3Ou3x5722iyBbrcTgICUIaAEvF+gRIWCgXvDFSJhzUKW8GERzoOf4rKN1/x7fh0iw00QCpvHxGzTS4xx8sJfrxcMFLRcU3w2K4S1tPDaKYJVKsE1cgxrpTKhyVs2sCIc+Fo1HIMXG307bjohgMXG4yYOz/kxcEhW+qQqbdhvLf7e7fZ0uwcHjdLBI83YnXiRrNStU+gYKngFkT7cP/wvd6fbKaOIY8mAdKuG/Dx8R925LWOctvLr320Ou+bfc+s3DYTAJGwHHIuD6LWOMomVfYXRJ+85Yo49Etr6M7S4sA1uQnyiHglHmV3XTeyKpqkyZ8ci49duTlAt2T9g5v+q0bsrmhWvfzZpcKw90sPaNfteV4eukBBKcVj6XcdwaupOeAVvh9YnLYXKTtKJPPW7QxW6BL4Ofvb8URyG4dQdXeM3XnJGLP26IV5IQlbmPjsww/uVxruoaa+h+w4eolNyTuP9EM1eKTIMjPKWgfk2fWmRa9XD+YtOdvd9GzhuUwAZNaGd/CvsiYLLt+hzMELHcvSi79bykcvJ0m5Jb7LO9HYjRa7AtF+bkWX3RX3bSdSBl1IsnpI9IRjdIAOWzpi6yjt++BDNNxsI813HdquETHZbJ/YYBkxF9d11NOMxM2+wT+2dyGGryJVd1O3WdyaPVaXGOb2UBYiAesVvCrV3Oem32hTxBoMyhO/8XN4nltKiGuhY3DgrKW98VrBLhtuVpf7Lk+XdxRdqvcQZa/YJNLZJUTgA6lXABEBQhmRZeDmQD2cRMop5TI+aBqIv9hbZ79dm1P8ZX7rLEImnzS/GJiecBIeZ6dKKl22nxFmZFGPyMHrAURyf6SUAKIIBee1hsiGfpbZrhZWnc6/DoDiccKzjvzzSmKeyqz/NcrQTCPTLlpD9adbwvRft4VvrGiKSikrj15TlB7zwoYQAAj2Y/GfaFKnA+uBiIzSTVEZDX9FbD5TqV6hj1OvMMwPTHhLFxybqFZICQmcGYMpC8/4AmEZB6ZFGk4dDn/jwG51NJjQpK14YjMMegSvzkKIfuvsoFWvzeUAqBfFYyr+Bvk2whLdALgAAAAAAElFTkSuQmCC"/></a>&nbsp;
			Vous pouvez créer, modifier et activer plusieurs types de module (session sécurisée, recherche de doublon, tags principaux...) %s
		</div>
	</div>', $text);	

