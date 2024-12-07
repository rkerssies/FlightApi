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
	    *   API-requests 4 AUTH and TOKENS
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
				['explanation'=> 'records of all beers appended with records of related tables and records (depth = 3, depth restricted due to mem-overload)',
					'method'=> 'get',      'path' => '/beers?related=3',    'post'=>null],
				['explanation'=> '3th page with paginated default amount beers-records of beers and there related tables and records (depth = 3)',
					'method'=> 'get',      'path' => '/beers?&page=3', 'post'=>null],
				['explanation'=> '3th page with paginated custom amount of 5 beers-records of beers and there some (depth = 1) related tables and records',
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
			
			
			'possible_methods' =>['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],             // accepted methods
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
		$this->dataResponse         = $this->jwtAuth->createToken( (array) $this->request->post);
		$this->messageResponse      = $this->jwtAuth->messageResponse;
		$this->statusResponse       = $this->jwtAuth->statusResponse;
		$this->response->validation = $this->jwtAuth->validation;
		$this->request->post->password = null;
		$this->response->count      = $this->jwtAuth->count;
	});
