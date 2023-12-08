<?php
	/**
	 * Project: MVC2022.
	 * Author:  InCubics
	 * Date:    01/07/2022
	 * File:    ValidationPatterns.php
	 */
	
	namespace core\validation;
	
	class ValidationPatterns
	{
		/*
		 *      Class with variety of validation-options
		 */
		public $failMessage = null;
		
		// The is_Regex method is reused by serveral other methods in this class
		private function is_Regex($regExpr, $value)
		{
			if(!preg_match($regExpr, $value, $aMatches))    {
				$this->failMessage = 'field doesn\'t match regular expression';
				return false;
			}
			else    {
				return true;
			}
		}
		
		//////////////////////////////
		
		protected function is_Text($value)
		{
			$r='/^[A-Za-z09\-\ \_\.\,]+$/D';
			if(!$this->is_Regex($r, $value))    {
				$this->failMessage = 'field doesn\'t match textual characters';
				return false;
			}
			else    {
				return true;
			}
		}
		
		protected function is_Required($value)
		{
			if(empty($value)  )   {      // nb: requird getal 'nul' (0) als text is === null
				$this->failMessage = 'field is required';
				return false;
			}
			else    {
				return true;
			}
		}
		
		protected function is_Integer($value)
		{
			if(!is_int($value)) {
				$this->failMessage = 'field must be an integer';
				return false;
			}
			else    {
				return true;
			}
		}
		
		protected function is_Bool($value)
		{
			if(!is_bool($value)) {
				$this->failMessage = 'field must be of type boolean (true or false)';
				return false;
			}
			else    {
				return true;
			}
		}
		
		protected function is_Double($value, $format='6,2')
		{
			// count chars before and after DOT.  eq for $format with 3,2 corresponds with $value:  123.78
			//TODO make isDouble validation better
			if(!is_numeric($value) && substr_count($value, '.') == 1) {
				$this->failMessage = 'field must be of type double (eq. format: 133.99)';
				return false;
			}
			else    {
				return true;
			}
		}
		
		protected function is_Numeric($value)
		{
			if(!is_numeric($value)) {
				$this->failMessage = 'field must be numeric';
				return false;
			}
			else    {
				return true;
			}
		}
		protected function is_Biggerthan($value, $biggerthan)
		{
			if($biggerthan > $value) {
				$this->failMessage = 'field must be bigger than '.$biggerthan;
				return false;
			}
			else    {
				return true;
			}
		}
		protected function is_Smallerthan($value, $smallerthan)
		{

			$smallerthan = (float)$smallerthan;
			if($smallerthan < $value)  {
				$this->failMessage = 'field must be smaller than '.$smallerthan;
				return false;
			}
			else    {
				return true;
			}
		}
		
		protected function is_String($value)
		{
			if(!is_string($value))  {
				$this->failMessage = 'field must be of type string';
				return false;
			}
			else    {
				return true;
			}
		}
		
		protected function is_Alphanum($value)
		{
			// minimal 4 chars
			$r='/^[A-Za-z09]+$/D';
			if($this->is_Regex($r, $value))
			{
				$this->failMessage = 'field must be of type string';
				return false;
			}
			else    {
				return true;
			}
			
		}
		
		protected function is_Float($value)
		{
			if(!is_float($value))   {
				$this->failMessage = 'fieldvalue must be of type float';
				return false;
			}
			else    {
				return true;
			}
		}
		
		protected function is_Array($value)
		{
			if(!is_array($value))   {
				$this->failMessage = 'fieldvalue must be of type array';
				return false;
			}
			else
			{
				return true;
			}
		}
		
		protected function is_Object($value)
		{
			if(!is_object($value))  {
				$this->failMessage = 'fieldvalue must be of type object';
				return false;
			}
			else
			{
				return true;
			}
		}
		
		protected function is_Resource($value)
		{
			if(empty($value))   {
				$this->failMessage = 'fieldvalue can\'t be empty';
				return false;
			}
			else
			{
				return true;
			}
		}
		
		
		protected function is_Json($value)
		{
			if((is_string($value) &&
				(is_object(json_decode($value)) ||
					is_array(json_decode($value)))))    {
				$this->failMessage = 'value must be json-string';
				return true;
			}
			else    {
				return false;
			}
		}
		
		protected function is_Isset($value)
		{
			if(!isset($value))  {
				$this->failMessage = 'fieldvalue must be set';
				return false;
			}
			else
			{
				return true;
			}
		}
		
		protected function is_Null($value)
		{
			if(!is_null($value))    {
				$this->failMessage = 'fieldvalue must be null';
				return false;
			}
			else
			{
				return true;
			}
		}
		
		protected function is_Max($value, $max)
		{
			if(strlen($value) > $max)    {
				$this->failMessage = 'field cann\'t have more than: '.$max.' characters';
				return false;
			}
			else    {
				return true;
			}
		}
		
		protected function is_Min($value, $min)
		{
			if(strlen($value) < $min)    {
				$this->failMessage = 'field must have at least: '.$min.' characters';

				return false;
			}
			else    {
				return true;
			}
		}
		
		protected function is_Between($value, $scale)       // lowest value is (int) 0
		{
			$limits = explode('-', $scale);
			asort($limits);
			
			
			if(is_numeric($limits[1]) && $value >= (int) $limits[0] && $value <= (int) $limits[1])    { // for numbers
				return true;
			}
			if(!is_numeric($limits[1]) && $value >= $limits[0] && $value <= $limits[1])    {   // for strings
				return true;
			}
			else    {
				$this->failMessage = 'field must be betweeen: '.$limits[0].' and '.$limits[1];
				return false;
			}
		}
		
		protected function is_Same($value, $sameAs)       // lowest value is (int) 0
		{
			if($value != \lib\validation\request()->post->$sameAs)    { // for numbers
				$this->failMessage = 'field must be the same as the value of field: '.$sameAs;
				return false;
			}
			else    {
				return true;
			}
		}
		
		
		
		
///// add if's with error-messages
/////

		
		protected function is_Name($value)
		{
			$r='/^[A-Z]{1}[A-Za-z -]+$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_Lastname($value)
		{
			$r='/^[a-z\-\s]{2,8}+$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_PostfixHomenr($value)
		{
			$r='/^([a-zA-Z-0-9]{1,3})+$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_PostHomenr($value)
		{
			$r='/^([a-zA-Z-0-9]{1,3})+$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_NLzip($value)
		{
			$r='/^[0-9]{4}([a-z]{2}|[A-Z]{2})$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_Zip($value)
		{
			$r='/^[0-9]{4}([a-z]{2}|[A-Z]{2})$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_NLphone($value)
		{
			$r='/(0[0-9]{2}-[0-9]{7})|(0[0-9]{3}-[0-9]{6})$/';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_NLmobile($value)
		{
			$r='/0[0-9]{1}-[0-9]{8}$/';
			return $this->is_Regex($r, $value);
		}
		
		
		protected function is_Email($value)
		{
			$r="/^[A-z0-9-_]+([.][A-z0-9-_]+)*[@][A-z0-9-_]+([.][A-z0-9-_]+)*[.][a-z]{2,4}$/D";
			return $this->is_Regex($r, $value);
		}
		
		protected function isEmailUpper($value)
		{
			$r='/^[a-zA-Z0-9\.\-_]{2,}+@[a-zA-Z0-9\-_]{2,}+\.([a-zA-Z0-9\-]+\.)*+[a-z]{2,4}$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_EUdate($value)
		{
			$r='/^0[1-9]{1}|1[0-9]{1}|2[0-9]{1}|3[01]{1}\-0[1-9]{1}|1[012]{1}\-19|20[0-9]{2}$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_EUdate2($value)
		{
			$r='00-00-0000';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_USdate($value)
		{
			$r='/^19|20[0-9]{2}\-0[1-9]{1}|1[012]{1}\-0[1-9]{1}|1[0-9]{1}|2[0-9]{1}|3[01]{1}$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function isTime($value)
		{
			$r='/^0[0-9]{1}|1[0-9]{1}|2[0-4]{1}[\:]0[0-9]{1}|1[0-9]{1}|2[0-9]{1}|3[0-9]{1}|4[0-9]{1}|5[0-9]{1}$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function isMoney($value)
		{
			$r='/^[0-9]{1,}.[0-9]{2}$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_NLgiro($value)
		{
			$r='/^[0-9]{7}+$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_NLbanknr($value)
		{
			$r='/^[0-9]{9}+$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_Iban($value)
		{
			//IBAN eq: DE05.1002.0500.0003.2873.00
			$r='/^[A-Z]{2}[0-9]{2}+[\.-]+[0-9]{4}+[\.-]+[0-9]{4}+[\.-]+[0-9]{4}+[\.-]+[0-9]{4}+[\.-]+[0-9]{2}+$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_Key4blocks4chars($value)
		{
			$r='/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_Username($value)
		{
			$r='/^[a-zA-Z0-9&\.\-_\+\!@#$%&]{6,}$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_Password($value)
		{
			$r='/^.*(?=.{8,15})(?=.*[0-9])(?=.*[^\(\)\<\>\{\}\[\]\+\=\`])(?=.*[\!\~\§\±\@\?\*\#\$\%\&\^\_\-])(?=.*[a-z])(?=.*[A-Z]).*$/';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_PasswordSimpel($value)
		{
			// minimal 2 chars - for testing purpose only
			$r='/^[a-zA-Z0-9&\.\-\_\+\!\@\#\$\%\&]{2,}$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_PasswordDB($value)
		{
			// minimal 8 chars
			$r='/^[a-zA-Z0-9&\.\-_\+\!@#$%&]{8,}$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_Memo($value)
		{
			$r='/^[a-zA-Z0-9&\.\-_\+\!\?\%\&\^\s]{2,}$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_Dbname($value)
		{
			// minimal 4 chars
			$r='/^[a-zA-Z0-9&\.\-_\+\!@#$%&]{4,}$/D';
			return $this->is_Regex($r, $value);
		}
		
		protected function is_Url($value)
		{
			// minimal 4 chars
			$r='/https?://([a-zA-Z0-9]+.)+[a-zA-Z0-9-_?&=:/.]+/i';
			return $this->is_Regex($r, $value);
		}
		
	}
