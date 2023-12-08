<?php
	/**
	 * Project: PhpStorm.
	 * Author:  InCubics
	 * Date:    23/11/2023
	 * File:    index.php
	 */
	
	// error reporting on
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	
	
	require_once "../core/FlightApi.php";
	
	(new \core\FlightApi())->run();
	

