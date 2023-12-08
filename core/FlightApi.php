<?php
	/**
	 * Project: PhpStorm.
	 * Author:  InCubics
	 * Date:    23/11/2023
	 * File:    FlightApi.php
	 *
	 * @package core
	 */
	
	namespace core;
	
	use core\cleanRequests;
	use Flight;
	use RecursiveArrayIterator;
	use core\validation\FormRequests;
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
//	use pdoDB;
	
	
	class FlightApi
	{
		private $config = [];
		private $rolePermissionArray = [];
		
		public $requestValidation = null;
		private $db = null;
		private $jwtAuth = null;    // for jwt-token encode and decode
		private $host = null;
		private $successResponse = false;
		//private $lastInserted = null;
		private $request = [];
		private $response = [];
		private $id = null;
		private $paginate = null;
		private $page = null;
		private $messageResponse = 'Request data NOT FOUND on url';
		private $dataResponse = null;
		private $statusResponse = 404;  //Not found
		
		public function __construct()
		{
			$this->request = (object) $this->request;
			global $_PUT;
			global $_PATCH;
			global $_DELETE;
			$_PUT    = [];
			$_PATCH  = [];
			$_DELETE = [];
			require_once '../vendor/mikecao/flight/flight/Flight.php';
			require_once '../vendor/mikecao/flight/flight/autoload.php';
			include '../vendor/firebase/php-jwt/src/JWT.php';
			include '../vendor/firebase/php-jwt/src/Key.php';
			include '../vendor/firebase/php-jwt/src/JWTExceptionWithPayloadInterface.php';
			include '../vendor/firebase/php-jwt/src/SignatureInvalidException.php';
			include '../vendor/firebase/php-jwt/src/ExpiredException.php';
			include '../vendor/firebase/php-jwt/src/BeforeValidException.php';

			include '../core/jwtAuth.php';
			require_once '../core/helperFunctions.php';
			require_once '../core/cleanRequests.php';
			require_once "../core/pdoDB.php";
			require_once "../core/validation/ValidationPatterns.php";
			require_once "../core/validation/FormRequests.php";

			
			// pdo database-object
			$this->config =  include "../config/app.php";    // get config
			$this->db = (new pdoDB());                      // database object
			
			$pathParts = explode('?',Flight::request()->url);
			$this->host = isset($_SERVER["HTTPS"]) ? 'https'.'://' : 'http' .'://'.$_SERVER['HTTP_HOST'];
			
			// JWT object
			$this->jwtAuth = new jwtAuth($this->config->jwt, $this->db, $this->host.Flight::request()->url);
			
			// some default get-values from request (via Flight)
			$this->id       = Flight::request()->query['id'];   // get-value from URL
			$this->paginate = Flight::request()->query['paginate'];
			$this->page     = Flight::request()->query['page'];
			
			// build response of get and  post AND-OR put, patch or delete
			$cleanRequests = new cleanRequests();
			if(! $cleanRequests->build()) {
				die('FAILED support on cleaning get and/or post AND creating clean globals for put, patch or delete');
			}
			
			$method = $cleanRequests->method;
			$cleanRequests->cleanGet();
			$cleanRequests->cleanPost();
			$this->request  = (object) [];
			$this->response = (object) [];

			
			if($method == 'post' ||$method == 'put' || $method == 'patch'  )
			{   // validation-object, when data is possibly submitted, eq for actions: 'create' and 'edit'
				$validationArray = include('../config/validation.php');
				$table = explode('/',ltrim($pathParts[0], '/'))[0];
				$this->requestValidation = new FormRequests($validationArray, $table);
			}
			
			
			$this->request  = (object) // default response-values, ready to overwrite
			[
				'hostname' => Flight::request()->host,
				'path'      => $pathParts[0],
				'qsa'       => !empty($pathParts[1]) ? '?'.$pathParts[1] : null ,
				'method' => $method,
				$method     => (object)     $cleanRequests->cleanPutPatchDeleteArray,   // overwritten wehn GET or POST
				'get'       => (object)     $cleanRequests->cleanGetArray,
				'post'      => (object)     $cleanRequests->cleanPostArray,
				'values' => [
					'id'       => Flight::request()->query['id'] , //    Flight::request()->query['id'],   // get-value from URL
					'paginate' => Flight::request()->query['paginate'],
					'page'     => Flight::request()->query['page'],
					'action'   => null
				]
			];
			
			$this->response  = (object)  // default response-values, ready to overwrite
			[
				'count'         => 0 ,
				// 'affectedrows' => null,
				'lastinserted'  => null,
				'token_payload'=>null,
				'validation'    => []
			];
		}
		
		public function run()
		{
			// include all Flight-routs organized in several files
			include "../routes/api_auth.php";               // include and run all route-functions
			include "../routes/custom_api.php";             // include and run custom created route-functions
			include "../routes/api.php";                    // include and run all route-functions
			Flight::map('notFound', function(){
				// return 404 json-respons for non-existing url-requests
				$this->error('Not Found', 404);
			});
			
			// create default json-headers
			header("Access-Control-Allow-Origin: ".$this->host );
			header("Created-by: ".'InCubics.net (c)'.date('Y')."-".(date('Y')+1) );
			header("Access-Control-Allow-Methods: *");
			header("Access-Control-Max-Age: 3600");
			header("Content-type: application/json");
			header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authentication, Authorization, X-Requested-With");
			
			Flight::start();                                // run Flight

			$this->sendRespons();                           // return unified json-response
		}
		
		private function sendRespons()
		{
			if(is_array($this->dataResponse) && empty($this->response->lastinserted))
			{ $this->response->count = count($this->dataResponse); }
			elseif($this->statusResponse != 400 || $this->statusResponse != 404) {
				$this->response->count = 0;
			}
			else {
				$this->response->count = 1;
			}
			if(!empty($this->dataResponse['token']))   {
				$this->response->count = 1;
			}
			
			Flight::json(
			[
				'data' => $this->dataResponse,
				'meta'=> [
					'site'      => $this->config->siteName,
					'created_by'=> $this->config->created,
					'success'   => $this->successResponse,
					'status'    => $this->statusResponse,
					'message'   => $this->messageResponse,
				],
				'request'     => $this->request,         // created in constructor, updated with the process
				'response'    => $this->response,       // created in constructor, updated with the process
			]);
		}
		
		public function error($message, $status)
		{   // helper to return message and error-status
			$this->messageResponse = $message;
			$this->statusResponse = $status;
			return true;
		}
		
	}
