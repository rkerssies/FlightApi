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
//	use InvalidArgumentException;
//	use UnexpectedValueException;
	
	class jwtAuth extends JWT
	{
		private $jwtConfig = [];
		private $pdo = null;
		private $rolesPermissionsArray = [];
		private $hostPath = null;
		public $requestValidation = null;
		public $successResponse = false;
		public $messageResponse = null;
		public $validation = null;
		public $permissions = null;
		public $statusResponse = 400;
		public $count = 0;
		
		private $user = null;
		private $name = null;
		private $roles = [];
		public $jwtFail = null;
		public $jwtSuccess = null;
		

		
		public function __construct($jwtConfig, $pdo = [], $host = null)
		{
			$this->jwtConfig = $jwtConfig;
			$this->rolesPermissionsArray = include '../config/rbac.php';
			
			$this->pdo      = $pdo;
			$this->hostPath = $host;
		}
		
		
		private function checkUser($requestPost = [])
		{
			// dd(password_hash('password',PASSWORD_BCRYPT, ['cost' => 10]));  // hashing password
			$validationArray = include('../config/validation.php');
			$requestValidation = new FormRequests($validationArray); // get validation SIGNIN, $table = empty
			$validated = $requestValidation->validator((object) $requestPost);

			if(! $validated)    {
				$this->successResponse  = false;
				$this->messageResponse  = 'JWT-token NOT created and provided. Validation FAILED on submitted data';
				$this->validation = $requestValidation->fails;
				$this->statusResponse  = 400;
				$this->count = 0;
			}
			elseif($validated)  {
				$record = $this->pdo->query('SELECT * FROM `users` WHERE `email` = "'.$requestPost['email'].'"')[0];
			
				if(password_verify($requestPost['password'], $record['password']))  // password from form --- hashed password
				{
					$this->user = $record['email'];
					$this->name = $record['name'];
					$this->roles = $record['roles'];      // comma-separated values
					
					$this->successResponse  = true;
					$this->messageResponse  = 'JWT-token created and provided on a valid user';
					$this->statusResponse  = 200;
					$this->count = 1;
					
					return true;
				}
			}
 			return false;
		}
		
		public function createToken($request = [])
		{
			if($this->checkUser($request))
			{
				$roles = explode(',', $this->roles);
				
				$permissionsArray = [];
				$roles = array_merge($roles,['visitor']);
				foreach($roles as $role)    {

					$role = trim($role.' ');
					if(!empty($this->rolesPermissionsArray[$role]))
					{   // skip meantioned roles in usert-table field: roles,
						// but not found in array /config/rbac.php
						$permissions=$this->rolesPermissionsArray[$role]; // get permissions on roles for this user requesting a token
						foreach($permissions as $table=>$arrayActions)
						{
							foreach($arrayActions as $action)
							{
								$permissionsArray[]=$table.'-'.$action;
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
						'email' => $this->user,
						'name'  => $this->name,
						'roles' => $this->roles,
						'permissions' => $permissionsArray, // make array of permissions available for the Front-end, to be stored in LocalStorage
					]
				];
				$this->permissions = $permissionsArray;     // make array of permissions available for the Front-end
				$jwt = (new \Firebase\JWT\JWT())->encode($payload, $this->jwtConfig->token_key, $this->jwtConfig->token_encrypt);
				
				header("Authorization: Bearer:". $jwt);
				
				return [ // return token-response
					'token' =>'Bearer:'.$jwt,
					'username' => $this->name,
					'email'     => $this->user,
					"issued_at"  => gmdate('Y-m-d H:i:s',$this->jwtConfig->IssuedAT_claim),
					"not_before" => gmdate('Y-m-d H:i:s',$this->jwtConfig->NotBeFore_claim),
					"expired_at" => gmdate('Y-m-d H:i:s',$this->jwtConfig->EXPire_claim),
					'permissions' => $permissionsArray
				];
			}
		}
		
		public function validate_jwt_token($jwt_token, $secret_key, $encrptionType)
		{   // interpret te fails to messages OR return the decoded result
			$this->jwtFail = null;

				try {
					//$leeWayTime         = 120;    // seconds      //JWT::$leeway = $leeWayTime;   // $leeway in seconds
					return JWT::decode($jwt_token, new Key($secret_key, $encrptionType));
				}
				catch (ExpiredException $e) {
					$this->jwtFail = 'Token expired';
				}
			    catch (SignatureInvalidException $e) {
						$this->jwtFail = 'Invalid token signature';
				}
				catch (BeforeValidException $e)
				{
					$this->jwtFail = 'Token not valid yet';
				}
				catch (Exception $e) {
					$this->jwtFail = 'Invalid token';
				}
		}
		
		public function checkPermissions($neededPermission ='')
		{
			$jwtToken = str_replace('Bearer ', '', getallheaders());

			if(empty($jwtToken['Authorization']))    {
				$this->jwtFail = 'no jwt-token provided in header-key: Authorization';
				return false;
			}

			$decodedPayload = $this->validate_jwt_token($jwtToken['Authorization'], $this->jwtConfig->token_key,$this->jwtConfig->token_encrypt ) ;
			if($decodedPayload == false)    {
				$this->jwtFail = 'no valid jwt-token provided - '.$this->jwtFail;
				return false;
			}
			
			if(!is_array($decodedPayload->data->permissions)
				|| ! in_array($neededPermission, $decodedPayload->data->permissions)){
				$this->jwtFail = 'jwt-token provided no valid permission for requested url';
				return false;
			}
//			unset($decodedPayload->data->permissions);      // remove list with ALL ALLOWED permissions from response
			
			$this->jwtSuccess = $decodedPayload->data;
			return true;
		}
	}
