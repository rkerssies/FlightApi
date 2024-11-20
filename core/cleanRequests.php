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
		
		public $cleanPutPatchDeleteArray = [];
		public $method = null;
		public $request = [];
		public function build()
		{
			$this->request = (object) $this->request;
			
			if( !  $this->cleanGet())      {
				return false;
			}
			elseif( !  $this->cleanPost())     {
				return false;
			}
			if(! $this->buildPutPatchOrDelete()){
				return false;  // only when required, but failed
			}
			$this->method = strtolower($_SERVER['REQUEST_METHOD']);
			return true;
		}
		
		public function cleanGet()
		{
			// clean GET, eq: key and values from:    url/path?key=value
			$array = [];
			if(!empty($_GET)){
				foreach($_GET as $key => $value)        {
					if(! $array[$key] = strip_tags(htmlspecialchars($value)))  { // clean get-input
						return false;
					}
				}
			}
			$this->cleanGetArray = $array;
			return true;
		}
		
		public function cleanPost()
		{
			//clean POST, eq: submitted keys and values with method POST
			$array = [];
			$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;
			if(!empty($_POST))      {
				foreach($_POST as $key=>$value) {
					if( ! $array[$key] = strip_tags(htmlspecialchars($value)))   {    // clean post-input
						return false;
					}
				}
			}
			$this->cleanPostArray = $array;
			return true;
		}
		
		public function buildPutPatchOrDelete()
		{
			// create global PUT, PATCH or DELETE
			if($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'PATCH' || $_SERVER['REQUEST_METHOD'] == 'DELETE')
			{
				$methodArray = createPutPatchDelete();

				if(is_array($methodArray)) {
					$method = '_'.strtoupper($_SERVER['REQUEST_METHOD']);

					if($method == '_DELETE') {
						global $_DELETE;
						$_DELETE = [];
						$methodSmall = 'delete';
						$this->request->delete = (object) [];
					}
					elseif($method == '_PUT'){
						global $_PUT;
						$_PUT = [];
						$methodSmall = 'put';
						$this->request->put = (object) [];
					}
					elseif($method == '_PATCH'){
						global $_PATCH;
						$_PATCH = [];
						$methodSmall = 'patch';
						$this->request->patch = (object) [];
					}
					
					
					$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
					$method = $_SERVER['REQUEST_METHOD'];
					$methodArray = [];
					
					if (strpos($contentType, 'application/json') !== false) {
						// Handle JSON payload
						$methodArray = json_decode(file_get_contents("php://input"), true);
					}
					elseif (strpos($contentType, 'multipart/form-data') !== false
						&& ($method === 'PUT' || $method === 'PATCH')) {
						// Handle multipart/form-data
						$boundary = substr($contentType, strpos($contentType, "boundary=") + 9);
						$input = file_get_contents('php://input');
						$blocks = preg_split("/-+$boundary/", $input);
						array_pop($blocks);
						
						foreach ($blocks as $block) {
							if (empty($block)) continue;
							
							if (preg_match('/name="([^"]*)"\s+([^"]*)$/', $block, $matches)) {
								$methodArray[$matches[1]] = trim($matches[2]);
							}
						}
					}
					else {
						// Default handling for other content types
						parse_str(file_get_contents("php://input"), $methodArray);
						$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;
					}
					
					
					$cleanArray = [];
//					$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;
					foreach($methodArray as $key=>$value) {
						if( ! $cleanArray[$key] = strip_tags(htmlspecialchars($value)))   {    // clean post-input
							return false;
						}
					}
					$this->cleanPutPatchDeleteArray = $cleanArray;
					$this->request->$methodSmall = $cleanArray;
					return true;
				}
				else {
					return false;
				}
			}   // NO put, patch or delete submitted
			return true;
		}
		
	}
