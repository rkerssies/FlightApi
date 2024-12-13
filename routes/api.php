<?php
	/**
	 * Project: PhpStorm.
	 * Author:  InCubics
	 * Date:    23/11/2023
	 * File:    api.php
	 */
	
	/* ***************************************************
		this file contains  GENERIC  API-requests
	*************************************************** */
	
	/*  SELECT some DATA   */
	
	Flight::route('GET /@table', function($table)
	{   ///eq: http://flightapi.rk/beers   or  http://flightapi.rk/beers?paginate=3  or  http://flightapi.rk/beers?paginate=5&page=3
		/// http://flightapi.rk/beers?related=1 or http://flightapi.rk/beers?page=3&paginate=10&related=1
		
		$this->request->get->table = $table;
		
		$this->request->values['table']  = $table;           // used for RBAC
		$this->request->values['rbac']   = $table."-all";    // used for RBAC
		
		$jwtObj = (new \core\jwtAuth($this->config->jwt, $this->db, $this->host));
		if(! $jwtObj->checkPermissions($this->request->values['rbac']))
		{   // check: token isn't provided, token is invalid or RBAC in token is invalid
			$this->messageResponse      = $jwtObj->jwtFail;
			$this->statusResponse       = 403;  // Forbidden, no valid access-rights/permissions
		}
		elseif(!$this->db->tableExists($table)){
			$this->dataResponse = false;
			$this->statusResponse = 412;
			$this->messageResponse='table-name is incorrect or unknown; '.$table;
			$this->response->validation  = ['tablename' => 'unknonw', 'table' => $table];
		}
		else    {
			if(!empty($jwtObj->jwtSuccess)) { $this->response->token_payload  = $jwtObj->jwtSuccess; }
			
			// setup pagination
			$paginate = $this->config->noPagination;   // get default limit of paginated
			$page = 1;
			if(!empty($this->request->get->related))
			{
				if(empty($this->request->get->pagination) && empty($this->request->get->page)) {
					$paginate = $this->config->defafaultPagination;  // using only pages uses the default amount of records to paginate
				}
			}
			if( !empty($this->request->get->paginate)
				|| !empty($this->request->get->page) )
			{ // not related, but with a param
				if(!empty($this->request->get->page) && is_numeric($this->request->get->page)){
					$page = $this->request->get->page;
				}
				
				if(!empty($this->request->get->paginate) && is_numeric($this->request->get->paginate))
				{
					$paginate = $this->request->get->paginate;
				}
				elseif(empty($this->request->get->pagination)) {
					$paginate = $this->config->defafaultPagination;  // using only pages uses the default amount of records to paginate
				}
			}
			
			$this->response->total  = $this->db->query('SELECT COUNT(*) as `total` FROM '.strtolower($table)) [0]['total'];  // max records in table, useful for calculating the amount of pages with pagination
			
			// Append response with related-data (on request: index.php?related=1 )
			if(!empty($this->request->get->related) && is_numeric($this->request->get->related))
			{

				if (!in_array($this->request->get->related, [1,2,3,4])) {
					$this->dataResponse = ['message' => 'URLkey \'related\' with incorrect max recursive depth. Acceeptehd depth-values are:  1,2,3, or 4 '];
				}
				else    {
					$this->db->queryParams = ['id' => null, 'page'=> $page, 'paginate' => $paginate];
					$this->dataResponse =  $this->db->fetchDataRecursive($table, null, null, [], 0, $this->request->get->related);
					$this->db->queryParams = [];
				}
			}
			else    {
				$this->dataResponse = $this->db->query('SELECT * FROM `'.strtolower($table).'` LIMIT '.(($page-1)*$paginate).','.$paginate);// paginate ?
			}
			
			if(is_array($this->dataResponse)) { // empty array; no records found
				$this->messageResponse      = 'requested records from '.$table.' page '.$page.' of paginated per '.$paginate.' records';
				$this->statusResponse       = 200;
				$this->successResponse      = true;
			}
			else {
				$this->error('NOT FOUND: no records found for '.$table, 404);
			}
		}
	});
	
	
	Flight::route('GET /@table/@id:[0-9]+', function($table, $id = null)
	{   ///eq: http://flightapi.rk/beers/9   http://flightapi.rk/beers/13?related=3
		
		$this->request->get->table = $table;
		$this->request->get->id = $id;
		
		$this->request->values['id']        = (int) $id;
		$this->request->values['table']     = $table;           // used for RBAC
		$this->request->values['action']    = "find";           // used for RBAC
		$this->request->values['rbac']      = $table.'-find';   // used for RBAC
		
		$jwtObj = (new \core\jwtAuth($this->config->jwt, $this->db, $this->host));
		if(! $jwtObj->checkPermissions($this->request->values['rbac']))
		{   // check: token isn't provided, token is invalid or RBAC in token is invalid
			$this->messageResponse      = $jwtObj->jwtFail;
			$this->statusResponse       = 403;  // Forbidden, no valid access-rights/permissions
		}
		elseif(!$this->db->tableExists($table)){
			$this->dataResponse = false;
			$this->statusResponse = 412;
			$this->messageResponse='table-name is incorrect or unknown; '.$table;
			$this->response->validation  = ['tablename' => 'unknonw', 'table' => $table];
		}
		else    {
			if(!empty($jwtObj->jwtSuccess)) { $this->response->token_payload  = $jwtObj->jwtSuccess; }

			// Append response with related-data (on request: index.php?related=1 )

			if (!empty($this->request->get->related) && !in_array($this->request->get->related, [1,2,3,4]))
			{
				$this->dataResponse = ['message' => 'URLkey \'related\' with incorrect max recursive depth. Acceeptehd depth-values are:  1, 2, 3, or 4'];
			}
			elseif(!empty($this->request->get->related) && in_array($this->request->get->related, [1,2,3]))
			{

				$this->db->queryParams=['id'=>$id, 'page'=>null, 'paginate'=>null];
				$this->dataResponse=$this->db->fetchDataRecursive($table, null, null, [], 0, $this->request->get->related);
			}
			else    {
				$this->dataResponse = $this->db->query('SELECT * FROM '.strtolower($table).' WHERE id = '.$id);
			}
			
			if(is_array($this->dataResponse) && !empty($this->dataResponse)) {
				$this->messageResponse      = 'requested record-id '.$id.' from '.$table;
				$this->statusResponse       = 200;
				$this->request->values['id'] = $id;
				$this->successResponse      = true;
			}
			else    {
				$this->error('NOT FOUND: record-id '.$id.' NOT found in '.$table, 404);
			}
		}
	});
	
	
