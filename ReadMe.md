
# Flight-API

This light-weight REST-API is a bare basis project
It is build on the Flight-framework, and uses also Firebase-JWT and PDO

You can easelly add your own routes, custom sql-queries and data-validation, roles and permissions.

### Used Git-projects

- [Flight-framework]( 'https://github.com/mikecao/flight' )
- [Firebase JWY]( 'https://github.com/firebase/php-jwt'  )
- PHP PDO 

### Some impressions
Below are some PostMan screenshots that give an impression of how te json-responses.

<img src="https://github.com/rkerssies/FlightApi/blob/main/ReadMe/01-get-token.png" width="30%" style="margin-right:10px;display:block;float:left;"> 
<img src="https://github.com/rkerssies/FlightApi/blob/main/ReadMe/02-find-by-id.png" width="30%" style="margin-right:10px;display:block;float:left;"> 
<img src="https://github.com/rkerssies/FlightApi/blob/main/ReadMe/03-related-sql.png" width="30%" style="margin-right:10px;display:block;float:left;">


## Installation and configuration
* Pull the project from GitHub; [FlightAPI]( 'https://github.com/rkerssies/FlightApi' )
* Get other project with [composer]( 'https://getcomposer.org/download/' ), 
run the following command in the root of your FlightAPI project;

```cmd
 composer update 
 ```
It creates the folder `/vendor` and downloads the required project defined within `/composer.json`. 
* Create a database `flight_beers` and import the default database-tables /config/flight_beers.sql.<br>
NB: the 'users'-table should exist and contain a field 'roles' for access with rolles and permissions.

* Remove teh ReadMe-file, the ReadMe-folder & the sql-file /config/flight_beers.sql

* The file `/config/app.php` contains site-defaults, ready to change according to your needs;
    1. Site-name
    2. App-key (not yet used for eq. encrypting passwords) 
    2. Database-connection settings
    3. Defaults for pagination
    4. The used encryption method and key to encrypt and decrypt the JWT-token<br>
       NB: Make sure all folders are protected, only the public-folder should be accessable
    5. The due-days a token is valid (var in the top of the file)
      
    ‼️ Keep the structure of the array, only change the values

## How it works

### Methods and url-paths
Bij default (without taking RBAC in consideration) all existing tables in the database 
can be queried with actions: all, find by id, CRUD, soft deleting and restore.
The build-up of url is:

`http://MyFlightApi.rk/ <table-name> / <action> `
or with QSA:
`http://MyFlightApi.rk/ <table-name> / <action>  ?key1=value1 `

NB: The methods GET, POST, PUT, PATCH and DELETE are supported.

See for url-examples and used methods on the url-path `/about` on your own project-domain.

There are three route files;
1. `/routes/api.php` for all default-routes on each exitsing database-tabel (keep out)
2. `/routes/api_auth.php` for all visitor routes and the /signin -route to receive an JWT-token
3. `/routes/custom_api.php` for all your own created routes.

NB: The custom routes contain one example of een route that queries data over several tables (join).


### Validation of submitted data
There is data-validation in place for ge create- (POST) and update-actions (PUT).
The file `config/validation.php` returns an array, see part of the array below as example;
```php
	 return [
		'beers' => [
				'name'   => ['required', 'string','max:30'],
				'brewer' => ['required','string'],
				'perc'   => ['required','numeric','smallerthan:14', 'biggerthan:-0.1'],
			]
    ];
```
The example shows that submitted data for updating or new records in table 'beers' eq. require three
records. The names of the three example fields are one-on-one with the column names in the database-table.

The default validation-rules van be found in `core/validation/ValidationPatterns.php`.
A rule in the array like `required` is a method with the name `is_Required` in the file ValidationPatterns.php.

### Protecting routes with roles and permissions
Some routes are accessable for visitors (routes in /routes/api_auth.php).

All other routes are or can be protected with permissions. 
Permissions are organised with in roles and an user can have several roles.
An easy way of dealing with this is the column 'roles' in the user-table.
This field may contain comma-separated role-names, like 'admin,user' (no spaces).

A JWT-token can be requested like: `http://MyFlightApi.rk/signin` .<br>
When requesting a token you need to provide a valid email and password.
If a matching user is found the roles from the database ar looked up and all permissions 
are placed within the JWT-token it-self.
Then using the JWT-token on a API-request, the required permission is looked-up 
permissions with the token without a database-request.

The permissions for the required roles in the JWT-token are constructed from a 
array return from the file `/config/rbac.php`.
An example of this file is shown below:
```php
	 return [
		'user' => [ 
			'beers'     => ['all','find'],
			'bars'      => ['totals'],  
			]
	];
```
This means that a user with the role `user` is allowed to access:<br>
beers-all, beers-find and bars-totals.<br>
Beers is a one-on-one wuth a database-table-name.    
Bars is a fictive, unique, non-table name defined within a custom-route.  




> Good luck with the API !   🙌🏼





