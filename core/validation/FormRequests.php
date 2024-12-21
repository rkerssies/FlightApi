<?php
	/**
	 * Project: MVC2022.
	 * Author:  InCubics
	 * Date:    01/07/2022
	 * File:    FormRequests.php
	 */
	
	
	namespace core\validation;
	
	class FormRequests  extends ValidationPatterns
	{
		public $fail = null;
		private $rulesArray = [];
		private $table = null;
		public $fails = null;
		
		public function __construct($rulesArray, $table='signin')
		{
			$this->rulesArray = $rulesArray;
			$this->table      = $table;
		}
		
		public function validator($request)
		{
//TODO build more validationfrom : ValidationPatterns.php
			if(! empty($this->rulesArray[$this->table]))
			{   // validation is required (no empty key-name in config/validation.php )
				foreach($this->rulesArray[$this->table] as $fieldName => $validationArray)
				{   // array with validation-rules is leading to check if all fields ar valid, or required

					foreach($validationArray as $v_item)
					{
						$validatorParam = null;
						if(str_contains($v_item, ':'))  {
							$v_parts=explode(':', $v_item);
							$validatorMethodName='is_'.ucfirst($v_parts[0]);
							$validatorParam=$v_parts[1];
						}
						else    {
							$validatorMethodName='is_'.ucfirst($v_item);
						}
						
						if($v_item=='nullable' && empty($request->$fieldName))
						{  // field allowed to be null
							break;
						}
						if(!method_exists($this, $validatorMethodName))
						{
							$this->fails=['message'=>'FAILED validation, validation-rule '.$validatorMethodName.' not found', 'status'=>false, 'fail'=>true];
							return false;
						}
						
						if( $validatorMethodName == 'is_Required'&&
							( empty($request->$fieldName) || $request->$fieldName == ''  || $request->$fieldName == null))
						{
							$fails[$fieldName][] = $fieldName.' - NOT found in request and is required';
						}
						elseif( $validatorMethodName == 'is_Numeric' &&
							!empty($request->$fieldName) && !is_numeric($request->$fieldName))
						{
							$fails[$fieldName][] = $fieldName.' - is NOT numeric';
						}
						elseif( $validatorMethodName == 'is_String' &&
							!empty($request->$fieldName) && !is_string($request->$fieldName))
						{
							$fails[$fieldName][] = $fieldName.' - is NOT string';
						}
						elseif( $validatorMethodName == 'is_Lessthan' &&
							$request->$fieldName < $validatorParam )
						{
							$fails[$fieldName][] = $fieldName.' - value is NOT less than '.$validatorParam;
						}
						elseif( $validatorMethodName == 'is_Biggerthan' &&
							$request->$fieldName < $validatorParam )
						{
							$fails[$fieldName][] = $fieldName.' - value is NOT more than '.$validatorParam;
						}
						elseif( $validatorMethodName == 'is_Different_with' &&
							$request->$fieldName == $validatorParam )
						{
							$fails[$fieldName][] = $fieldName.' - value is NOT different from value '.$validatorParam;
						}
						elseif( $validatorMethodName == 'is_Equals_with' &&
							$request->$fieldName != $validatorParam )
						{
							$fails[$fieldName][] = $fieldName.' - value is NOT the same with with value '.$validatorParam;
						}
						elseif( $validatorMethodName == 'is_Min' &&
							strlen($request->$fieldName) < $validatorParam )
						{
							$fails[$fieldName][] = $fieldName.' - character count is LESS than '.$validatorParam;
						}
						elseif( $validatorMethodName == 'is_Max' &&
							strlen($request->$fieldName) > $validatorParam )
						{
							$fails[$fieldName][] = $fieldName.' - character count is MORE than '.$validatorParam;
						}
						elseif( $validatorMethodName == 'is_Email' &&
							! preg_match("/^[A-z0-9-_]+([.][A-z0-9-_]+)*[@][A-z0-9-_]+([.][A-z0-9-_]+)*[.][a-z]{2,4}$/D",
										$request->$fieldName))
						{
							$fails[$fieldName][] = $fieldName.' - is NOT a valid email-address';
						}
						elseif(! empty($request->$fieldName) && ! empty($validatorParam)
							&& ! $this->$validatorMethodName($request->$fieldName, $validatorParam))
						{
							$fails[$fieldName][] = $fieldName.' - '.$this->failMessage;
						}
					}
				}
				
				if(!empty($fails))
				{
					$this->fails=['message'=>'submitted form has validation errors', 'status'=> false, 'fail'=>$fails];
					return false;       // found fails
				}
			}
			else {
				$this->fails=['message'=>'WARNING: no validation required for table'.$this->table, 'status'=> true, 'fail'=>null];
			}
			return true;
		}
	}
