<?php
	
	/* *******************************************************
	                configuration values
	******************************************************* */
	
$now = time();
$token_due_days = 1; 									// token valid for X days
	
return (object) [
	
	'siteName' => 'my FlightApi project',
	'created' => 'InCubics.net',
	'app_key'  =>  'qwerty%#%0987654321#$#qwerty',     	//TODO used 4 encrypt passwords
	'time_zone' => 'Europe/Amsterdam',					// https://www.php.net/manual/en/timezones.php

	/// Pagination defaults
	'noPagination'          => 1000,    // limits the amount of records for a request without pagination
	'defafaultPagination'   => 10,      // amount of records when only pageing is used in url-qsa
	
	/// JWT
	'jwt' => (object) [
		'token_due_days'    => $token_due_days,                            	// in days
		'IssuedAT_claim'    => $now,                                        // now in seconds Unix-timestamp
		'NotBeFore_claim'   => ($now +  10),                                // now + seconds later
		'EXPire_claim'      => ($now + ($token_due_days * 24 * 60 * 60)), 	// amount of seconds after now
		'token_encrypt' => 'HS256',                              			// 'HS256' || 'HS512'
		// the token encription-key is stored in the file  /.app_key
		],

	/// the default Database-account is strored in the file /.env
	/// a SMTP-account for sending email is stored  in the file /.env

];
?>
