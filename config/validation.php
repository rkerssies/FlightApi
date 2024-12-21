<?php
	
	/* *******************************************************
	                configuration values
	******************************************************* */
	
	
	 return [
		'signin' =>
		[
			'email'     => ['required','string','min:8','email'],
			'password'  => ['required','string','min:8'],
		],
		'users' => [    // table-name and it's fields and its field-validation
			'name'  => ['required','string' ],
			'email'  => ['required','string', 'email' ],
			'password'  => ['required','string' ],

		],
		'beers' => [
				'name'  => ['required', 'string','max:50'],
//				'brewer'  => ['required','string'],
				'brewer_id'  => ['required','numeric'],
				'perc'  => ['required','numeric','smallerthan:14', 'biggerthan:-0.1'],
			]
		
	];

?>
