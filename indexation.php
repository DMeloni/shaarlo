<?php
// Indexe dans ES chaque lien
require_once 'config.php';

// Creation du mapping


function sendContentToES() {
    $ch = curl_init();
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_AUTOREFERER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_ENCODING => 'gzip',
        //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
        //CURLOPT_SSL_CIPHER_LIST => 'RC4-SHA',            
        CURLOPT_HTTPHEADER => array(
        'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3',
        'Accept-Encoding: gzip, deflate',
        'DNT: 1',
        'Connection: keep-alive',
        ),
    );

    if($sslVersion != null) {
        $options[CURLOPT_SSLVERSION] = $sslVersion;
    }

    curl_setopt_array($ch, $options);

    //print_r(curl_errno($ch));

    $data = curl_exec($ch);
    //print_r(curl_getinfo($ch)); 
    //print_r(curl_error($ch)); 
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
}





