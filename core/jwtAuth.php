<?php
	/**
	 * Project: PhpStorm.
	 * Author:  InCubics
	 * Date:    01/12/2023
	 * File:    jwtAuth.php
	 *
	 * @package core
	 */
	
	namespace core;
	use core\validation\FormRequests;
	use Firebase\JWT\JWT;
	use Firebase\JWT\Key;
	use Exception;
	use Firebase\JWT\SignatureInvalidException;
	use Firebase\JWT\BeforeValidException;
	use Firebase\JWT\ExpiredException;
	
	class jwtAuth extends JWT
	{
		private $jwtConfig = [];
		private $pdo = null;
		private $rolesPermissionsArray = [];
		public $permissionsArray = [];
		private $hostPath = null;
		public $requestValidation = null;
		public $statusResponse = 404;
		public $successResponse = false;
		public $messageResponse = null;
		public $validation = null;
		public $permissions = [];
		public $count = 0;
		
		public $data = null;
		private $email = null;
		private $name = null;
		private $avatar = null;
		public $roles = [];
		public $setStatusResponse =404;
		public $blocked = null;
		public $newpass = null;
		public $jwtFail = null;
		public $jwtSuccess = null;
		

		
		public function __construct($jwtConfig, $pdo = [], $host = null)
		{
			$this->jwtConfig = $jwtConfig;
			$this->rolesPermissionsArray = include '../config/rbac.php';
			
			$this->pdo      = $pdo;
			$this->hostPath = $host;
		}
		
		
		public function checkUser($requestPost)
		{
			$validationArray = include('../config/validation.php');
			$requestValidation = new FormRequests($validationArray); // get validation SIGNIN, $table = empty
			$validated = $requestValidation->validator((object) $requestPost);

			if(! $validated)    {
				$this->successResponse  = false;
				$this->messageResponse  = 'JWT-token NOT created and provided. Validation FAILED on submitted data';
				$this->validation 		= $requestValidation->fails;
				$this->statusResponse  	= 400;
				$this->count = 0;
			}
			elseif($validated)  	
			{
				$sql = 'SELECT u.*, 
							(SELECT COUNT(*) FROM users WHERE roles LIKE "%root%") AS root_count,
							CASE WHEN u.roles LIKE "%root%" THEN 1 ELSE 0 END AS has_root_role
						FROM users u
						WHERE `u`.`email` = "'.$requestPost->email.'"
						AND  `u`.`password` ="'.make_crypt($requestPost->password).'"';

					$result = $this->pdo->query($sql);

					if(array_key_exists(0, $result))		{
						$record = (object) $result[0];
					}
					else 		{
						$record = null;
					}


				if(empty($record))
				{	// No user-record found 
					$this->data = (object) [
						'token' =>null, 
						'email' =>null, 
						'name' => null, 
						'avatar'=> null
					];
					$this->roles 				= [];
					$this->setStatusResponse 	=  403;
					$this->successResponse  	= false;
					$this->blocked 				= false;
					$this->messageResponse  	= 'WARN! Not valid account was found';
					$this->count 				= 0;
					return false;			// no valid user
				}
				if( (!empty($record->root_count) && $record->root_count <= 1 )
					 &&
					(!empty($record->blocked) && $record->blocked != '0000-00-00 00:00:00') )
				{ 	// No root-account found
					$this->data = (object) [
						'token' =>null, 
						'email' =>$record->email, 
						'name' => $record->name, 
						'avatar'=> $record->avatar
					];
					$this->roles 				= [];
					$this->setStatusResponse 	=  500;
					$this->successResponse 		= false;
					$this->blocked 				= true;
					$this->messageResponse  	= 'ERROR! NO valid root-account available or last root-account is blocked';
					$this->count 				= 0;
					return true;
				}
				elseif(!empty($record->email) 
				&& (empty($record->blocked) || $record->blocked == '0000-00-00 00:00:00'))
				{	// valid user, no issues -->  provide token
					$this->name 		= $record->name;		// private to create response and payload
					$this->email 		= $record->email;		// private to create response and payload
					$this->avatar 		= $record->avatar;		// private to create response
					if(!empty($record->newpass) && $record->newpass != '0000-00-00 00:00:00')	{
						$this->newpass = true;
					}

					$this->data = $this->createToken( $requestPost ,$record->roles); // includes; token, name, email, avatar, etc.

					if(!empty($record->newpass) && $record->newpass != '0000-00-00 00:00:00')
					{
						$this->newpass 				=  true;
						$this->setStatusResponse  	= 307;
						$this->messageResponse  	= 'WARNING! Forced to RENEW password (your permissions has been restricted).';
						$this->permissions 			= ['user-newpass'];
						$this->successResponse  	= true;
					}
					else 
					{				
						$this->setStatusResponse  	= 200;
						$this->messageResponse  	= 'JWT-token created and provided on a valid user.';
						$this->successResponse 		= true;
					}
					return true;
				}
				elseif( !empty($record->blocked) && $record->blocked != '0000-00-00 00:00:00'
				&&  ($record->root_count > 1 || $record->has_root_role != true))
				{	// changes on last root-account; NOT blockable
					$this->data = (object) [
						'token' =>null, 
						'email' =>$record->email, 
						'name' => $record->name, 
						'avatar'=> $record->avatar
					];
					$this->roles 			= [];
					$this->permissionsArray = [];  
					$this->blocked 			= true;
					$this->successResponse  = false;
					$this->messageResponse  = 'WARNING! User-account is BLOCKED! Please contact the administrator.';
					$this->count 			= 0;
					$this->statusResponse  	= 403;
					return true;
				}
			}
 			return false;
		}
		
		public function createToken($request, $roles = '')
		{
			$rolesArray = [];
			if($this->newpass != true)
			{
				$rolesArray = explode(',', $roles);
			}
			$rolesArray = array_merge($rolesArray,['visitor']);

			foreach($rolesArray as $role)    {

				$role = trim($role.' ');
				if(!empty($this->rolesPermissionsArray[$role]))
				{   // skip mentioned roles in usert-table field: roles,
					// but not found in array /config/rbac.php
					$permissionRole=$this->rolesPermissionsArray[$role]; // get permissions on roles for this user requesting a token
					foreach($permissionRole as $table=>$arrayActions)
					{
						foreach($arrayActions as $action)
						{
							$this->permissions[]=$table.'-'.$action;
						}
					}
				}
			}
			$payload = [
				'iss' => isset($_SERVER["HTTPS"]) ? 'https'.'://' : 'http' .'://'.$_SERVER['HTTP_HOST'],
				'aud' => 'InCubics.net',
				"iat"        => $this->jwtConfig->IssuedAT_claim,
				"nbf"        => $this->jwtConfig->NotBeFore_claim,
				"exp"        => $this->jwtConfig->EXPire_claim,
				'data' => [
					'email' => $this->email,
					'name'  => $this->name,
					'roles' => $rolesArray,
					'permissions' => $this->permissions, // make array of permissions available for the Front-end, to be stored in LocalStorage
					]
			];

			$jwt = (new \Firebase\JWT\JWT())->encode($payload, $this->jwtConfig->token_key, $this->jwtConfig->token_encrypt);
				
			header("Authorization: Bearer:". $jwt);
			
			return [ // return token-response
				'token' 		=> 'Bearer:'.$jwt,
				"issued_at"  	=> gmdate('Y-m-d H:i:s',$this->jwtConfig->IssuedAT_claim),
				"not_before" 	=> gmdate('Y-m-d H:i:s',$this->jwtConfig->NotBeFore_claim),
				"expired_at" 	=> gmdate('Y-m-d H:i:s',$this->jwtConfig->EXPire_claim),
				'name' 			=> $this->name,
				'email'     	=> $this->email,
				'avatar'    	 => $this->avatar,  // base64 encode blob data
				"roles"			=> $rolesArray,
				'permissions' 	=> $this->permissions
			];
		}
		
		public function validate_jwt_token($jwt_token, $secret_key, $encrptionType)
		{   // interpret te fails to messages OR return the decoded result
			$this->jwtFail = null;

				try {
					//$leeWayTime         = 120;    // seconds      //JWT::$leeway = $leeWayTime;   // $leeway in seconds
					return JWT::decode($jwt_token, new Key($secret_key, $encrptionType));
				}
				catch (ExpiredException $e) {
					$this->jwtFail 		= 'Token expired';
				}
			    catch (SignatureInvalidException $e) {
						$this->jwtFail = 'Invalid token signature';
				}
				catch (BeforeValidException $e)
				{
					$this->jwtFail 		= 'Token not valid yet';
				}
				catch (Exception $e) {
					$this->jwtFail 		= 'Invalid token';
				}
		}
		
		public function checkPermissions($neededPermission ='')
		{
			$jwtToken = str_replace('Bearer ', '', getallheaders());

			if(empty($jwtToken['Authorization']))    {
				$this->jwtFail 	= 'No jwt-token provided in header-key: Authorization';
				return false;
			}

			$decodedPayload = $this->validate_jwt_token($jwtToken['Authorization'], $this->jwtConfig->token_key,$this->jwtConfig->token_encrypt ) ;
			if($decodedPayload == false)    {
				$this->jwtFail 	= 'No valid jwt-token provided - '.$this->jwtFail;
				return false;
			}
			
			if(!is_array($decodedPayload->data->permissions)
				|| ! in_array($neededPermission, $decodedPayload->data->permissions)){
				$this->jwtFail 	= 'Jwt-token provided has no valid permission on requested endpoint';
				return false;
			}
			
			$this->jwtSuccess = $decodedPayload->data;
			return true;
		}
	}