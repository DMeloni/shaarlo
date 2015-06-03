<?php

class webshots
{
	
	private $api_url;																			// api url
	private $profile_secret_code;															// user profile secret code (will be available from profile page.)
	private $profile_secret_key;															// user profile secret key (will be available from profile page.)
	
	function __construct()
	{
		//$this->api_url = 'http://www.plsein.tk/api/webshots';
                $this->api_url = 'screen.microweber.com/shot.php';
               
		//$this->profile_secret_code = 'sinedchryser@gmail.com'; 	// user profile secret code
		//$this->profile_secret_key = '30c8b4e7dc8508f80ca41ec2b17bec9d93c9bff29dcd8110e2d381092f331957';		// user profile secret key
		$this->profile_secret_code = ''; 	// user profile secret code
		$this->profile_secret_key = '';		// user profile secret key
	}
	
	function post_to_url($url)
	{
                //echo $url; 
		$fields = http_build_query();
		/*foreach($data as $key => $value) {
			$fields .= $key . '=' . $value . '&';
		}
		$fields = rtrim($fields, '&');*/
		$c = curl_init($url);
                
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
                curl_setopt_array($c, $options);
                
		$result = curl_exec($c);
                //print_r(curl_error($c)); 
		curl_close($c);
		return $result;
	}
	
	function url_to_image($webpage_url, $img_path)
	{
		$img = $this->post_to_url(sprintf('%s?url=%s', $this->api_url, urlencode($webpage_url)));

		if($img !== false) {
			@ file_put_contents($img_path, $img);
			// your code to further use image as per your req. will be here
			return true;
		}
		return false;
	}

}
?>
