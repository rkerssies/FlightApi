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


	function make_hash($planetext_password)
	{
		return password_hash($planetext_password, PASSWORD_DEFAULT);
	}

	function check_hash($inputPassword, $hashedPasswordFromDB)
	{
		return password_verify($inputPassword, $hashedPasswordFromDB);
	}

	function make_crypt($planetext_password, $hashString='')
	{
		return crypt($planetext_password, '$6$'.base64_encode($planetext_password));
	}

	function check_crypt($planetext_password, $hashed_password)
	{
		if (crypt($planetext_password, '$6$'.base64_encode($planetext_password)) == $hashed_password) 
		{ 
			return true;
		  } 
		return false;
	}
