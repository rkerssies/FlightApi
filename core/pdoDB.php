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
		public $affectedRows = null;
		public $affected = 0;
		public $lastInsert = null;
		
		public function __construct()
		{
			$config = (object)(include("../config/app.php"))->db;
			$attr = [
				PDO::MYSQL_ATTR_FOUND_ROWS=>TRUE,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			];
			if(empty($this->pdo))
			{
				$this->pdo=new \PDO('mysql:host='.$config->host.';dbname='.$config->dbname, $config->user, $config->pass, $attr);
				
			}
		}
		
		public function query($query, $parameters=null)
		{
			$this->pdo->beginTransaction();
			
				$stmt=$this->pdo->prepare($query);
				$this->querySuccess=$stmt->execute();
				$result=null;
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
					$result = true;
					$this->lastInsert=$this->pdo->lastInsertId();
				}
				if(str_contains($query, 'UPDATE') || str_contains($query, 'DELETE'))
				{
					$this->affectedRows = $stmt->rowCount();
				}
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
				return false;   // table-name not found
			}
			return true;
		}
		
		
		/////// RELATIONS ///////
		/**
		 * Fetch data recursively for a table and its related tables while preventing cycles.
		 */
		public function fetchDataRecursive($table, $parentKey=null, $parentValue=null,
						$visitedTables=[], $depth=0, $maxDepth=1|2|3|4)
		{
			// stop recursive if max depth reached, prevent cycle
			if(in_array($table, $visitedTables))
			{
				return [];
			}
			// add current table to list
//			$visitedTables[]=$table;
			$query="SELECT * FROM `$table`";
			$params=[];
			if($parentKey===null && is_numeric($this->queryParams['id']))
			{
				$query.=" WHERE `id` = :id";
				$params=['id'=>$this->queryParams['id']];
			}
			elseif($parentKey && $parentValue)
			{
				$query.=" WHERE `$parentKey` = :value";
				$params=['value'=>$parentValue];
			}
			
			// pagination
			if(empty($this->queryParams['id']) && is_numeric($this->queryParams['page'])
				&& $parentKey===null)
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
			$foreignKeys = $this->getForeignKeys($table);
			// Verwerk elke rij om gerelateerde gegevens toe te voegen
			$visitedTables[] =  $table;
			foreach($rows as &$row)
			{
				foreach($foreignKeys as $fk)
				{
					if($fk['parent_table']===$table) //&& !empty($this->queryParams['id'])
					{   // Reverse lookup: other tabel points to this table
						
						$relatedTable=$fk['child_table'];
						$relatedColumn=$fk['child_column'];
						if(! in_array($relatedTable, $visitedTables)) // add only new relations
						{
							$row[$relatedTable]=$this->fetchDataRecursive($relatedTable,
										$relatedColumn, $row[$fk['parent_column']], $visitedTables,
										$depth+1, $maxDepth);
						}
					}
					elseif($fk['child_table']===$table)
					{
						$relatedTable=$fk['parent_table'];
						$relatedColumn=$fk['parent_column'];
						// Default lookup: this table points to other table
						if(! in_array($relatedTable, $visitedTables) ) // add only new relations
						{
							if ($depth + 1 < $maxDepth)
							{
								$stmt=$this->pdo->prepare("SELECT * FROM `$relatedTable` WHERE `$relatedColumn` = :value");
								$stmt->execute(['value'=>$row[$fk['child_column']]]);
								$relatedRows=$stmt->fetchAll(PDO::FETCH_ASSOC);
								// Voeg gerelateerde gegevens toe
								$row[$relatedTable]=$relatedRows;
							}
							$visitedTables[] = $relatedTable;   // add table to list of found tables
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
