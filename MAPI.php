<?php
 /**
  * class for work with MyStat API
  */
 class MyStat
 {
 	function __construct()
 	{
 		try
		{
           curl_version(); // just for test curl
		}
		catch (Exception $e)
		{
            throw new Exception("Error with curl. Curl exception :" . $e->getMessage()); // if curl not installed      
		}       
 	}

	/**
	* Send a POST requst using cURL
	* @param string $url to request
	* @param array $post values to Send
	* @param array $options for cURL
	* @return string
	*/
	function curl_post($url, array $post = NULL, array $options = array())
	{
    	$defaults = array(
        	CURLOPT_POST => 1,
        	CURLOPT_HEADER => 0,
        	CURLOPT_URL => $url,
        	CURLOPT_FRESH_CONNECT => 1,
        	CURLOPT_RETURNTRANSFER => 1,
        	CURLOPT_FORBID_REUSE => 1,
        	CURLOPT_TIMEOUT => 4,
			CURLOPT_POSTFIELDS => json_encode($post,true),
			CURLOPT_SSL_VERIFYHOST=> FALSE,
			CURLOPT_SSL_VERIFYPEER=> FALSE,
    	);

    	$ch = curl_init();
    	curl_setopt_array($ch, ($options + $defaults));
    	if( ! $result = curl_exec($ch))
    	{
    	    trigger_error(curl_error($ch));
    	}
    	curl_close($ch);
    	return $result;
	}
	
	/**
	* Send a GET requst using cURL
	* @param string $url to request
	* @param array $get values to send
	* @param array $options for cURL
	* @return string
	*/
	function curl_get($url, array $get = NULL, array $options = array())
	{   
    	$defaults = array(
        	CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get),
        	CURLOPT_HEADER => 0,
        	CURLOPT_RETURNTRANSFER => TRUE,
        	CURLOPT_TIMEOUT => 4
    	);
	   
    	$ch = curl_init();
    	curl_setopt_array($ch, ($options + $defaults));
    	if( ! $result = curl_exec($ch))
    	{
        	trigger_error(curl_error($ch));
    	}
    	curl_close($ch);
    	return $result;
	} 
	/**
	* Get token from login and password
	* @param string $Pass to send to MyStat
	* @param string $Login to send to MyStat
	* @param int $City_id to send to MyStat (can be get from https://msapi.itstep.org/api/v1/public/cities)
	* @return string 
	*/
	function Login ($Pass , $Login ,$City_id = 31)
	{
	//$this->log->Log(100,'started login function.');
	$data = array(
		'application_key' => '6a56a5df2667e65aab73ce76d1dd737f7d1faef9c52e8b8c55ac75f565d8e8a6',
		'id_city' => null,
		'password' => $Pass,
		'username' => $Login
		);
	$url = 'https://msapi.itstep.org/api/v1/auth/login';
	$options = array(CURLOPT_HTTPHEADER => array('Authorization: Bearer null','Content-Type: application/json'));
	$result = $this->curl_post($url,$data,$options);
	$result = json_decode($result);
	//echo var_export($result);
	$result = (array)$result;
	//echo var_export($result);
	//$this->log->Log(100,'results of login: '.var_export($result,true).'Encoding of string : ' . mb_detect_encoding((array)$result[0]['message'][0]));
	if (array_key_exists('access_token' , $result) == true)
	{
		return $result['access_token'];
	}
	else
	{
			throw new Exception($result['message']);
	}
	}
	/** 
	* Gets info about user 
	* @param string Token for MyStat API
	* @return mixed All data.
	*https://msapi.itstep.org/api/v1/settings/user-info
	**/
	function GetUserInfo ($Token)
	{
		$data = array();
		$url = 'https://msapi.itstep.org/api/v1/settings/user-info';
		$options = array(CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $Token,'Content-Type: application/json'));
		$result = $this->curl_get($url,$data,$options);
		$result = json_decode($result);
		//echo var_export($result);
		if (array_key_exists('message' , $result) == false)
		{
			return $result;
		}
		else
		{
			throw new Exception($result->message);
		}
		}
	
	/**
	* Get count of homeworks from token
	* @param string $Token token to pass to MyStat
	* @return array 
	*Number in array - meaning in mystat (not correct to 2019.06 will fix when I go to academy)(14.09.19 fixed some values like completed , gived and on check I will fix all values "soon")
	*0 - Completed
	*1 - Gived by teacher
	*2 - Overdue 
	*3 - On check
	*4 - Deleted by teacher 
	*5 - All
	*/
	function HomeWorkcount($Token)
	{
	$data = array();
	$url = 'https://msapi.itstep.org/api/v1/count/homework';
	$options = array(CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $Token,'Content-Type: application/json'));
	$result = $this->curl_get($url,$data,$options);
	$result = json_decode($result);
	echo var_export($result);
	if (array_key_exists('message' , $result) == false)
	{
		$out = array();
		$out[0] = $result[0]->counter;
		$out[1] = $result[1]->counter; // not proofed 
		$out[2] = $result[2]->counter; // not proofed
		$out[3] = $result[3]->counter; // not proofed
		$out[4] = $result[4]->counter; // not proofed
		$out[5] = $result[5]->counter; 
		return $out;
	}
	else
	{
		throw new Exception($result->message);
	}
	}

	/**
	* Get place of user in class and form by token
	* @param string $Token to pass to MyStat
	* @return array 
	*Number in array - place in 
	*0 - place in class
	*1 - place in form
	*/
	function GetPlace($Token='')
	{
	$data = array();
	$url = 'https://msapi.itstep.org/api/v1/dashboard/progress/leader-group-points';
	$options = array(CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $Token,'Content-Type: application/json'));
	$result = $this->curl_get($url,$data,$options);
	$result = json_decode($result);
	if (array_key_exists('message' , $result) == false)
	{
        $Out[] = $result->studentPosition;
		$url = 'https://msapi.itstep.org/api/v1/dashboard/progress/leader-stream-points';
		$result = $this->curl_get($url,$data,$options);
		$result = json_decode($result);
		if (array_key_exists('message' , $result) == false)
		{
			$Out[] = $result->studentPosition;
			return $Out;
		}
		else
		{
				throw new Exception($result->message);
		}
	}
	else
	{
		throw new Exception($result->message);
	}
	}
	/*
	*@param string $Token to pass to MyStat
	*@return array  leaders of group
	*
	*/
	function GetLeaderboard($Token='')
	{
	$data = array();
	$url = 'https://msapi.itstep.org/api/v1/dashboard/progress/leader-group';
	$options = array(CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $Token,'Content-Type: application/json'));
	$result = $this->curl_get($url,$data,$options);
	$result = json_decode($result);
	if (array_key_exists('message' , $result) == false)
	{
		foreach ($result as $key => $value){
			//echo "Key : " . $key . ' value: ' . $value->id;
			if($value->photo_path == null)
			{
				$value->photo_path = 'https://mystat.itstep.org/assets/resources/profile.svg?v=67734166db7ccc381ee701f2a11ac7db'; // this is default profile photo 
			}
		}
		return $result;
	}
	else
	{
		throw new Exception($result->message);
	}
	}
	/*
	*@param string $Token to pass to MyStat
	*@return array  leaders of stream
	*
	*/
	function GetLeaderboardStream($Token='')
	{
	$data = array();
	$url = 'https://msapi.itstep.org/api/v1/dashboard/progress/leader-stream';
	$options = array(CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $Token,'Content-Type: application/json'));
	$result = $this->curl_get($url,$data,$options);
	$result = json_decode($result);
	if (array_key_exists('message' , $result) == false)
	{
		foreach ($result as $key => $value){
			//echo "Key : " . $key . ' value: ' . $value->id;
			if($value->photo_path == null)
			{
				$value->photo_path = 'https://mystat.itstep.org/assets/resources/profile.svg?v=67734166db7ccc381ee701f2a11ac7db'; // this is default profile photo 
			}
		}
		return $result;
	}
	else
	{
		throw new Exception($result->message);
	}
	}
 }
?>