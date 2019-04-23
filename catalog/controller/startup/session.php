<?php
class ControllerStartupSession extends Controller {
	public function index() {

		// IF Mobile API
		if (isset($this->request->get['route']) && substr($this->request->get['route'], 0, 8) == 'api/app/') {
			//FIX OPENCART - RETROFIT ISSUE		
			if(isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json; charset=UTF-8' 
				&& $this->request->server['REQUEST_METHOD'] == 'POST') {
					$_POST = json_decode(file_get_contents('php://input') , true);
					$this->request->post = json_decode(file_get_contents('php://input') ,true);
			}
			//FIX OPENCART - RETROFIT ISSUE			
			
			// Delete outdated sessions after 6 hours
			$this->db->query("DELETE FROM `" . DB_PREFIX . "api_session` WHERE TIMESTAMPADD(HOUR, 6, date_modified) < NOW()");

			// Check if there Authorization token
			if( isset($this->request->post['Authorization']) && !empty($this->request->post['Authorization']) && strlen((String)$this->request->post['Authorization']) > 10 )
			{
				
				$token = $this->request->post['Authorization'] ;				
				$api_token = substr($token,0,32);
				//$this->session->start($api_token);
				//var_dump($this->session);die();
				
				$api_query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "api` `a` LEFT JOIN `" . DB_PREFIX . "api_session` `as` ON (a.api_id = as.api_id) LEFT JOIN " . DB_PREFIX . "api_ip `ai` ON (a.api_id = ai.api_id) WHERE a.status = '1' AND `as`.`session_id` = '" . $this->db->escape($api_token) . "'");
					//Deleted : because we use all ip
							//"AND ai.ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "'");

				//check if there session already :
				if ($api_query->num_rows) { 
					$this->session->start($api_token);
					// keep the session alive
					$this->db->query("UPDATE `" . DB_PREFIX . "api_session` SET `date_modified` = NOW() WHERE `api_session_id` = '" . (int)$api_query->row['api_session_id'] . "'");
				}
				// else : no exist sessions : check if valid then create session
				else
				{
					
					$customer = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE remember_token =  '$token' ");
					$customer = $customer->row;
					//check if user token is valid :
					if($customer){						
						$q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "api` WHERE `username` = 'mobileApi' AND status = '1'");
						$api_info = $q->row ;
						// if mobileApi aviable 
						if($api_info){
							// create new session 
							//$session = new Session($this->config->get('session_engine'), $this->registry);
							$this->session->start($api_token);
							// add session to api_session table
							$this->db->query("INSERT INTO `" . DB_PREFIX . "api_session` SET api_id = '" . $api_info['api_id'] . "', session_id = '" . $this->db->escape($this->session->getId()) . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', date_added = NOW(), date_modified = NOW()");
							//save api_id in the new session to check it in the next requests
							$this->session->data['api_id'] = $api_info['api_id'];
						}
						// login the user BY startup.php
						$this->session->data['customer'] = array(
							'customer_id'       => $customer['customer_id'],
							'customer_group_id' => $customer['customer_group_id'],
							'firstname'         => $customer['firstname'],
							'lastname'          => $customer['lastname'],
							'email'             => $customer['email'],
							'telephone'         => $customer['telephone'],
							'custom_field'      => isset($customer['custom_field']) ? $customer['custom_field'] : array()
						);
						// for startup.php
						$this->session->data['customer']['customer_group_id'] = $customer['customer_group_id'];
												
					}					
				}
				
			}// End of if Authorization
			
			//Change Language
			if( 
				( isset($this->request->post['language_id']) && !empty($this->request->post['language_id']) )
				||
				( isset($this->request->get['language_id']) && !empty($this->request->get['language_id']) )
			 ){			 	
				if($this->request->server['REQUEST_METHOD'] == 'GET')//$_SERVER['REQUEST_METHOD']
					$language_id = (int) $this->db->escape($this->request->get['language_id']) ;
				else
					$language_id = (int) $this->db->escape($this->request->post['language_id']) ;

				$query = $this->db->query("SELECT * FROM ".DB_PREFIX ."language WHERE `language_id` = '" . $language_id . "'");
				if($query->num_rows){					
					$this->session->data['language'] = $query->row['code'];
				}				
			}
			//Change Language
			
			
		}
		elseif (isset($this->request->get['route']) && substr($this->request->get['route'], 0, 4) == 'api/') {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "api_session` WHERE TIMESTAMPADD(HOUR, 1, date_modified) < NOW()");
					
			// Make sure the IP is allowed
			//NEW
			// The error that when 'api' in route we must check if their api_token too by isset
			isset($this->request->get['api_token']) ? $api_token = $this->request->get['api_token']
			: $api_token = '' ;			
			//NEW
			$api_query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "api` `a` LEFT JOIN `" . DB_PREFIX . "api_session` `as` ON (a.api_id = as.api_id) LEFT JOIN " . DB_PREFIX . "api_ip `ai` ON (a.api_id = ai.api_id) WHERE a.status = '1' AND `as`.`session_id` = '" . $this->db->escape($api_token) . "' AND ai.ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "'");
		 
			if ($api_query->num_rows) { // IF the api session (not just session) not deleted start it
				$this->session->start($api_token);
				
				// keep the session alive
				$this->db->query("UPDATE `" . DB_PREFIX . "api_session` SET `date_modified` = NOW() WHERE `api_session_id` = '" . (int)$api_query->row['api_session_id'] . "'");
			}
		} else {
			if (isset($_COOKIE[$this->config->get('session_name')])) {
				$session_id = $_COOKIE[$this->config->get('session_name')];
			} else {
				$session_id = '';
			}
			
			$this->session->start($session_id);
			
			setcookie($this->config->get('session_name'), $this->session->getId(), ini_get('session.cookie_lifetime'), ini_get('session.cookie_path'), ini_get('session.cookie_domain'));	
		}
	}
}