/*  ***************************************************
	CREATE  - UPDATE - DELETE - softdelete - restore
  *************************************************** */
	
	Flight::route('POST /@table/create', function($table)
	{   // ADD
		
		$this->request->get->table = $table;
		$this->request->get->action = 'create';
		
		$this->request->values['table']     = $table;           // used for RBAC
		$this->request->values['action']    = "create";         // used for RBAC
		$this->request->values['rbac']      = $table.'-create'; // used for RBAC
		
		$jwtObj = (new \core\jwtAuth($this->config->jwt, $this->db, $this->host));
		if(! $jwtObj->checkPermissions($this->request->values['rbac']))
		{   // check: token isn't provided, token is invalid or RBAC in token is invalid
			$this->messageResponse      = $jwtObj->jwtFail;
			$this->statusResponse       = 403;  // Forbidden, no valid access-rights/permissions
		}
		elseif($this->db->tableExists($table))
		{
			if(!empty($jwtObj->jwtSuccess)) { $this->response->token_payload  = $jwtObj->jwtSuccess;}
			$validated = $this->requestValidation->validator($this->request->post);
			
			if(! $validated)    {
				$this->successResponse  = false;
				$this->messageResponse  = 'validation FAILED on submitted data';
				$this->response->validation  = $this->requestValidation->fails;
				$this->statusResponse  = 400;
			}
			elseif($validated)  {
				$keyString='';
				$valueString='';
				foreach((array)$this->request->post as $key => $value)
				{
					$keyString.= "`".$key.'`,';
					$valueString.="'".$value."',";
				}
				
				$sql = "INSERT INTO ".$table." (".rtrim($keyString, ',').") VALUES (".rtrim($valueString, ',')." )";
				
				$this->dataResponse = $this->dataResponse=$this->db->query($sql);
				
				if($this->dataResponse)
				{
					$this->successResponse=true;
					$this->response->lastinserted=$this->db->lastInsert;
					$this->request->values['id'] = $this->db->lastInsert;
					$this->messageResponse='created new record in table '.$table;
					$this->statusResponse=201;
				}
				else    {
					$this->dataResponse = false;
					$this->error('NO record inserted for '.$table.'. Precondition failed.', 412);
				}
			}
		}
		else {
			$this->dataResponse = false;
			$this->statusResponse = 412;
			$this->messageResponse='table-name is incorrect or unknown; '.$table;
			$this->response->validation  = ['tablename' => 'unknonw', 'table' => $table];
		}
	});
	
	Flight::route('PUT /@table/edit/@id:[0-9]+', function($table, $id)
	{   // EDIT
		$this->request->get->table = $table;
		$this->request->get->id = $id;
		
		$this->request->get->table = $table;
		$this->request->get->action = 'edit';
		$this->request->get->id = $id;
		
		$this->request->values['id'] = (int) $id;
		$this->request->values['table']     = $table;           // used for RBAC
		$this->request->values['action']    = "edit";           // used for RBAC
		$this->request->values['rbac']      = $table.'-edit';   // used for RBAC
		
		$jwtObj = (new \core\jwtAuth($this->config->jwt, $this->db, $this->host));
		if(! $jwtObj->checkPermissions($this->request->values['rbac']))
		{   // check: token isn't provided, token is invalid or RBAC in token is invalid
			$this->messageResponse      = $jwtObj->jwtFail;
			$this->statusResponse       = 403;  // Forbidden, no valid access-rights/permissions
		}
		if(!$this->db->tableExists($table)){
			$this->dataResponse = false;
			$this->statusResponse = 412;
			$this->messageResponse='table-name is incorrect or unknown; '.$table;
			$this->response->validation  = ['tablename' => 'unknonw', 'table' => $table];
		}
		else
		{
			if(!empty($jwtObj->jwtSuccess)) { $this->response->token_payload  = $jwtObj->jwtSuccess; }
			$validated = $this->requestValidation->validator((object)$this->request->put);
			
			if( ! $validated) // failed
			{
				$this->successResponse  = false;
				$this->messageResponse  = 'validation FAILED on submitted data';
				$this->response->validation  = $this->requestValidation->fails;
				$this->statusResponse  = 400;
			}
			elseif($validated) // valid
			{
				$setString='';
				foreach((array)$this->request->put as $key=>$value) // PostMan needs "put" and React needs "post"
				{
					$setString.="`".$key."`='".$value."', ";
				}
				$sql = "UPDATE `".$table."` SET ".rtrim($setString, ', ')." WHERE `id` = '".$id."'";
				$this->dataResponse=$this->db->query($sql);
				if($this->db->affectedRows > 0)
				{
					$this->request->put=(object)array_merge(['id'=>$id], (array)$this->request->put);
					$this->dataResponse=true;
					$this->successResponse=true;
					$this->request->values['id']=$id;
					$this->response->affectedrows = $this->db->affectedRows;
					$this->messageResponse='updated record-id '.$id.' in table '.$table;
					$this->statusResponse=200;
				}
				else    {
					$this->dataResponse = false;
					$this->error('record-id '.$id.' NOT updated in '.$table.', incorrect data provided', 400);
				}
			}
		}
	});
	
	Flight::route('DELETE /@table/destroy/@id:[0-9]+', function($table, $id)
	{   // DELETE
		$this->request->get->table = $table;
		$this->request->get->id = $id;
		
		$this->request->values['id'] = (int) $id;
		$this->request->values['table']     = $table;               // used for RBAC
		$this->request->values['action']    = "destroy";            // used for RBAC
		$this->request->values['rbac']      = $table.'-destroy';    // used for RBAC
		
		$jwtObj = (new \core\jwtAuth($this->config->jwt, $this->db, $this->host));
		if(! $jwtObj->checkPermissions($this->request->values['rbac']))
		{   // check: token isn't provided, token is invalid or RBAC in token is invalid
			$this->messageResponse      = $jwtObj->jwtFail;
			$this->statusResponse       = 403;  // Forbidden, no valid access-rights/permissions
		}
		elseif(!$this->db->tableExists($table)){
			$this->dataResponse = false;
			$this->statusResponse = 412;
			$this->messageResponse='table-name is incorrect or unknown; '.$table;
			$this->response->validation  = ['tablename' => 'unknonw', 'table' => $table];
		}
		else
		{   // table exists
			if(!empty($jwtObj->jwtSuccess))
			{
				$this->response->token_payload=$jwtObj->jwtSuccess;
			}
			if($this->db->query('SELECT COUNT(`id`) AS count FROM '.$table.' WHERE id = "'.$id.'"')[0]['count']>0)
			{   // record exists
				$this->db->query('DELETE FROM '.$table.' WHERE id = "'.$id.'"');
				if($this->db->affectedRows>0)
				{ // success
					$this->dataResponse=true;
					$this->successResponse=true;
					$this->request->values['id']=$id;
					$this->response->affectedrows = $this->db->affectedRows;
					$this->messageResponse='deleted record-id '.$id.' in table '.$table;
					$this->statusResponse=200;
				}
				else
				{ // failed
					$this->dataResponse=false;
					$this->error('record-id '.$id.' NOT destroyed (deleted) in '.$table, 400);
				}
			}
			else
			{   // record doesn't exist
				$this->dataResponse=false;
				$this->successResponse=false;
				$this->request->values['id']=$id;
				$this->messageResponse='record-id '.$id.' to destroy NOT FOUND in table '.$table;
				$this->statusResponse=400; // bad request
			}
		}
	});
	
	Flight::route('DELETE /@table/trash/@id:[0-9]+', function($table, $id)
	{   // TRASH
		$this->request->get->table = $table;
		$this->request->get->id = $id;
		
		$this->request->values['id'] = (int) $id;
		$this->request->values['table']     = $table;           // used for RBAC
		$this->request->values['action']    = 'trash';          // used for RBAC
		$this->request->values['rbac']      = $table.'-trash';  // used for RBAC
		
		$jwtObj = (new \core\jwtAuth($this->config->jwt, $this->db, $this->host));
		if(! $jwtObj->checkPermissions($this->request->values['rbac']))
		{   // check: token isn't provided, token is invalid or RBAC in token is invalid
			$this->messageResponse      = $jwtObj->jwtFail;
			$this->statusResponse       = 403;  // Forbidden, no valid access-rights/permissions
		}
		elseif(!$this->db->tableExists($table)){
			$this->dataResponse = false;
			$this->statusResponse = 412;
			$this->messageResponse='table-name is incorrect or unknown; '.$table;
			$this->response->validation  = ['tablename' => 'unknonw', 'table' => $table];
		}
		else    {
			if(!empty($jwtObj->jwtSuccess)) { $this->response->token_payload  = $jwtObj->jwtSuccess; }
			if( ! (bool) $this->db->query('SELECT count(`TABLE_NAME`)as count FROM INFORMATION_SCHEMA.COLUMNS
                                   WHERE TABLE_SCHEMA = "'.$this->config->db['dbname'].'" AND TABLE_NAME = "'.$table.'" AND COLUMN_NAME ="deleted_at"')[0]['count'])
			{ // column deleted_at not found, no soft-deletion support
				$this->dataResponse     = false;
				$this->successResponse  = false;
				$this->messageResponse  = 'soft-deletion or restoring NOT SUPPORTED on '.$table.', column `deleted_at` (nullable) is missing';
			}
			elseif( $this->db->query('SELECT COUNT(`id`) AS count FROM '.$table.' WHERE id = "'.$id.'"')[0]['count'] > 0)
			{ // record exists
				$this->db->query('UPDATE '.$table.' SET deleted_at ="'.date('Y-m-d H:i:s').'" WHERE id = "'.$id.'"');
				if($this->db->affectedRows > 0)
				{  // success
					$this->dataResponse     = true;
					$this->successResponse  = true;
					$this->messageResponse  = 'trashed (soft-deleted) record-id '.$id.' in table '.$table ;
					$this->statusResponse   = 200;
				}
				else    {
					$this->dataResponse     = false;
					$this->error('record-id '.$id.' NOT trashed (soft-deleted) in '.$table, 400);
				}
			}
			else    {   // record doesn't exist
				$this->dataResponse     = false;
				$this->successResponse  = false;
				$this->request->values['id']=$id;
				$this->response->affectedrows = $this->db->affectedRows;
				$this->messageResponse  = 'record-id '.$id.' to trash NOT FOUND in table '.$table;
				$this->statusResponse   = 400; // bad request
			}
		}
	});
	
	Flight::route('DELETE /@table/restore/@id:[0-9]+', function($table, $id)
	{   // RESTORE
		$this->request->get->table = $table;
		$this->request->get->id = $id;
		
		$this->request->values['id'] = (int) $id;
		$this->request->values['table']     = $table;               // used for RBAC
		$this->request->values['action']    = "restore";            // used for RBAC
		$this->request->values['rbac']       = $table.'-restore';   // used for RBAC
		
		$jwtObj = (new \core\jwtAuth($this->config->jwt, $this->db, $this->host));
		if(! $jwtObj->checkPermissions($this->request->values['rbac']))
		{   // check: token isn't provided, token is invalid or RBAC in token is invalid
			$this->messageResponse      = $jwtObj->jwtFail;
			$this->statusResponse       = 403;  // Forbidden, no valid access-rights/permissions
		}
		elseif(!$this->db->tableExists($table)){
			$this->dataResponse = false;
			$this->statusResponse = 412;
			$this->messageResponse='table-name is incorrect or unknown; '.$table;
			$this->response->validation  = ['tablename' => 'unknonw', 'table' => $table];
		}
		else    {
			if(!empty($jwtObj->jwtSuccess)) { $this->response->token_payload  = $jwtObj->jwtSuccess; }
			if( ! (bool) $this->db->query('SELECT count(`TABLE_NAME`)as count FROM INFORMATION_SCHEMA.COLUMNS
                                   WHERE TABLE_SCHEMA = "'.$this->config->db['dbname'].'" AND TABLE_NAME = "'.$table.'" AND COLUMN_NAME ="deleted_at"')[0]['count'])
			{ // column deleted_at not found, no soft-deletion support
				$this->dataResponse     = false;
				$this->successResponse  = false;
				$this->messageResponse  = 'soft-deletion or restoring NOT SUPPORTED on '.$table.', column `deleted_at` (nullable) is missing';
			}
			elseif( $this->db->query('SELECT COUNT(`id`) AS count FROM '.$table.' WHERE id = "'.$id.'"')[0]['count'] > 0)
			{ // record exists
				$this->db->query('UPDATE '.$table.' SET deleted_at = null WHERE id = "'.$id.'"');
				if($this->db->affectedRows > 0) {  // success
					$this->dataResponse     = true;
					$this->successResponse  = true;
					$this->messageResponse  = 'restored trashed (soft-deleted) record-id '.$id.' in table '.$table ;
					$this->statusResponse   = 200;
				}
				else    {
					$this->dataResponse     = false;
					$this->error('record-id '.$id.' NOT restored (soft-deleted) in '.$table, 400);
				}
			}
			else    {   // record doesn't exist
				$this->dataResponse     = false;
				$this->successResponse  = false;
				$this->request->values['id']=$id;
				$this->response->affectedrows = $this->db->affectedRows;
				$this->messageResponse  = 'record-id '.$id.' to restore NOT FOUND in table '.$table;
				$this->statusResponse   = 400; // bad request
			}
		}
	});
	
	

