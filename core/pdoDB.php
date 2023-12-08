<?php
	/**
	 * Project: PhpStorm.
	 * Author:  InCubics
	 * Date:    28/06/2022
	 * File:    lib\db\pdoDB.php
	 */

	namespace core;

use \PDO;
	
	class pdoDB extends \stdClass
	{
//		private static $_pdo=null;
		private $stmt = null;
		public $querySucces = false;
		public $affectedRows = null;
		public $affected = 0;
		
		public $lastInsert = null;
		public function __construct()
		{
			$config= (object) (include("../config/app.php"))->db;
			$attr  = array(
				PDO::MYSQL_ATTR_FOUND_ROWS   => TRUE,
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			);
			if(empty($this->pdo))  {
				$this->pdo =new \PDO('mysql:host='.$config->host.';dbname='.$config->dbname ,
					$config->user, $config->pass, $attr );
			}
		}
		
		public function query($query, $parameters=null)
		{
				$this->pdo->beginTransaction();
									  $stmt =	$this->pdo->prepare($query);
				$this->querySucces  = $stmt->execute(); //$parameters
	
				if(str_contains($query, 'SELECT')  || str_contains($query, 'UPDATE')) {
					$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				}
				else {
					$result = $this->querySucces;
				}
				if(str_contains($query, 'INSERT') ) {
					$this->lastInsert = $this->pdo->lastInsertId();
				}
//				if(str_contains($query, 'UPDATE')  || str_contains($query, 'DELETE')) {
//					$this->affectedRows = $stmt->rowCount();
//				}
			$this->pdo->commit();
			return $result;
		}

		private function _toArray(&$parameters)
		{
			if(!is_array($parameters))  {
				$parameters=array($parameters);
			}
		}

		public function tableExists($tableName){
			
			if($this->query ('SHOW TABLE STATUS LIKE "'.$tableName.'"'))
			{
				return true;
			}
			return false;
		}
//		private function __construct()
//		{
//		}
//
//		private function __clone()
//		{
//		}
//
//		private function __wakeup()
//		{
//		}
	}
