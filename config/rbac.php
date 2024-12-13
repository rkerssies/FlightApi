<?php
	
	/* *******************************************************
	                configuration values
	******************************************************* */
	
	
	 return [
		 /*
		  *     keys with role must correspondent stored roles in users-table in field `roles`
		  *
		  *     Note, NOT all Flight-routes contain permission-checks,
		  *     eq: urls for visitors-pages or signing in for a JWT-token
		 */
		 
		'root' => [         // role with its permissions
			// custom routes; routes/custom_api.php
			'bars'      => ['totals'],                                                  // custom permissions, eq: bars-totals
			
			// auto-API on tables; routes/api.php
			'users'     => ['all','find','create','edit','destroy','trash','restore' ], // keep last root !!!  (trash OR destroy)
			'beers'     => ['all','find','create','edit','destroy','trash','restore' ],
			'brewers'   => ['all','find','create','edit','destroy','trash','restore' ],
			'beers_brewers' => ['all','find','create','edit','destroy','trash','restore' ],
			'users_beers'   => ['all','find','create','edit','destroy','trash','restore' ],
			'order_beer'    => ['all','find','create','edit','destroy','trash','restore' ],
			
		],
		'user' => [         // role with its permissions (theme & action)
			// auto-API on tables; routes/api.php
			'beers'     => ['all','find'],
			'brewers'   => ['all'],
			]
	];

?>
