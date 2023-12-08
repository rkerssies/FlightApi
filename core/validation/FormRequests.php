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
			if(! empty($this->rulesArray[$this->table]))
			{   // validation is required (no empty key-name in config/validation.php_
				foreach($this->rulesArray[$this->table] as $fieldName => $validationArray)
				{   // array with validation-rukles is leading to check if all fields ar valid, or required
					//					dd($validationArray);

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
						if(empty($request->$fieldName) && $validatorMethodName == 'is_Required')
						{
							$fails[$fieldName][] = $fieldName.' - NOT found in request and is required';
						}
						elseif(! empty($request->$fieldName) && empty($validatorParam)
							&& ! $this->$validatorMethodName($request->$fieldName))
						{
							$fails[$fieldName][] = $fieldName.' - '.$this->failMessage;
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
