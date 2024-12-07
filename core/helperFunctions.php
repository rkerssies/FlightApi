<?php
	/**
	 * Project: PhpStorm.
	 * Author:  InCubics
	 * Date:    24/11/2023
	 * File:    helperFunctions.php
	 */


	function dd($var=null)
	{
		echo '('.gettype($var).'):'."&emsp;";
		
		if(is_array($var) || is_object($var))
		{
			echo '<pre>';
			print_r($var);
			echo '</pre>';
			die;
		}
		elseif(is_numeric($var) )
		{
			echo $var;
			die;
		}
		elseif(is_bool($var) && $var === false )
		{
			die("false");
		}
		elseif(is_bool($var) && $var === true)
		{
			die("true");
		}
		elseif(empty($var))
		{
			die('NULL');
		}
		else
		{
			die($var);
		}
		die('dd - value of parameter: '.$var);
	}
	
	
	function createPutPatchDelete() {
		$array = [];
		
		if ( strtoupper($_SERVER['REQUEST_METHOD']) == 'PUT' || strtoupper($_SERVER['REQUEST_METHOD']) == 'PUT'
			|| strtoupper($_SERVER['REQUEST_METHOD']) == 'PATCH'|| strtoupper($_SERVER['REQUEST_METHOD']) == 'DELETE') {
			$method = '_'.strtoupper($_SERVER['REQUEST_METHOD']);
			
			parse_str(file_get_contents('php://input'), $dataMethod);
			if(!empty($dataMethod))
			{
				$key = key($dataMethod);
				$dataMethod = explode('----------------------------',$dataMethod[$key]);
				foreach($dataMethod as $value) {
					$parts = explode('"', $value);
					if(isset($parts[1]) && isset($parts[2])) {
						$key = preg_replace('/\s+/','',($parts[1]));
						$value = str_replace("\t", '', $parts[2]);
						$value = str_replace("\n", '', $value);
						$value = str_replace("\r", '', $value);
						$array[$key] = trim(strip_tags(htmlspecialchars($value)), ' ');
					}
				}
			}
		}
		return $array;
	}

	
	function getBearer($url, $config)
	{
	// !!!!   include class JWT  namespace
		// $this->Key  === Key::class
	
//		$jwtToken = get_headers($url, true)['Authorization'];
//
//
//		$leeWayTime         = 120; // seconds   /// FROM CONFIG !!!
//		$encryptionType     = 'HS256';              // or  HS512  -> GET FROM    config
//
//		//$this->config->token_key, 'HS256');
//		JWT::$leeway = $leeWayTime; // $leeway in seconds
//		$decoded = JWT::decode($jwtToken, new Key($config->token_key, $encryptionType));
//
//		print_r($decoded);
		die(' decoding and checking Permissions from JWT-token ');
		
	}
	
	
	function check_diff_multiArray($array1, $array2){
		$result = array();
		foreach($array1 as $key => $val) {
			if(isset($array2[$key])){
				if(is_array($val) && $array2[$key]){
					$result[$key] = check_diff_multi($val, $array2[$key]);
				}
			} else {
				$result[$key] = $val;
			}
		}
		
		return $result;
	}
