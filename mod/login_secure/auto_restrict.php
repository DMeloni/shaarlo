<?php
  /**
	 * @author bronco@warriordudimanche.com / www.warriordudimanche.net
	 * @copyright open source and free to adapt (keep me aware !)
	 * @version 2.0
	 *   
	 * Verrouille l'accès à une page
	 * Il suffit d'inclure ce fichier pour bloquer l'accès
	 * il gère seul l'expiration de session, la connexion,
	 * la déconnexion.
	 *
	 * Améliorations eventuelles:
	 * ajouter compteur de tentatives sur ban id. 
	 * ajouter la sécurisation du $_POST (en cas d'usage d'une base de donnees)
	 * 
	*/	
	session_start();
	include 'config.php';
	
	/*
	 * Mod filter
	 */
	$modActivedOnPages = array('admin.php', 'boot.php');
	$path = $_SERVER['PHP_SELF'];
	$file = basename ($path);
	if(!in_array($file, $modActivedOnPages)){
		return ;
	}

	global $DATA_DIR, $MOD;
	
	/*
	 * Data dir creation
	 */
	if(!is_dir($DATA_DIR)){
		if(!mkdir($DATA_DIR)){
			return;
		}
	}
	// ------------------------------------------------------------------
	// configuration	
	// ------------------------------------------------------------------
	$auto_restrict['error_msg']='Erreur - impossible de se connecter.';// utilisé si on ne veut pas rediriger
	$auto_restrict['cookie_name']='auto_restrict';// nom du cookie
	$auto_restrict['encryption_key']='abcdef';// clé pour le cryptage de la chaine de vérification
	$auto_restrict['session_expiration_delay']=10;//minutes
	$auto_restrict['cookie_expiration_delay']=360;//days
	$auto_restrict['login']='login'; // caractères alphanum + _ et .
	$auto_restrict['redirect_error']='index.php';// si précisé, pas de message d'erreur
	
	
	// ---------------------------------------------------------------------------------
	// sécurisation du passe: procédure astucieuse de JérômeJ (http://www.olissea.com/)
	$passFile = sprintf('%s/%s', $DATA_DIR, 'pass.php');
	
	if(file_exists($passFile)) include $passFile;
	if(!isset($auto_restrict['pass'])){
		if(isset($_POST['pass'])&&isset($_POST['login'])&&$_POST['pass']!=''&&$_POST['login']!=''){ # Création du fichier pass.php
			$salt = md5(uniqid('', true));
			file_put_contents($passFile, '<?php $auto_restrict["login"]="'.$_POST['login'].'";$auto_restrict["salt"] = '.var_export($salt,true).'; $auto_restrict["pass"] = '.var_export(hash('sha512', $salt.$_POST['pass']),true).'; ?>');
			include 'login_form.php';exit();
		}
		else{ # On affiche un formulaire invitant à rentrer le mdp puis on exit le script
			include 'login_form.php';exit();
		}
	}
	// ---------------------------------------------------------------------------------

	
	// ------------------------------------------------------------------
	
	// ------------------------------------------------------------------
	// gestion de post pour demande de connexion
	// si un utilisateur tente de se loguer, on gère ici
	// ------------------------------------------------------------------	
	if (isset($_POST['login']) && isset($_POST['pass'])){
		log_user($_POST['login'],$_POST['pass']);
		if (isset($_POST['cookie'])){setcookie($auto_restrict['cookie_name'],sha1($_SERVER['HTTP_USER_AGENT']),time()+$auto_restrict['cookie_expiration_delay']*1440);}
	}

	// ------------------------------------------------------------------	
	// si pas de demande de connexion on verifie les vars de session
	// et la duree d'inactivité de la session
	// si probleme,on include un form de login.
	// ------------------------------------------------------------------
	if (!is_ok()){session_destroy();include 'login_form.php';exit();} 
	// ------------------------------------------------------------------
	// demande de deco via la variable get 'deconnexion'
	// ------------------------------------------------------------------	
	if (isset($_GET['deconnexion'])){log_user('dis','connect');}
	// ------------------------------------------------------------------	
	
	
	
		
	
	// ------------------------------------------------------------------	
	// fonctions de cryptage 
	// récupérées sur http://www.info-3000.com/phpmysql/cryptagedecryptage.php
	// ------------------------------------------------------------------
	function GenerationCle($Texte,$CleDEncryptage) 
	  { 
	  $CleDEncryptage = md5($CleDEncryptage); 
	  $Compteur=0; 
	  $VariableTemp = ""; 
	  for ($Ctr=0;$Ctr<strlen($Texte);$Ctr++) 
		{ 
		if ($Compteur==strlen($CleDEncryptage))
		  $Compteur=0; 
		$VariableTemp.= substr($Texte,$Ctr,1) ^ substr($CleDEncryptage,$Compteur,1); 
		$Compteur++; 
		} 
	  return $VariableTemp; 
	  }
	function Crypte($Texte,$Cle) 
	  { 
	  srand((double)microtime()*1000000); 
	  $CleDEncryptage = md5(rand(0,32000) ); 
	  $Compteur=0; 
	  $VariableTemp = ""; 
	  for ($Ctr=0;$Ctr<strlen($Texte);$Ctr++) 
		{ 
		if ($Compteur==strlen($CleDEncryptage)) 
		  $Compteur=0; 
		$VariableTemp.= substr($CleDEncryptage,$Compteur,1).(substr($Texte,$Ctr,1) ^ substr($CleDEncryptage,$Compteur,1) ); 
		$Compteur++;
		} 
	  return base64_encode(GenerationCle($VariableTemp,$Cle) );
	  }
	function Decrypte($Texte,$Cle) 
	  { 
	  $Texte = GenerationCle(base64_decode($Texte),$Cle);
	  $VariableTemp = ""; 
	  for ($Ctr=0;$Ctr<strlen($Texte);$Ctr++) 
		{ 
		$md5 = substr($Texte,$Ctr,1); 
		$Ctr++; 
		$VariableTemp.= (substr($Texte,$Ctr,1) ^ $md5); 
		} 
	  return $VariableTemp; 
	  }
	  


