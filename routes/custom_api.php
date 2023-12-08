<?php
	/**
	 * Project: PhpStorm.
	 * Author:  InCubics
	 * Date:    23/11/2023
	 * File:    custom_api.php
	 */

	
	/* ***************************************************
			this file contains  CUSTOM  API-requests
	*************************************************** */
	
	
		Flight::route('GET /bars/totals', function()
		{
			$requiredPermission = 'bars-totals';        // required permission in JWT-token to get data, defined in /config/rbac.php
			
			$jwtObj = (new \core\jwtAuth($this->config->jwt, $this->db, $this->host));
			if(! $jwtObj->checkPermissions($requiredPermission))
			{   // check: token isn't provided, token is invalid or RBAC in token is invalid
				$this->messageResponse      = $jwtObj->jwtFail;
				$this->statusResponse       = 403;  // Forbidden, no valid access-rights/permissions
			}
			elseif($this->db->tableExists('customers') && $this->db->tableExists('orders'))
			{
				if(!empty($jwtObj->jwtSuccess)) { $this->response->token_payload  = $jwtObj->jwtSuccess; }
				
				$sql='SELECT c.id,
					        c.barname,
					        c.city,
					        COUNT(o.customer_id) as totalOrders,
							SUM(b.amount) as total_amount
						FROM   customers c
						LEFT JOIN orders as o
							ON c.id = o.customer_id
			                LEFT JOIN order_beer as b
								ON o.id = b.order_id
						GROUP BY c.id, c.barname
						ORDER BY c.barname';

				$this->dataResponse=$this->db->query($sql);
				if(is_array($this->dataResponse))
				{ // empty array; no records found
					$this->messageResponse = 'barnames and there count of orders and totals of beer-units';
					$this->statusResponse  = 200;
					$this->successResponse = true;
					$this->request->values['action'] = 'totals';
				}
				else
				{
					$this->error('NOT FOUND: no dataset found of bars and there totals', 404);
				}
			}

		});
