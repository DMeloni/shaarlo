<?php
    ini_set('session.use_cookies', 1);       // Use cookies to store session.
    ini_set('session.use_only_cookies', 1);  // Force cookies for session (phpsessionID forbidden in URL)
    ini_set('session.use_trans_sid', false); // Prevent php to use sessionID in URL if cookies are disabled.

    session_name('shaarli');
    session_start();
    var_dump($_SESSION);    