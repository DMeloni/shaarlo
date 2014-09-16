<?php
/**
    https://stackoverflow.com/questions/3422759/php-aes-encrypt-decrypt
    
    $password = "myPassword_!";
    $messageClear = "Secret message";

    // 32 byte binary blob
    $aes256Key = hash("SHA256", $password, true);

*/

function fnEncrypt($sValue, $sSecretKey) {
    return rtrim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $sSecretKey, $sValue, MCRYPT_MODE_CBC)), "\0\3");
}

function fnDecrypt($sValue, $sSecretKey) {
    return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $sSecretKey, base64_decode($sValue), MCRYPT_MODE_CBC), "\0\3");
}
