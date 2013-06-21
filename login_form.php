<!DOCTYPE html>
<html lang="fr">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Shaarlo</title><meta charset="utf-8"><meta name="description" content="">
</head>
<body>
<style>
	.form_content{box-shadow:0 1px 2px black;border-radius:3px;margin:auto; border:2px solid #999;font-family: 'georgia'; font-size:18px;background-color:#CCC;width:200px;padding:20px;text-shadow:0 1px 1px white;color:black;}
	h1{font-size:22px; }
	label{display:block;}
	input{width:100%;}
	input[type=checkbox]+label{display:inline;width:auto;cursor:pointer;}
	input[type=checkbox]{display:inline;width:auto;}
	input{border-radius:3px;width:100%;}
	#login, #pass{border:1px solid #999;padding:3px;}
	#login:focus{text-shadow:0 0 3px green;}
	#pass:focus{text-shadow:0 0 3px red;}
	@media (max-width:600px){
		.form_content{width:90%;font-size:26px!important;}
		input{font-size:26px!important;}
	}
	@viewport{
    width: device-width;
    zoom:1;
	}
</style>

<div class="form_content">
	<form action='' method='post' name='' >
		<p class="logo"> </p>
		<?php 
			global $DATA_DIR;
			$passFile = sprintf('%s/%s', $DATA_DIR, 'pass.php');
			if(file_exists($passFile)){
				?><h1>Identifiez-vous</h1><?php
			}else{
				?><h1>Créez votre mot de passe</h1><?php 
			} ?>
			<hr/>
			<label for='login'>Login </label>
			<input type='text' name='login' id='login' required="required"/>
			<br/>
			<hr/>
		<label for='pass'>Passe </label>
		<input type='password' name='pass' id='pass'  required="required"/>	

		<hr/>
		<input id="cookie" type="checkbox" value="cookie" name="cookie"/><label for="cookie">Rester connecté</label>
		<hr/>
		<input type='submit' value='Connexion'/>	
	</form>
</div>
</body>
</html>
