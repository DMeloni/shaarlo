<?php

class webshots
{
	
	private $api_url;																			// api url
	private $profile_secret_code;															// user profile secret code (will be available from profile page.)
	private $profile_secret_key;															// user profile secret key (will be available from profile page.)
	
	function __construct()
	{
		$this->api_url = 'http://ns3010509.ip-46-105-120.eu:85/shot.php';
		$this->profile_secret_code = ''; 	// user profile secret code
		$this->profile_secret_key = '';		// user profile secret key
	}
	
	function post_to_url($url, $data=array())
	{       
               
		$fields = http_build_query($data);
		/*foreach($data as $key => $value) {
			$fields .= $key . '=' . $value . '&';
		}
		$fields = rtrim($fields, '&');*/
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		//curl_setopt($c, CURLOPT_GET, count($data));
		//curl_setopt($c, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($c);
		curl_close($c);
		return $result;
	}
	
	function url_to_image($webpage_url, $img_path)
	{
		// $webpage_url = 'http://www.yahoo.com/';											// webpage url for which image is to be created
		// $img_path = '#full absolute path for png image file to be created#'; 	// e.g.: linux: /var/www/images/img.png OR windows: d:\www\images\ (path where you want to store image)
		$url = $this->api_url."/?url=".urlencode($webpage_url); 													// api url with random unique time based value as parameter to prevent cached response
		$params = array();
		//
		$img = $this->post_to_url($url, $params);
		// print_r($img); exit;
		if(strpos($img, '"er":"error: #') === false) {
			@ file_put_contents($img_path, $img);
			// your code to further use image as per your req. will be here
			return true;
		}
		return false;
	}

}
?>
