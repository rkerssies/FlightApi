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
				'name'  => ['required', 'string','max:20'],
				'brewer'  => ['required','string'],
				'perc'  => ['required','numeric','smallerthan:14', 'biggerthan:-0.1'],
			]
		
	];

?>
