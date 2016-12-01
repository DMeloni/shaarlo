<?php
include('phpmailer/class.phpmailer.php');

// Envoie un mail au modérateur
function envoieMailRecuperation($to, $profilId, $key) {
	global $ADMIN_EMAIL, $ADMIN_PASSWORD;

	//error_reporting(1);
    $mail2 = new PHPmailer();
	$mail2->IsSMTP();
	$mail2->CharSet = 'UTF-8';
	$mail2->IsHTML(true);
	$mail2->SMTPAuth = true;
	$mail2->Host='smtp.shaarli.fr';
	$mail2->Port='587';
    //$mail2->Host='ssl0.ovh.net';
	//$mail2->Port='465';

	$mail2->Username = $ADMIN_EMAIL;
	$mail2->Password =  $ADMIN_PASSWORD;
	$mail2->From=$ADMIN_EMAIL;
	$mail2->FromName = 'Shaarli.fr' ;
	$mail2->AddAddress($to);
	$mail2->AddReplyTo($ADMIN_EMAIL);
	//$mail2->SMTPDebug = 2;
        $ip = getClientIp();
	$mail2->Subject = utf8_decode(sprintf('Récupération du profil %s', $profilId));
	$message = "Voici le nouveau mot de passe de votre compte : " . $key;
        $message .= "<p>Demande de l'adresse IP : $ip.</p>";
	$message .= "<p>Note : Modifiez le dès que possible (votre prestataire de messagerie pourrait se connecter à votre compte !).</p>";

	$enveloppe = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	    <head>
	        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	        <title></title>
	        <style></style>
	    </head>
	    <body>
	        %s
	    </body>
	</html>';

	$mail2->Body = utf8_decode(sprintf($enveloppe, $message));
	if(!$mail2->Send()){
		//print($mail2->ErrorInfo); 
		return false;
	}
	$mail2->SmtpClose();
	unset($mail2);
	
	return true;
}

function getClientIp() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
       $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
* Retourne une adresse email obfusquée
*
* @param string $email : machin@truc.com
*
* @return string $emailObfusque : m*****@truc.com
*/
function obfusqueEmail($email) {
    if (!isValidEmail($email)) {
        return '';
    }
    $emailExploded = explode('@', $email);
    $premiereLettre = substr($emailExploded[0], 0, 1);

    return sprintf('%s******@%s', $premiereLettre, $emailExploded[1]);
}

/**
* Indique si une adresse mail contient un @
*
* @param string $email : machin@truc.com
*
* @return bool
*/
function isValidEmail($email) {
	if (strpos($email, ' ') !== false) {
		return false;
	}

    return 1 === substr_count($email, '@');
}


