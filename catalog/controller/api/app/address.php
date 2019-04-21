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
					'street'	=> $address['address_1'],// not found , replace with address_1
					'suburb'	=> $address['address_2'],// not found , replace with address_2
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
			$json = array('success'=>'1', 'data'=>$result, 'message'=>"Return shipping addresses successfully");
		}else{			
			$json = array('success'=>'0', 'data'=>$result, 'message'=>"Addresses are not added yet.");
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return;

	}


	public function addShippingAddress()
	{
		//Initilize
		$result = array();
		// check if logged / because this auth method
		if($this->checkLogin() && $this->validateAddress()){
			//load model
			$this->load->model('account/address');
			// prepare data
			$data = array(
				'firstname' 		=> $this->request->post['entry_firstname'],
				'lastname' 		=> $this->request->post['entry_lastname'],
				'company' 		=> '', // not found
				'address_1' 		=> $this->request->post['entry_street_address'],
				'address_2' 		=> '', // not found
				'postcode' 		=> $this->request->post['entry_postcode'],
				'city' 		=> $this->request->post['entry_city'],
				'zone_id' 		=> $this->request->post['entry_zone_id'],
				'country_id' 		=> $this->request->post['entry_country_id'],
				'custom_field' 		=> array(),// array('address' => '')
				// default address_id if any value (!empty) will make the last one default
				'default' 		=> $this->request->post['customers_default_address_id'], 				
			);
			//add address : customer_id , data => return new added address
			$address_id = $this->model_account_address->addAddress($this->customer->getId() , $data);
			// get all customer addresses
			$addresses = $this->model_account_address->getAddresses();
			// if default selected make it default else keep it the old
			$customer_address_id = $this->customer->getAddressId() ;
			if($this->request->post['customers_default_address_id'] && !empty($this->request->post['customers_default_address_id']))
				$customer_address_id = $address_id;
			foreach ($addresses as $address) {
				$result[]=array(
					'address_id'	=> $address['address_id'],
					'gender'	=> '',//$address['gender'], not found
					'company'	=> $address['company'],
					'firstname'	=> $address['firstname'],
					'lastname'	=> $address['lastname'],
					'street'	=> $address['address_1'],// not found , replace with address_1
					'suburb'	=> $address['address_2'],// not found , replace with address_2
					'postcode'	=> $address['postcode'],
					'city'	=> $address['city'],
					'state'	=> '', //$address['state'], // not found
					'countries_id'	=> $address['country_id'],
					'country_name'	=> $address['country'],
					'zone_id'	=> $address['zone_id'],
					'zone_code'	=> $address['zone_code'],
					'zone_name'	=> $address['zone'],
					'default_address'	=> $customer_address_id ,
				);
			}
		}
		if($result){
			$json = array('success'=>'1', 'data'=>$result, 'address_id' => $address_id, 'message'=>"Add shipping address successfully");
		}else{			
			$json = array('success'=>'0', 'data'=>$result, 'message'=>"Failed !. ". $this->myError);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return;

	}

	public function updateShippingAddress()
	{
		//Initilize
		$result = array();
		// check if logged / because this auth method / address to update it /all fields
		if($this->checkLogin() && isset($this->request->post['address_id']) && !empty($this->request->post['address_id']) && $this->validateAddress()){
			//load model
			$this->load->model('account/address');
			// prepare data
			$data = array(
				'firstname' 		=> $this->request->post['entry_firstname'],
				'lastname' 		=> $this->request->post['entry_lastname'],
				'company' 		=> '', // not found
				'address_1' 		=> $this->request->post['entry_street_address'],
				'address_2' 		=> '', // not found
				'postcode' 		=> $this->request->post['entry_postcode'],
				'city' 		=> $this->request->post['entry_city'],
				'zone_id' 		=> $this->request->post['entry_zone_id'],
				'country_id' 		=> $this->request->post['entry_country_id'],
				'custom_field' 		=> array(),// array('address' => '')
				// default address_id if any value (!empty) will make the last one default
				'default' 		=> $this->request->post['customers_default_address_id'], 				
			);
			// set address_id
			$address_id = $this->request->post['address_id'];
			//edit address : address_id , data => No return 
			$this->model_account_address->editAddress($address_id , $data);
			// get all customer addresses
			$addresses = $this->model_account_address->getAddresses();
			// if default selected make it default 
			$customer_address_id = $this->customer->getAddressId() ;
			if($this->request->post['customers_default_address_id'] && !empty($this->request->post['customers_default_address_id']))
				$customer_address_id = $address_id;
			foreach ($addresses as $address) {
				$result[]=array(
					'address_id'	=> $address['address_id'],
					'gender'	=> '',//$address['gender'], not found
					'company'	=> $address['company'],
					'firstname'	=> $address['firstname'],
					'lastname'	=> $address['lastname'],
					'street'	=> $address['address_1'],// not found , replace with address_1
					'suburb'	=> $address['address_2'],// not found , replace with address_2
					'postcode'	=> $address['postcode'],
					'city'	=> $address['city'],
					'state'	=> '', //$address['state'], // not found
					'countries_id'	=> $address['country_id'],
					'country_name'	=> $address['country'],
					'zone_id'	=> $address['zone_id'],
					'zone_code'	=> $address['zone_code'],
					'zone_name'	=> $address['zone'],
					'default_address'	=> $customer_address_id ,
				);
			}
		}
		if($result){
			$json = array('success'=>'1', 'data'=>$result, 'message'=>"Update shipping address successfully !");
		}else{			
			$json = array('success'=>'0', 'data'=>$result, 'message'=>"Failed !. ". $this->myError);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return;

	}

	public function updateDefaultAddress()
	{
		//Initilize
		$result = array();
		// check if logged : because this auth method / address to update it as default
		if($this->checkLogin() && isset($this->request->post['address_id']) && !empty($this->request->post['address_id']) ){
			//set default address id
			$address_id = $this->request->post['address_id'] ;
			// update default address
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$this->customer->getId() . "'");			
			
			//load model
			$this->load->model('account/address');
			// get all customer addresses
			$addresses = $this->model_account_address->getAddresses();
			foreach ($addresses as $address) {
				$result[]=array(
					'address_id'	=> $address['address_id'],
					'gender'	=> '',//$address['gender'], not found
					'company'	=> $address['company'],
					'firstname'	=> $address['firstname'],
					'lastname'	=> $address['lastname'],
					'street'	=> $address['address_1'],// not found , replace with address_1
					'suburb'	=> $address['address_2'],// not found , replace with address_2
					'postcode'	=> $address['postcode'],
					'city'	=> $address['city'],
					'state'	=> '', //$address['state'], // not found
					'countries_id'	=> $address['country_id'],
					'country_name'	=> $address['country'],
					'zone_id'	=> $address['zone_id'],
					'zone_code'	=> $address['zone_code'],
					'zone_name'	=> $address['zone'],
					'default_address'	=> $address_id ,
				);
			}
		}
		if($result){
			$json = array('success'=>'1', 'data'=>$result, 'message'=>"Update default address successfully !");
		}else{			
			$json = array('success'=>'0', 'data'=>$result, 'message'=>"Failed. ". $this->myError);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return;

	}

	public function deleteShippingAddress()
	{
		//Initilize
		$result = array();
		// check if logged : because this auth method / address to delete 
		if($this->checkLogin() && isset($this->request->post['address_id']) && !empty($this->request->post['address_id']) ){
			// set deleted address id
			$address_id = $this->request->post['address_id'] ;
			// delete the address
			$this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
			
			//load model
			$this->load->model('account/address');			
			// get all customer addresses
			$addresses = $this->model_account_address->getAddresses();			
			foreach ($addresses as $address) {
				$result[]=array(
					'address_id'	=> $address['address_id'],
					'gender'	=> '',//$address['gender'], not found
					'company'	=> $address['company'],
					'firstname'	=> $address['firstname'],
					'lastname'	=> $address['lastname'],
					'street'	=> $address['address_1'],// not found , replace with address_1
					'suburb'	=> $address['address_2'],// not found , replace with address_2
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
			$json = array('success'=>'1', 'data'=>$result, 'message'=>"Address has been Deleted !");
		}else{			
			$json = array('success'=>'0', 'data'=>$result, 'message'=>"Failed !. ". $this->myError);
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return;

	}

	private $myError = "";
	private function validateAddress()
	{
		$filters = array(
			'entry_firstname',
			'entry_lastname',				
			'entry_street_address',
			'entry_postcode',
			'entry_city',
			'entry_zone_id',
			'entry_country_id',
			'customers_default_address_id',
		);
		foreach ($filters as $filter) {
			if(!isset($this->request->post[$filter])){
				$this->myError = "not enough information";
				return false;
			}
		}
		return true;
		
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

	