//------------------------------------------------------------------------------------------

	function id_user(){
		// retourne une chaine identifiant l'utilisateur que l'on comparera par la suite
		// cette chaine cryptée contient les variables utiles sérialisées		
		$id=array();
		$id['REMOTE_ADDR']=$_SERVER['REMOTE_ADDR'];
		$id['HTTP_USER_AGENT']=$_SERVER['HTTP_USER_AGENT'];
		$id['session_id']=session_id();
		$id=serialize($id);
		return $id;	
	}

	

	function is_ok(){
		// vérifie et compare les variables de session
		// en cas de problème on sort/redirige en détruisant la session
		global $auto_restrict;
		$expired=false;
		if (isset($_COOKIE[$auto_restrict['cookie_name']])&&$_COOKIE[$auto_restrict['cookie_name']]==sha1($_SERVER['HTTP_USER_AGENT'])){return true;}
		if (!isset($_SESSION['id_user'])){return false;}
		if ($_SESSION['expire']<time()){$expired=true;}
				$sid=Decrypte($_SESSION['id_user'],$auto_restrict['encryption_key']);
		$id=id_user();
		if ($sid!=$id || $expired==true){// problème
			return false;
		}else{ // tout va bien
			//on redonne un délai à la session
			$_SESSION['expire']=time()+(60*$auto_restrict['session_expiration_delay']);
			return true;
		}
	}
	
	
	function log_user($login_donne,$pass_donne){
		//cree les variables de session
		global $auto_restrict;
		if ($auto_restrict['login']==$login_donne && $auto_restrict['pass']==hash('sha512', $auto_restrict["salt"].$pass_donne)){
			$_SESSION['id_user']=Crypte(id_user(),$auto_restrict['encryption_key']);
			$_SESSION['login']=$auto_restrict['login'];	
			$_SESSION['expire']=time()+(60*$auto_restrict['session_expiration_delay']);
			return true;
		}else{
			exit_redirect();
			return false;
		}
	}

	function redirect_to($page){header('Location: '.$page); }
	function exit_redirect(){
		global $auto_restrict;
		@session_unset();
		@session_destroy();
		setcookie($auto_restrict['cookie_name'],'',time()+1);
		if ($auto_restrict['redirect_error']&&$auto_restrict['redirect_error']!=''){//tester sans la deuxième condition
				redirect_to($auto_restrict['redirect_error']);
		}else{exit($auto_restrict['error_msg']);}
	}
	
	/*
	 * View on admin.php
	 */
	$nbJour = (!isset($_COOKIE[$auto_restrict['cookie_name']])) ? $auto_restrict['session_expiration_delay'].' min' : $auto_restrict['cookie_expiration_delay'].' jour(s)';
	$MOD['admin.php_top'] .= sprintf('<div class="article shaarli-youm-org">
				<h2 class="article-title ">
				<a title="Go to original place" href="">Info sur la session en cours</a>
				</h2>
				<div class="article-content">
					Durée d\'inactivité avant déconnexion:<em>%s</em>&nbsp;
					<a href="admin.php" title="Recharger cette page" ><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAYAAADEtGw7AAAEO0lEQVR4Xs3TcUyUZRwH8O/z3vvee3ceB3cHiCAQeiKRliitTDQtpwOTxOklUxR3UUA1RSmrLWXoDET+UFqlbpoOqMbMwkQiBs4aEqYxEBHE4ADl6Di4Ozjujnvvfaq5+c+hxvynz/b89du+e/bdvvhfKDhejU+P1aDo+Hmm49RLoGfwUAymYJwEAYpQad+oeu3eW7lRW3oasHih7smDRUYGIuEC+qzS7BG333bG3sbPWH4Qk2HxEHd6TAhQsiS/vF9rNDl0tjFnRG3jn5xGJQ+yOGULQGRPc5KwK92axIuYBMEk3v38NlbFqZTVTeY1t4zWTbaxiUVuwavxil4JQ6joJRIZw8kZrT+qtTJrJqXU+MP+5EcHn7tuASshmt87x3Ou3x5722iyBbrcTgICUIaAEvF+gRIWCgXvDFSJhzUKW8GERzoOf4rKN1/x7fh0iw00QCpvHxGzTS4xx8sJfrxcMFLRcU3w2K4S1tPDaKYJVKsE1cgxrpTKhyVs2sCIc+Fo1HIMXG307bjohgMXG4yYOz/kxcEhW+qQqbdhvLf7e7fZ0uwcHjdLBI83YnXiRrNStU+gYKngFkT7cP/wvd6fbKaOIY8mAdKuG/Dx8R925LWOctvLr320Ou+bfc+s3DYTAJGwHHIuD6LWOMomVfYXRJ+85Yo49Etr6M7S4sA1uQnyiHglHmV3XTeyKpqkyZ8ci49duTlAt2T9g5v+q0bsrmhWvfzZpcKw90sPaNfteV4eukBBKcVj6XcdwaupOeAVvh9YnLYXKTtKJPPW7QxW6BL4Ofvb8URyG4dQdXeM3XnJGLP26IV5IQlbmPjsww/uVxruoaa+h+w4eolNyTuP9EM1eKTIMjPKWgfk2fWmRa9XD+YtOdvd9GzhuUwAZNaGd/CvsiYLLt+hzMELHcvSi79bykcvJ0m5Jb7LO9HYjRa7AtF+bkWX3RX3bSdSBl1IsnpI9IRjdIAOWzpi6yjt++BDNNxsI813HdquETHZbJ/YYBkxF9d11NOMxM2+wT+2dyGGryJVd1O3WdyaPVaXGOb2UBYiAesVvCrV3Oem32hTxBoMyhO/8XN4nltKiGuhY3DgrKW98VrBLhtuVpf7Lk+XdxRdqvcQZa/YJNLZJUTgA6lXABEBQhmRZeDmQD2cRMop5TI+aBqIv9hbZ79dm1P8ZX7rLEImnzS/GJiecBIeZ6dKKl22nxFmZFGPyMHrAURyf6SUAKIIBee1hsiGfpbZrhZWnc6/DoDiccKzjvzzSmKeyqz/NcrQTCPTLlpD9adbwvRft4VvrGiKSikrj15TlB7zwoYQAAj2Y/GfaFKnA+uBiIzSTVEZDX9FbD5TqV6hj1OvMMwPTHhLFxybqFZICQmcGYMpC8/4AmEZB6ZFGk4dDn/jwG51NJjQpK14YjMMegSvzkKIfuvsoFWvzeUAqBfFYyr+Bvk2whLdALgAAAAAAElFTkSuQmCC"/></a>&nbsp;
					<a href="?deconnexion=ok" class="deco" title="DECONNEXION"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAWCAYAAADEtGw7AAAFCUlEQVR4XoWVa2wU1xmGn5ld79W7a4xstDh4G6jBik1cx1sugoRc3FAKUiCK8qNQ5aJIEUT5UalARSFtqrYWQSVKRaoqRhCqVApSCVISAkooCkQhwXGgKTY3Y+PY4CveZe317s5tvx6PhGpFbvtJj+boO0fPeUc6M0cTEWaqt6uSGlADLHWBJUAeuDCNzmdvtTvMUDOKlbQBeAeoD2pCyAOxsghF22EimyNfhIKiiNYOPKfkHXyn9O8IdcV2hLY5QU9988ubWPteKz/+6gQrvjjOg18e50enjvD4/j08tHEdlV6SCF+rIDsU3hkTuxNw3K/TvKj2e9zX+kd8VXOxb49RLBiggdg2UjCRooPm81Po6qZn95+43jtCQTgJrFbpiwDTd9kW0Ghe9tN1JH6/k6JpYg2PUjKnEr1k+jJwLIvcpatuf8GuXxD+ywH+2X6l2SiyFdgNgIhwcG5T/cF4k3G++UlxDEOMwWGxs1mZKse2Zbi7Ry6f/sxFjd3eVE3035TRw0dl6K1DcrZupUw5lKtBREANPIqvjiWSkrvapaRDYo2lXGlqaEjeb3lNTmzbKW2vtsjFlr3u8/PX98lYX78rH/5Xhwzua5XuLVvlWOIBUa6LCk0H6nQkWbfxCfe17VQGb/kssuk0/2jZQyKToy4SY/7ieuKLaojrXqqzeQb2/5Xs6G0qF9eRK4/hi1cSjwYB6oEaXYPGoA6xVSvIdV7GWzEbgLOH/sYCC2K2A5pG+fq1LuOGgZMZJ5IrMPTuEQBKG+8nn0rji0UIaYIGS706NIZ08N87j7G/f0C46QdYhQL5M2eJzq7ANjPokVLl1gAYzk64iQE0tYGt1s5KJOgZGSEaCBD2gGErsUejsTQcxE7dwRoaweP3c7vjEpGCiXMnA0CxLMbdsicnVX+CuzXZ20esdiFmwO9uHtYho7HEq2sEPSUect90YnRdByCgRL7JHLblAOCZ+I+omMtjuxvi4o9GAFSYTqKZHKChnHndA225iRzGt/0Yvf1M9t8kdk8Vhm3jpDNu6mI2N12M20+lEcsmMDdOpq8ffTTlJjYVHrigK/s5U0BMy/2axtu/AaB6ywuI7SCWg9k/gGOa2IbJ5M0B3L4jzNn8PAC32r4mGgih+QNYbmIuaEfnNS0Eri5+fCWFaz04fh81h1sJzCrj6q9+h/XJGQDSc2bzbSFHxUiKKn+QsjWPcc+r2ymk73B47VM0UIJu2vSO3kGgQSWmS9Fx69wFfFVxPIZB9+43AKj9wy7iLTuJLP8hFbbwgCdA7cMPUb37Feb99pcAfPqbFhJFnUBpKRnDRNNoV75ORIQPq5saFMb5VevlypPPSufD6+XMiz+X8YFB+S/lzh19brO8l3xErmx4Rs4vXyNTDkW9iOCKFXyUaNr+8feXyaU1G6Vr00ty+Scb5bPVT8sXr/9Zrn98SjI3b03hjj/f+6YcWLlaTj+2Qbp+9pJcUms/qVkuyrHjrs877ce8p2hZzQPdN5ornWrCiSrihkn+dBuDx05xzTKYtG3CJSVEQ2GWJRbgD4XIqtM0eqMPxzRP6vDajDfIyXuTXmAb8OtIZYWv/L5FlEQjaD4fOA4g4PEihoE1kSV9+RrjwyMm8Aqwp/lGe/F/Xk2n5ifrgYPoWtIXDOELh/BHy8CrY6RSmNlJzHweRDqATY/2uGf0/995AKfnJz1AHdA4jSDQBpxzga5VPe0zCv4NGfzEC3rbCQIAAAAASUVORK5CYII="/></a>
				</div>
			</div>', $nbJour);
	
?>
