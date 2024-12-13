<?php
	/**
	 * Project: PhpStorm.
	 * Author:  InCubics
	 * Date:    27/11/2023
	 * File:    cleanRequests.php
	 *
	 * @package core
	 */
	
	namespace core;
	class cleanRequests
	{
		public $cleanGetArray =  [];
		public $cleanPostArray =  [];
		
		public $cleanSubmitted = [];
		public $method = null;

		public function build()
		{
			$this->method = strtolower($_SERVER['REQUEST_METHOD']);
			
			/// cleaning GET
			$cleanArray = $this->cleanSubmittedArray($_GET);
			if(is_array($cleanArray)) {
				$this->cleanGetArray=  $cleanArray;
			}
			else {
				$this->cleanGetArray =[];
			}
		if( (   $this->method == 'post'
				|| $this->method == 'put'
				|| $this->method == 'patch')
				&& $this->method != 'get'
				&& $cleanSubmitted = $this->buildPostPutPatchOrDelete() )   {
				return $cleanSubmitted;
			}
		}
		
		
		public function buildPostPutPatchOrDelete()
		{
			if(strtolower($this->method) == 'post')
			{
				if(!empty ($_POST['_method']) &&
					( strtolower($_POST['_method']) != 'put'
						|| strtolower($_POST['_method']) != 'patch'
						|| strtolower($_POST['_method']) != 'delete')
				)
				{   // setting real method, eq: put, patch or delete -- skip post
					$this->method = strtolower($_POST['_method']);
				}
				
				if($this->method == 'post')
				{   // cleaning real POST
					if($cleanArray = $this->cleanSubmittedArray($_POST)) {
						return   $cleanArray;
					}
					else {
						return  [];
					}
				}
				return false;
			}
			
			if($this->method == 'put' || $this->method == 'patch' || $this->method == 'delete')
			{

				$dirtySubmittedArray = $this->createPutPatchDelete();
				$cleanSubmittedArray = $this->cleanSubmittedArray($dirtySubmittedArray);

				if(is_array($cleanSubmittedArray)) {

						$methodSmall =  strtolower($this->method);
						if($cleanArray = $this->cleanSubmittedArray($cleanSubmittedArray)) {
							return $cleanArray;
						}
						else {
							return  [];
						}
				 }
				return false;
			}
		}
		
		
		private function cleanSubmittedArray($arrayDirty)
		{
			$cleanArray = [];
			foreach($arrayDirty as $key=>$value) {
				if( ! $cleanArray[$key] = strip_tags(htmlspecialchars($value)))   {    // clean post-input
				}
			}
			return $cleanArray;
		}
		
		
		private function createPutPatchDelete()
		{
			$array = [];
			if ($this->method == 'put' || $this->method == 'patch'|| $this->method == 'delete')
			{
				// If is:  Content-Type `multipart/form-data`, use a custome parser
				if(stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data')!==false)
				{
					// Een tijdelijke oplossing om multipart/form-data te verwerken
					$rawData=file_get_contents('php://input');
					// Splits de data in delen op basis van boundary
					preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
					$boundary=$matches[1] ?? '';
					$submittedArrayData=[];
					if($boundary)
					{
						$parts=preg_split('/-+'.preg_quote($boundary, '/').'/', $rawData);
						foreach($parts as $part)
						{
							if(empty(trim($part)))
							{
								continue;
							}
							// search fieldname
							if(preg_match('/name="([^"]*)"/', $part, $nameMatches))
							{
								$name=$nameMatches[1];
								// search value
								$value=trim(substr($part, strpos($part, "\r\n\r\n")+4));
								$value=rtrim($value, "\r\n");
								$array[$name]=$value;
							}
						}
					}
				}
				else
				{   // other formats, such as JSON or URL-encoded
					parse_str(file_get_contents('php://input'), $array);
				}
			}
			return $array;
		}
	}
