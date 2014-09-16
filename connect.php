<?php
ini_set('session.save_path', $_SERVER['DOCUMENT_ROOT'].'/sessions');
ini_set('session.use_cookies', 1);       // Use cookies to store session.
ini_set('session.use_only_cookies', 1);  // Force cookies for session (phpsessionID forbidden in URL)
ini_set('session.use_trans_sid', false); // Prevent php to use sessionID in URL if cookies are disabled.
ini_set('session.cookie_domain', '.shaarli.fr');
session_name('shaarli');
session_start();?><html>

    <?php 
    if(!isset($_GET['password'])) {
        $password = '';
    }else{
        $password = $_GET['password'];
    }
     // Returns a token.
    function getToken()
    {
        $rnd = sha1(uniqid('',true).'_'.mt_rand().$GLOBALS['salt']);  // We generate a random string.
        $_SESSION['tokens'][$rnd]=1;  // Store it on the server side.
        return $rnd;
    }   
      if(isset($_GET['pseudo']) ) {
        ?><form action="<?php echo sprintf("http://my.shaarli.fr/%s/", htmlentities($_GET['pseudo']));?>" method="post" name="loginform">
                Login: <input name="login" tabindex="1" type="text" value="<?php echo htmlentities($_GET['pseudo']);?>">&nbsp;&nbsp;&nbsp;
                Password : <input name="password" tabindex="2" type="password" value="<?php echo htmlentities($password);?>">
                <a style="display: inline; background-color: red; color: white;" href="Show">Show</a>
                <input value="Login" class="bigbutton" tabindex="4" type="submit">
                <br>
                <input name="longlastingsession" id="longlastingsession" tabindex="3" type="checkbox">
                <label for="longlastingsession">&nbsp;Stay signed in (Do not check on public computers)</label>
                <input name="token" value="<?php echo getToken();?>" type="hidden">
                <input name="returnurl" value="http://shaarli.fr/index2.php" type="hidden">
            </form>
        </form>
        <script language="JavaScript">
            //document.loginform.submit();
        </script>
        <?php
    }
    
?>


</html>

