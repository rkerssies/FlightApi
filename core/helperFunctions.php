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
