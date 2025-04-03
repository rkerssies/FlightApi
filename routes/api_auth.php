<?php
	/**
	 * Project: PhpStorm.
	 * Author:  InCubics
	 * Date:    23/11/2023
	 * File:    api_auth.php
	 */
	
	/* *******************************************************
		this file contains;
			*   default-routes that do not require permissions
			*   API-requests 4 AUTH, TOKENS AND RenewPasswords
	******************************************************* */
	
	/*  HOME   */
	Flight::route('/', function()
	{
		$this->dataResponse     = [];
		$this->messageResponse  = 'The landing-page of the FlightApi project.';
		$this->statusResponse   = 200;
		$this->successResponse  = true;
		
		$this->request->values['action'] = "info"; // used for RBAC
	});
	
	
	/*  ABOUT   */
	Flight::route('/about', function()
	{
		$this->dataResponse = [
			'purpose' => 'An light-weight basic API-service build with Flight-PHP, Firebase-JWT and PDO ',
			'description' => 'It includes: JWT-tokens, RBAC with permissions, sql-queries with PDO and validation on submitted data. '
							.'Dynamically call API-requests an url-path permission-protected by default for: '
							.'all records, find an ID and CRUD is on every existing db-table possible. '
							.'Create your own API-requests with sql-queries nested and/or with relations or aggregations.'
							.'Get dynamically records from related tables (parent-related).',
			
			'possible_urls' => [
				['explanation'=> 'home-page',
					'method'=> 'get',             'path' => '/',      'post'=>null],   // home
				['explanation'=> 'about-page + explanation',
					'method'=> 'get',   'path' => '/about',           'post'=>null],  // about and example url's

				// get JWT-token
				['explanation'=> 'get JWT-token + permissions',
					'method'=> 'post',  'path' => '/signin',            'post'=>null],   // get JWT-token with included permissions

				['explanation'=> 'beer with id 12 from table `beers`',
					'method'=> 'get',           'path' => '/beers/12',     'post'=>null],
				['explanation'=> 'all beers from table `beers`',
					'method'=> 'get',      'path' => '/beers',             'post'=>null],
				['explanation'=> 'beers paginated by default, page 3 from table `beers`',
					'method'=> 'get',      'path' => '/beers?page=3',      'post'=>null],
				['explanation'=> 'beers paginated by 5, page 3 from table `beers`',
					'method'=> 'get',      'path' => '/beers?paginate=5&page=3', 'post'=>null],
				
				// RELATED
				['explanation'=> 'records of all beers appended with records of related tables and records (depth = 3. depth restricted to max 3, due to mem-overload)',
					'method'=> 'get',      'path' => '/beers?related=3',    'post'=>null],
				['explanation'=> '3th page with paginated default amount beers-records of beers and there related tables and records (depth = 3)',
					'method'=> 'get',      'path' => '/beers?related=3&page=3', 'post'=>null],
				['explanation'=> '3th page with paginated custom amount of 5 beers-records of beers and there some (depth = 1, same as without key \'related\' ) related tables and records',
					'method'=> 'get',      'path' => '/beers?related=1&paginate=5&page=3', 'post'=>null],
				['explanation'=> 'beer with id 13 from table `beers` with all the records of related table-records (depth = 3)',
					'method'=> 'get',      'path' => '/beers/13?related=3', 'post'=>null],
				// CRUD
				['explanation'=> 'create new beer-record in table `beers`',
					'method'=> 'post',      'path' => '/beers/create',     'post'=>['name'=>'MyBeer', 'brewer'=>'Flights', '-keyname-'=>'...value...']],
				['explanation'=> 'update beer-record with id 12 in table `beers`',
					'method'=> 'put',       'path' => '/beers/update/12',  'put'=>['name'=>'MyB33r', 'brewer'=>'Fl1ghts', '-keyname-'=>'...value...']],
				['explanation'=> 'destroy beer with id 12 in table `beers`',
					'method'=> 'delete',    'path' => '/beers/destroy/12', 'delete'=>null],
				['explanation'=> 'trash beer with id 13 in table `beers`',
					'method'=> 'delete',    'path' => '/beers/trash/13',   'delete'=>null],
				['explanation'=> 'restore trashed beer with id 13 in table `beers`',
					'method'=> 'delete',     'path' => '/beers/restore/13', 'delete'=>null],
	
				// CUSTOM EXAMPLE
				['explanation'=> 'custom url-path with its own permission and own specific mysql-query (with table-relations)',
					'method'=> 'get',    'path' => '/bars/totals',      'post'=>null], // custom api-request, with custom permissions in /config/rbac.php
			],
			
			
			'possible_methods' => [
								'directly'  => ['GET', 'POST'],
								'with @post and key _method ' => ['PUT', 'PATCH', 'DELETE']
								],             // accepted methods
			'CORS' => ['FligjtAPI supports CORS'],                                      //
			'JSON' => ['FlightAPI supports only a json-response, with keys:' =>
						['data', 'meta', 'request', 'response']],
			'used_sources' => ['Flight PHP'  =>'https://github.com/mikecao/flight',     // used Git-resources/projects
				              'Firebase-JWT'=>'https://github.com/firebase/php-jwt']
		];
		$this->messageResponse  = 'this data-structure explains options within this API build with Flight';
		$this->statusResponse   = 200;
		$this->successResponse  = true;
	});
	
	
	/* ME, own profile */
	Flight::route('/me', function()
	{
		$jwtToken = str_replace('Bearer ', '', getallheaders()['Authorization']).'';
		if(empty($jwtToken))    {
			$this->jwtFail = 'no jwt-token provided';
			return false;
		}
		$me = $this->jwtAuth->validate_jwt_token($jwtToken, $this->config->jwt->token_key, $this->config->jwt->token_encrypt);
		if($this->jwtAuth->jwtFail == null)
		{
				$this->dataResponse      = (array) $me->data;
				$this->messageResponse  = 'your personal data, roles and permissions';
				$this->statusResponse   = 200;
				$this->successResponse  = true;
		}
		else {
			$this->messageResponse      = $this->jwtAuth->jwtFail;
			$this->statusResponse       = 403;
		}
	});
	
	
	/* Get JWT-token with incl RBAC-permissions  */
	Flight::route('POST /signin', function()
	{
		$validated = $this->requestValidation->validator((object)$this->request->post);
		$validUser = $this->jwtAuth->checkUser($this->request->post);

		if( ! $validated) 	// form-input ok
		{
			// No valid useraccount
			$this->successResponse  	= false;
			$this->statusResponse  		= 403;
			$this->messageResponse 	 	= 'NOT SIGNED IN, validation FAILED on signin-data';
			$this->response->validation  = $this->requestValidation->fails;
			$this->request->post->password = '*************';  // security: remove password from request

		}
		elseif( ! $validUser) 	// user not found 
		{
			// No valid useraccount
			$this->successResponse  	= false;
			$this->statusResponse  		= 403;
			$this->messageResponse  	= $this->jwtAuth->messageResponse;
			$this->response->validation  = $this->requestValidation->fails;
		}
		elseif(!empty($this->jwtAuth->blocked) && $this->jwtAuth->blocked != '0000-00-00 00:00:00' )
		{	// blocked user	
			$this->successResponse  	= false;
			$this->statusResponse  		= 403;
			$this->dataResponse         = $this->jwtAuth->data;
			$this->messageResponse      = $this->jwtAuth->messageResponse;
			$this->response->validation = $this->jwtAuth->validation;
			$this->response->blocked 	= $this->jwtAuth->blocked;
			$this->request->post->password = '*************';  // security: remove password from request

		}
		elseif($validUser &&  (empty($this->jwtAuth->blocked) || $this->jwtAuth->blocked == '0000-00-00 00:00:00') ) 
		{	// valid user; provide token || force a new password
			$this->successResponse  	= true;
			$this->statusResponse  		= $this->jwtAuth->setStatusResponse;
			$this->dataResponse         = (object) $this->jwtAuth->data;
			$this->messageResponse      = $this->jwtAuth->messageResponse;
			$this->response->validation = $this->jwtAuth->validation;
			$this->response->newpass 	= $this->jwtAuth->newpass;
			$this->request->post->password = '*************';  // security: remove password from request
		}
	});



	Flight::route('PUT /newpassword', function()
	{   
		$cryptString = $this->config->app_key.str_replace(array(' ','&nbsp;'),'',$this->config->siteName);

		$loginAccount = (object)['email' => $this->request->put->email, 'password' =>$this->request->put->oldPassword];
		$validated = $this->requestValidation->validator((object)$loginAccount);
		$validUser = $this->jwtAuth->checkUser( $loginAccount);

		if(! $validUser)
		{	//no valid user found
			$this->request->put 		= $this->jwtAuth->data; // empty/clean-up
			$this->messageResponse      = $this->jwtAuth->messageResponse;	
			$this->meta->success       = $this->jwtAuth->setStatusResponse; 
			$this->successResponse  	= $this->jwtAuth->successResponse;
		}
		elseif(! $this->jwtAuth->checkPermissions('password-renew'))
		{  
			$this->messageResponse      = 'Invalid permissions to change the password';
			$this->statusResponse       = 403;  		// Forbidden, no valid access-rights/permissions
		}
		elseif($validUser == true && $validated == true)
		{
			$sql='UPDATE `users` SET `password` = "'.make_crypt($this->request->put->newPassword, $cryptString).'",
			`newpass` = "0000-00-00 00:00:00",
			`updated_at` = "'.date('Y-m-d H:i:s').'"
			WHERE `email` = "'.$this->request->put->email.'" 
			AND `password` = "'.make_crypt($this->request->put->oldPassword, $cryptString).'"
			AND (`blocked` IS NULL OR `blocked` = "0000-00-00 00:00:00")';

			$this->db->query($sql);	

			if($this->db->querySuccess == true)
			{	
				$newPut = [
							'email'			=> $this->request->put->email, 
							'oldPassword' 	=> "************",
							'newPassword' 	=> "*************",
							'confirmPassword' => "*************",
							];
				$this->request->put 	= $newPut;
				$this->messageResponse 	= 'renewed password';
				$this->statusResponse  	= 200;
				$this->successResponse 	= true;
				$this->request->values['action'] = 'newpassword';
			}
			else	{
				$this->error('NOT FOUND: no dataset found of bars and there totals', 404);
			}
		}

	});
