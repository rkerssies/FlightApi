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
					}
					elseif($method == '_PUT'){
						global $_PUT;
						$_PUT = [];
					}
					elseif($method == '_PATCH'){
						global $_PATCH;
						$_PATCH = [];
					}
					
					$cleanArray = [];
					foreach($methodArray as $key=>$value) {
						if( ! $cleanArray[$key] = strip_tags(htmlspecialchars($value)))   {    // clean post-input
							return false;
						}
					}
					$$method = $cleanArray;  // optional created $_PUT, $_PATCH or $_DELETE
					$this->cleanPutPatchDeleteArray = $cleanArray;
					return true;
				}
				else {
					return false;
				}
			}   // NO put, patch or delete submitted
			return true;
		}
		
	}
