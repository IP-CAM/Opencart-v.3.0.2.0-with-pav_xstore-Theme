<?php
class ControllerApiAppAddress extends Controller {

	public function index()
	{
		
	}

	public function getCountries()
	{
		$country_data = $this->cache->get('country.catalog');
		if (!$country_data) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE status = '1' ORDER BY name ASC");
			$country_data = $query->rows;
			$this->cache->set('country.catalog', $country_data);			
		}		
		$result = array();
		foreach ($country_data as $country) {
			$result[] = array(
				'id'=> $country['country_id'],
				'sortname'=> $country['iso_code_3'],
				'name'=> $country['name'],
				'phonecode'=> $country['country_id'] // no phonecode so we use country id
			);
		}
		
		if($result){
			$json = array('success'=>'1', 'data'=>$result, 'message'=>"Returned all countries.");
		}else{
			$json = array('success'=>'0', 'data'=> $result , 'message'=>"No countries.");
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return;
		
	}

	public function getZones()
	{
		$result = array();
		if(isset($this->request->post['zone_country_id']) && !empty($this->request->post['zone_country_id'])){
			$country_id = $this->request->post['zone_country_id'] ;
			$zone_data = $this->cache->get('zone.' . (int)$country_id);
			if (!$zone_data) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE country_id = '" . (int)$country_id . "' AND status = '1' ORDER BY name");
				$zone_data = $query->rows ;
				$this->cache->set('zone.' . (int)$country_id, $zone_data);
			}			
			foreach ($zone_data as $zone) {
				$result[] = array(
					'id'=> $zone['zone_id'],					
					'name'=> $zone['name'],
					'country_id'=> $zone['country_id'] 
				);
			}
		}
		if($result){
			$json = array('success'=>'1', 'data'=>$result, 'message'=>"Returned all zones.");
		}else{
			$result[] = array(
					'id'=> '0',					
					'name'=> 'Default',
					'country_id'=> isset($this->request->post['zone_country_id']) ? $this->request->post['zone_country_id'] : '0',
			);
			$json = array('success'=>'1', 'data'=> $result , 'message'=>"No zone was found.");
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return;
		
	}

	public function getAllAddress()
	{
		//Initilize
		$result = array();
		// check if logged / because this auth method
		if($this->checkLogin()){
			//load model
			$this->load->model('account/address');
			$addresses = $this->model_account_address->getAddresses();
			foreach ($addresses as $address) {
				$result[]=array(
					'address_id'	=> $address['address_id'],
					'gender'	=> '',//$address['gender'], not found
					'company'	=> $address['company'],
					'firstname'	=> $address['firstname'],
					'lastname'	=> $address['lastname'],
					'street'	=> $address['address_2'],// not found , replace with address_2
					'suburb'	=> $address['address_1'],// not found , replace with address_1
					'postcode'	=> $address['postcode'],
					'city'	=> $address['city'],
					'state'	=> '', //$address['state'], // not found
					'countries_id'	=> $address['country_id'],
					'country_name'	=> $address['country'],
					'zone_id'	=> $address['zone_id'],
					'zone_code'	=> $address['zone_code'],
					'zone_name'	=> $address['zone'],
					'default_address'	=> $this->customer->getAddressId() ,
				);
			}
		}
		if($result){
			$json = array('success'=>'1', 'data'=>$result, 'message'=>json_encode($this->customer->isLogged())."Return shipping addresses successfully");
		}else{			
			$json = array('success'=>'0', 'data'=>$result, 'message'=>json_encode($this->customer->isLogged())."Addresses are not added yet.");
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return;

	}


	private function checkLogin()
	{			
		// if user logged in return true
		if($this->customer->isLogged())
			return true;
		// else check if session Login
		elseif(isset($this->session->data['customer']) && $this->session->data['customer'] ){
			//try to logged in
			if ($this->customer->login($this->session->data['customer']['email'] , '' , true) )
				return true;
		}
		return false;
	}
		

	

	private static function doHttpNormal($url , $data)
	{
		$options = array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => 'GET',
		        'content' => http_build_query($data)
		    )
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		if ($result === FALSE) { /* Handle error */ }

		//var_dump($result);
		$result = json_decode($result);
		return $result;
	}

}

	
