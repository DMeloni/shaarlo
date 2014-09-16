<?php
/*
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

include_once 'config.php';

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
				<a href="admin.php" title="Recharger cette page" class="refreshButton">&#10227;</a>&nbsp;
				<a href="?deconnexion=ok" class="deco disconnectButton" title="DECONNEXION">&#9021;</a>
				Durée d\'inactivité avant déconnexion:<em>%s</em>&nbsp;
			</div>
		</div>', $nbJour);
	
