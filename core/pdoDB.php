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
		private $stmt=null;
		public $queryParams=['id'=>null, 'page'=>null, 'paganate'=>null]; // params to limit query
		public $querySucces=false;
		public $affectedRows=null;
		public $affected=0;
		public $lastInsert=null;
		
		public function __construct()
		{
			$config=(object)(include("../config/app.php"))->db;
			$attr=array(PDO::MYSQL_ATTR_FOUND_ROWS=>TRUE, PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,);
			if(empty($this->pdo))
			{
				$this->pdo=new \PDO('mysql:host='.$config->host.';dbname='.$config->dbname, $config->user, $config->pass, $attr);
			}
		}
		
		public function query($query, $parameters=null)
		{
			$this->pdo->beginTransaction();
			$stmt=$this->pdo->prepare($query);
			$this->querySucces=$stmt->execute(); //$parameters
			if(str_contains($query, 'SELECT') || str_contains($query, 'UPDATE'))
			{
				$result=$stmt->fetchAll(PDO::FETCH_ASSOC);
			}
			else
			{
				$result=$this->querySucces;
			}
			if(str_contains($query, 'INSERT'))
			{
				$this->lastInsert=$this->pdo->lastInsertId();
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
		
		public function tableExists($tableName)
		{
			if($this->query('SHOW TABLE STATUS LIKE "'.$tableName.'"'))
			{
				return true;
			}
			return false;
		}
		
		
		/////// RELATIONS ///////
		/**
		 * Fetch data recursively for a table and its related tables while preventing cycles.
		 */
		public function fetchDataRecursive($table, $parentKey=null, $parentValue=null, $visitedTables=[], $depth=0, $maxDepth=1|2|3)
		{
			// Stop recursie als de maximale diepte is bereikt
			// Voorkom cycli: controleer of de tabel al bezocht is
			if(in_array($table, $visitedTables))
			{
				return [];
			}
			// Voeg de huidige tabel toe aan de bezochte tabellen
			$visitedTables[]=$table;
			// Haal gegevens op voor de huidige tabel
			$query="SELECT * FROM `$table`";
			$params=[];
			if($parentKey===null && is_numeric($this->queryParams['id']))
			{
				// Alleen specifiek record ophalen
				$query.=" WHERE `id` = :id";
				$params=['id'=>$this->queryParams['id']];
			}
			elseif($parentKey && $parentValue)
			{
				// Gerelateerde records ophalen
				$query.=" WHERE `$parentKey` = :value";
				$params=['value'=>$parentValue];
			}
			// pagination
			if(empty($this->queryParams['id']) && is_numeric($this->queryParams['page']) && $parentKey===null)
			{
				if(empty($this->queryParams['paginate']))
				{
					$this->queryParams['paginate']=''; // from config
				}
				$offset=($this->queryParams['page']-1)*$this->queryParams['paginate'];
				$limit=(int)$this->queryParams['paginate'];
				$query.=" LIMIT $offset, $limit ";
			}
			$stmt=$this->pdo->prepare($query);
			$stmt->execute($params);
			$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
			// Verkrijg foreign key-relaties
			$foreignKeys=$this->getForeignKeys($table);
			// Verwerk elke rij om gerelateerde gegevens toe te voegen
			foreach($rows as &$row)
			{
				foreach($foreignKeys as $fk)
				{
					if($fk['parent_table']===$table) //&& !empty($this->queryParams['id'])
					{   // Reverse lookup: andere tabel verwijst naar deze tabel
						$relatedTable=$fk['child_table'];
						$relatedColumn=$fk['child_column'];
						$row[$relatedTable]=
							$this->fetchDataRecursive(
								$relatedTable,
								$relatedColumn,
								$row[$fk['parent_column']],
								$visitedTables,
								$depth+1,
								$maxDepth);
					}
					elseif($fk['child_table']===$table)
					{
						// Standaard lookup: deze tabel verwijst naar een andere tabel
						$relatedTable=$fk['parent_table'];
						$relatedColumn=$fk['parent_column'];
						if ($depth + 1 < $maxDepth)
						{
							$stmt=$this->pdo->prepare("SELECT * FROM `$relatedTable` WHERE `$relatedColumn` = :value");
							$stmt->execute(['value'=>$row[$fk['child_column']]]);
							$relatedRows=$stmt->fetchAll(PDO::FETCH_ASSOC);
							// Voeg gerelateerde gegevens toe
							$row[$relatedTable]=$relatedRows;
						}
					}
				}
			}
			return $rows;
		}
		
		/**
		 * Get foreign key relationships for a table, including reverse lookups.
		 */
		private function getForeignKeys($table)
		{
			$stmt=$this->pdo->prepare(
		"
		        SELECT
		            TABLE_NAME AS child_table,
		            COLUMN_NAME AS child_column,
		            REFERENCED_TABLE_NAME AS parent_table,
		            REFERENCED_COLUMN_NAME AS parent_column
		        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
		        WHERE TABLE_SCHEMA = DATABASE()
		        AND (TABLE_NAME = :table OR REFERENCED_TABLE_NAME = :table)
		        AND REFERENCED_TABLE_NAME IS NOT NULL
		    ");
			$stmt->execute(['table'=>$table]);
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}
