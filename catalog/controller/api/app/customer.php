<?php
class ControllerApiAppCustomer extends Controller {
	

	public function processRegistration() {

		//Load files
		$this->load->language('api/customer');
		$this->load->model('account/customer');

		// Delete past customer in case there is an error
		unset($this->session->data['customer']);

		$json = array();
		
		{
			// Add keys for missing post vars
			$keys = array(
				'1' => 'customers_firstname',
				'2' => 'customers_lastname',
				'3' => 'customers_email_address',
				'4' => 'customers_password',
				'5' => 'customers_telephone',				
				//'customer_group_id',				
			);

			// The new keys
			$new_keys = array(
				'1' => 'firstname',
				'2' => 'lastname',
				'3' => 'email',
				'4' => 'password',
				'5' => 'telephone',	
			);			

			/*For 'form' type
			isset($this->request->files['customers_picture']) ? $image = $this->request->files['customers_picture'] : $image = '';*/

			isset($this->request->post['customers_picture']) ? $image = $this->request->post['customers_picture'] : $image = '';
			if(isset($image)){
				$upload = $this->upload($image , 'base64');
				if(!isset($upload['error'])){
					$image = $upload['code'];
				}
			}

			$data = array();

			$data['custom_field']['account'] = array('1' => $image);
			
			foreach ($keys as $key => $value) {				
				if (isset($this->request->post[$value])) {
					$data[$new_keys[$key]] = $this->request->post[$value] ;
				}else{
					$json['error'] =array(true);
					$this->request->post[$key] = '';
				}
			}			

			// check post first name
			if ((utf8_strlen(trim($data['firstname'])) < 1) || (utf8_strlen(trim($data['firstname'])) > 32)) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}

			// check post last name
			if ((utf8_strlen(trim($data['lastname'])) < 1) || (utf8_strlen(trim($data['lastname'])) > 32)) {
				$json['error']['lastname'] = $this->language->get('error_lastname');
			}

			// check post email
			if ((utf8_strlen($data['email']) > 96) || (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))) {
				$json['error']['email'] = $this->language->get('error_email');
			}

			// Check if email exist
			if ($this->model_account_customer->getTotalCustomersByEmail($data['email'])) {
				$json['error'] = true ;
			}

			// check post password
			if ((utf8_strlen($data['password']) < 4) || (utf8_strlen(trim($data['password'])) > 32) ) {
				$json['error'] = true ;
			}

			// check post telephone
			if ((utf8_strlen($data['telephone']) < 3) || (utf8_strlen($data['telephone']) > 32)) {
				$json['error']['telephone'] = $this->language->get('error_telephone');
			}

			// Customer Group
			/*if (is_array($this->config->get('config_customer_group_display')) && in_array($this->request->post['customer_group_id'], $this->config->get('config_customer_group_display'))) {
				$customer_group_id = $this->request->post['customer_group_id'];
			} else {
				$customer_group_id = $this->config->get('config_customer_group_id');
			}*/
			

			if (!$json) {				
				$customer_id = $this->model_account_customer->addCustomer($data);
				$json = array('success'=>'1', 'message'=>"Sign Up successfully!") ;
			}else{
				$json = array('success'=>'0', 'message'=>"Sign Up Error !");
			}

		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function processLogin() {

		//Load files
		$this->load->language('api/customer');
		$this->load->model('account/customer');				

		$json = array();
		if(false){

		}
		else
		{
			// Add keys for missing post vars
			$keys = array(
				'1' => 'customers_email_address',
				'2' => 'customers_password',
			);

			// The new keys
			$new_keys = array(
				'1' => 'email',
				'2' => 'password',
			);			

			$data = array();
			
			foreach ($keys as $key => $value) {				
				if (isset($this->request->post[$value])) {
					$data[$new_keys[$key]] = $this->request->post[$value] ;
				}else{
					$json['error'] = true;
					$this->request->post[$key] = '';
				}
			}
			
			
			// check post email
			if ((utf8_strlen($data['email']) > 96) || (!filter_var($data['email'], FILTER_VALIDATE_EMAIL))) {
				$json['error']['email'] = $this->language->get('error_email');
			}			

			// check post password
			if ((utf8_strlen($data['password']) < 4) || (utf8_strlen(trim($data['password'])) > 32) ) {
				$json['error']['password'] = 'password' ;
			}

			// add 
			$loginAttempts = 0;
			$getLoginAttempts = $this->model_account_customer->getLoginAttempts($data['email']) ;
			if($getLoginAttempts){
				if(isset($getLoginAttempts))
					$loginAttempts = $getLoginAttempts['total'];
			}
				
			if($loginAttempts < 5){
				$this->model_account_customer->addLoginAttempt($data['email']);					
			}else
				$json['error']['attempts'] = 'attempts';

			if (!$json) {												

				// if login success // $this->customer->islogged()
				if($this->customer->login($data['email'] , $data['password']) ){

					// Delete past customer in case there is an error
					unset($this->session->data['customer']);

					// Delete last tries to login
					$this->model_account_customer->deleteLoginAttempts($data['email']);
					
					// return customer json with user Data - Details
					$json = $this->returnCustomerJson('Login successfully!');

				}else
					$json = array('success'=>'0','data' => array() , 'message'=>"Failed to login!"  , 'token' => '');
				
			}else{
				$json = array('success'=>'0', 'data' => array() , 'message'=>"Failed to login!" , 'token' => '');
			}

		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function returnCustomerJson($succes_message = 'Success !' , $is_update = false)
	{
					// get Picture
					//$this->load->model('account/customer');
					$this->load->model('account/wishlist');
					$this->load->model('tool/upload');

					$imageName = 'profile.png';$upload = '';
					$customer_from_database =
						 $this->model_account_customer->getCustomer($this->customer->getId());
					$code = $customer_from_database['custom_field'];
					if($code){
						$code = json_decode($code , true);
						if(isset($code['1'])){
							$code = $code['1'];
							$upload = $this->model_tool_upload->getUploadByCode($code);
						}
						if($upload)
							$imageName = $upload['filename'];
					}					
					
					// get Picture

					// get like products -wish list-					
					$liked_products = array();
					$wishlist = $this->model_account_wishlist->getWishlist();					
					foreach ($wishlist as $p_detail_array ) {
						$liked_products[] = array( 'products_id' => $p_detail_array['product_id'] );
					}					
					// get like products -wish list-

					//get number verified
					//$is_number_verify = $customer_from_database['is_number_verify'];

					$userDetails = array(); //List
					$userDetails[] = array(
						'customers_id' => $this->customer->getId(),
						'customers_firstname' => $this->customer->getFirstName(),
						'customers_lastname' => $this->customer->getLastName(),
						'customers_dob' => '1992',
						'customers_gender' => '0',
						'customers_picture' => 'image/users-profiles/' . $imageName,
						'customers_email_address' => $this->customer->getEmail(),
						'customers_password' => 'XXXXX',
						'customers_telephone' => $this->customer->getTelephone(),
						'customers_fax' => $customer_from_database['fax'],
						'customers_newsletter' => $this->customer->getNewsletter(),
						'fb_id' => 'XXXXX',
						'google_id' => 'XXXXX',
						'isActive' => '1', // is Active sure because he signed in
						'customers_default_address_id' => $this->customer->getAddressId(),
						'liked_products' => $liked_products,
						'is_number_verify' => $this->getVerifyNumberStatus(), 
					);
					$is_update == false ?
					$json = array('success'=>'1','data' => $userDetails , 'message'=>$succes_message , 'token' =>  $this->createCustomerSession() ) 
					: $json = array('success'=>'1','data' => $userDetails , 'message'=>$succes_message , 'token' => '')  ;
					
					return $json;				
	}

	private function getVerifyNumberStatus()
	{
		if(!$this->customer->isLogged())
			return 0;		
		$query = $this->db->query("SELECT is_number_verify FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$this->customer->getId() . "'");
		if($query->num_rows){			
			return $query->row['is_number_verify'];
		}
		return 0;		
	}

	public function facebookRegistration() {

		$this->load->model('account/customer');$this->customer->logout();

		// If is already signed in
		if ($this->customer->isLogged()) {			
			$json = $this->returnCustomerJson('Login successfully!');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		$this->load->language('account/login');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateFacebook()) {
			// Delete past customer in case there is an error
			unset($this->session->data['customer']);

			// return customer json with user Data - Details
			$json = $this->returnCustomerJson('Login successfully!');

		}
		else
			$json = array('success'=>'0', 'data' => array() , 'message'=>"Failed to login!" , 'token' => '');


		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}	


	protected function validateFacebook() {
		
		isset($this->request->post['access_token']) ? $access_token = $this->request->post['access_token'] 
		: $access_token = '';		

		$app_id = $this->config->get('module_facebook_login_facebook_app_id');
		$secret_key = $this->config->get('module_facebook_login_facebook_secret_key');

		$fb = new \Facebook\Facebook([
		  'app_id' => $app_id,
		  'app_secret' => $secret_key,
		    'default_graph_version' => 'v2.2', ]);		

		try {			
		   $response = $fb->get('/me?fields=id,name,email,first_name,last_name,gender,public_key,picture', $access_token);		
		} catch(\Facebook\Exceptions\FacebookResponseException $e) {
		   	$error =  'Graph returned an error: ' . $e->getMessage();
		   	$responseData = array('success'=>'0', 'data'=>$error , 'message'=>'');			
			//echo json_encode($responseData);
		} catch(\Facebook\Exceptions\FacebookSDKException $e) {
		   $error =  'Graph returned an error: ' . $e->getMessage();
		   	$responseData = array('success'=>'0', 'data'=>$error , 'message'=>'');			
			//echo json_encode($responseData);			
		} catch(\Exception $e) {
		   $error =  'Graph returned an error: ' . $e->getMessage();
		   	$responseData = array('success'=>'0', 'data'=>$error . (String)$e , 'message'=>'');	
			//echo json_encode($responseData);			
		}
		if(isset($response) && $response){

			$user = $response->getGraphUser();
			$email = $user['email'];

			if ($email) {
				
				$customer_info = $this->model_account_customer->getCustomerByEmail($email);
				
				// not Exist so Register
				if (!$customer_info) {
					$customer_info = array();
					$customer_info['email'] = $email;
					$customer_info['password']=  rand(10000,99999);
					$customer_info['firstname'] = $user['first_name'];
					$customer_info['lastname'] = $user['last_name'];
					$customer_info['telephone'] = '0';					

					//for small image : $image = $user['picture']['url'];
					isset($user['picture']) ?
						$image = 'https://graph.facebook.com/'.$user['id']
						.'/picture?type=large&access_token='.$access_token
						: $image = '';
					if($image){
						$upload = $this->upload($image , 'website');
						if(!isset($upload['error'])){
							$image = $upload['code'];
						}
					}
					$customer_info['custom_field']['account'] = array('1' => $image) ;
					

					$this->model_account_customer->addSocialCustomer($customer_info);
				}

				if ($this->customer->login($email , '' ,true)) {					
					return true;
				}
			}			
		}
		return false;
	}

	public function googleRegistration() {

		$this->load->model('account/customer');$this->customer->logout();

		// If is already signed in
		if ($this->customer->isLogged()) {			
			$json = $this->returnCustomerJson('Login successfully!');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		$this->load->language('account/login');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateGoogle()) {
			// Delete past customer in case there is an error
			unset($this->session->data['customer']);

			// return customer json with user Data - Details
			$json = $this->returnCustomerJson('Login successfully!');

		}
		else
			$json = array('success'=>'0', 'data' => array() , 'message'=>"Failed to login!" , 'token' => '');


		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}	


	protected function validateGoogle() {
		
		isset($this->request->post['idToken']) ? $idToken = $this->request->post['idToken'] 
		: $idToken = '';

		
		//$data['callback_url'] = trim( $this->config->get('module_google_login_callback_url') );
        //$data['client_secret'] = trim( $this->config->get('module_google_login_client_secret') );
        $CLIENT_ID =  trim( $this->config->get('module_google_login_client_id') );
				
		$client = new \Google_Client(['client_id' => $CLIENT_ID]);
		// Specify the CLIENT_ID of the app that accesses the backend

		$payload = $client->verifyIdToken($idToken);
		
		if($payload){
			// If request specified a G Suite domain:
		    //$domain = $payload['hd'];		      
		    //$google_id =  $payload['sub'];	
			$email = $payload['email'];
			if ($email) {
				
				$customer_info = $this->model_account_customer->getCustomerByEmail($email);
				
				// not Exist so Register
				if (!$customer_info) {
					$customer_info = array();
					$customer_info['email'] = $email;
					$customer_info['password']=  rand(10000,99999);
					$customer_info['firstname'] = $payload['given_name'];
					$customer_info['lastname'] = $payload['family_name'];
					$customer_info['telephone'] = '0';
					
					$image = $payload['picture'];
					if($image){
						$upload = $this->upload($image , 'website');
						if(!isset($upload['error'])){
							$image = $upload['code'];
						}
					}
					$customer_info['custom_field']['account'] = array('1' => $image) ;
					
					$this->model_account_customer->addSocialCustomer($customer_info);
				}

				if ($this->customer->login($email , '' ,true)) {					
					return true;
				}
			}			
		}
		return false;
	}

	public function processForgotPassword()
	{
		// If customer logged in exit
		if($this->customer->isLogged()){
			$json = array('success'=>'0',array() , 'message'=>"You are signed in !");
		}
		else
		{
			//Load Files
			$this->load->language('account/forgotten');
			$this->load->model('account/customer');

			if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForgotPassword()) {
				
				$this->model_account_customer->editCode($this->request->post['customers_email_address'], token(40));
				
				$json = array('success'=>'1', array() , 'message'=>"Your password reset link has been sent to your email address.");
			}
			else
				$json = array('success'=>'0',array() , 'message'=>" Email address doesn't exist !");

		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));

	}

	protected $error;
	protected function validateForgotPassword() {
		if (!isset($this->request->post['customers_email_address'])) {
			$this->error['warning'] = $this->language->get('error_email');
		} elseif (!$this->model_account_customer->getTotalCustomersByEmail($this->request->post['customers_email_address'])) {
			$this->error['warning'] = $this->language->get('error_email');
		}
		
		// Check if customer has been approved.
		$customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['customers_email_address']);

		if ($customer_info && !$customer_info['status']) {
			$this->error['warning'] = $this->language->get('error_approved');
		}

		return !$this->error;
	}


	public function updateCustomerInfo() {

		//Load files
		$this->load->language('api/customer');
		$this->load->model('account/customer');
		
		$json = array();		
		
		if(!$this->checkLogin()){
			$json = array('success'=>'0', 'message'=>"You must logged in first !");
		}
		else
		{
			// Add keys for missing post vars
			$keys = array(
				'1' => 'customers_firstname',
				'2' => 'customers_lastname',
				//	'3' => 'customers_email_address',
				'4' => 'customers_password',
				'5' => 'customers_telephone',
				'6' => 'customers_id',				
				//'customer_group_id',				
			);

			// The new keys
			$new_keys = array(
				'1' => 'firstname',
				'2' => 'lastname',
				//'3' => 'email',
				'4' => 'password',
				'5' => 'telephone',	
				'6' => 'customer_id',
			);

			//Initialize Data to save
			$data = array();	

			// get old image
			$customer_from_database = $this->model_account_customer->getCustomer($this->customer->getId());
			if($customer_from_database){
				$code = $customer_from_database['custom_field'];
				if($code){
					$code = json_decode($code , true);
					if(isset($code['1'])){
						$code = $code['1'];
						$data['custom_field']['account'] = array('1' => $code);
					}
				}
			}

			// change old image if there is new
			isset($this->request->post['customers_picture']) ? $image = $this->request->post['customers_picture'] : $image = '';
			if(!empty($image) && strlen($image) > 3){
				$upload = $this->upload($image , 'base64');
				if(!isset($upload['error'])){
					$image = $upload['code'];
					$data['custom_field']['account'] = array('1' => $image);
				}
			}			
			
			foreach ($keys as $key => $value) {				
				if (isset($this->request->post[$value])) {
					$data[$new_keys[$key]] = $this->request->post[$value] ;
				}else{
					$data[$new_keys[$key]] = '' ;
					$json['error'] =array('param' =>true);
					$this->request->post[$key] = '';
				}
			}			

			// check post first name
			if ((utf8_strlen(trim($data['firstname'])) < 1) || (utf8_strlen(trim($data['firstname'])) > 32)) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}

			// check post last name
			if ((utf8_strlen(trim($data['lastname'])) < 1) || (utf8_strlen(trim($data['lastname'])) > 32)) {
				$json['error']['lastname'] = $this->language->get('error_lastname');
			}			

			// check update password
			if(isset($data['password']) && !empty($data['password'])){
				if ((utf8_strlen($data['password']) < 4) || (utf8_strlen(trim($data['password'])) > 32) ) {
					$json['error']['password'] = true ;
				}
			}

			// check post telephone
			/*if ((utf8_strlen($data['telephone']) < 3) || (utf8_strlen($data['telephone']) > 32)) {
				$json['error']['telephone'] = $this->language->get('error_telephone');
			}*/

			// Customer Group
			/*if (is_array($this->config->get('config_customer_group_display')) && in_array($this->request->post['customer_group_id'], $this->config->get('config_customer_group_display'))) {
				$customer_group_id = $this->request->post['customer_group_id'];
			} else {
				$customer_group_id = $this->config->get('config_customer_group_id');
			}*/

			if($data['customer_id'] != $this->customer->getId()){
				$json['error']['customer_id'] = true ;				
			}	

			$string_json = '';
			foreach ($json as $j) {
				foreach ($j as $k => $v) {
					$string_json .= $k;
				}
			}			
			
			if (!$json) {
				// set email in params array to default because that editcustomer method change it !
				$data['email'] = $this->session->data['customer']['email'];
				// update customer data
				$this->model_account_customer->editCustomer($data['customer_id'] , $data);

				// update customer password if exist
				if(isset($data['password']) && !empty($data['password'])){					
					$this->model_account_customer->editPassword($data['email'] , $data['password']);
				}
				
				$json = $this->returnCustomerJson('Modify successfully!' , true); // true : dont create session
			}else{
				$json = array('success'=>'0', 'message'=>$string_json/*"Modify Error !"*/);
			}

		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function createCustomerSession()
	{
		//Create Token
		$token = token(177) . uniqid("" , true); // uniqid(prefix , more_entropy) true :  23 letter
		$token = substr($token, 0 , 200); // to be sure that just 200 letter

		// Save it to database
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET remember_token = '$token' WHERE customer_id = '" . (int)$this->customer->getId()  . "'");
		// create api_token / session_id
		$api_token = substr($token,0,32);
		// get mobile Api status
		$q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "api` WHERE `username` = 'mobileApi' AND status = '1'");
		$api_info = $q->row ;
		// if mobileApi aviable 
		if($api_info){
			// create new session 			
			$session = new Session($this->config->get('session_engine'), $this->registry);
			$session->start($api_token);
			// add session to api_session table
			$this->db->query("INSERT INTO `" . DB_PREFIX . "api_session` SET api_id = '" . $api_info['api_id'] . "', session_id = '" . $this->db->escape($session->getId()) . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', date_added = NOW(), date_modified = NOW()");
			//save api_id in the new session to check it in the next requests
			$session->data['api_id'] = $api_info['api_id'];			
			
			// login the user BY startup.php			
			$session->data['customer'] = array(
				'customer_id'       => $this->customer->getId(),
				'customer_group_id' => $this->customer->getGroupId(),
				'firstname'         => $this->customer->getFirstName(),
				'lastname'          => $this->customer->getLastName(),
				'email'             => $this->customer->getEmail(),
				'telephone'         => $this->customer->getTelephone(),
				'custom_field'      =>  array( '1' => ''),
			);
			// for startup.php
			$session->data['customer']['customer_group_id'] = $this->customer->getGroupId();			

			return $token;
		}
		
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
	

	// $type = form - website - base64
	private function upload($image , $type = false) {
		$this->load->language('tool/upload');

		$json = array();

		if (!empty($this->request->files[$image]['name']) && is_file($this->request->files[$image]['tmp_name']) && $type == 'form') {
			// Sanitize the filename
			$filename = basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode($this->request->files[$image]['name'], ENT_QUOTES, 'UTF-8')));
			

			// Validate the filename length
			if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 64)) {
				$json['error'] = $this->language->get('error_filename');
			}

			// Allowed file extension types
			$allowed = array();

			$extension_allowed = preg_replace('~\r?\n~', "\n", $this->config->get('config_file_ext_allowed'));

			$filetypes = explode("\n", $extension_allowed);

			foreach ($filetypes as $filetype) {
				$allowed[] = trim($filetype);
			}

			if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $allowed)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			// Allowed file mime types
			$allowed = array();

			$mime_allowed = preg_replace('~\r?\n~', "\n", $this->config->get('config_file_mime_allowed'));

			$filetypes = explode("\n", $mime_allowed);

			foreach ($filetypes as $filetype) {
				$allowed[] = trim($filetype);
			}

			if (!in_array($this->request->files[$image]['type'], $allowed)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			// Check to see if any PHP files are trying to be uploaded
			$content = file_get_contents($this->request->files[$image]['tmp_name']);

			if (preg_match('/\<\?php/i', $content)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			// Return any upload error
			if ($this->request->files[$image]['error'] != UPLOAD_ERR_OK) {
				$json['error'] = $this->language->get('error_upload_' . $this->request->files[$image]['error']);
			}
		} 
		elseif($type == 'website' && !empty($image) ){
			$img =  file_get_contents($image);
			$filename = "pic_".time().".jpg";
			$file =  token(32) . '.' . $filename;
			
			// It was in moving :
				//move_uploaded_file($img, DIR_IMAGE .'users-profiles/' . $file);
				// put in writing :
			file_put_contents(DIR_IMAGE .'users-profiles/' . $file , $img);
			
			// Hide the uploaded file name so people can not link to it directly.
			$this->load->model('tool/upload');
			
			$json['code'] = $this->model_tool_upload->addUpload($filename, $file);
			
			$json['success'] = $this->language->get('text_upload');
		}
		elseif($type == 'base64' && !empty($image) ){			
			
			$image = substr($image, strpos($image, ",") + 1);
			$img = base64_decode($image);
						
			$filename = "pic_".time().".jpg";
			$file =  token(32) . '.' . $filename;
			
			// It was in moving :
				//move_uploaded_file($img, DIR_IMAGE .'users-profiles/' . $file);
				// put in writing :
			file_put_contents(DIR_IMAGE .'users-profiles/' . $file , $img);
			
			// Hide the uploaded file name so people can not link to it directly.
			$this->load->model('tool/upload');
			
			$json['code'] = $this->model_tool_upload->addUpload($filename, $file);
			
			$json['success'] = $this->language->get('text_upload');
		}
		else {
			$json['error'] = $this->language->get('error_upload');
		}

		if (!$json) {
			// Delete spaces
			$filename = preg_replace('/\s+/', '', $filename);			

			//$file = $filename . '.' . token(32);
			$file =  token(32) . '.' . $filename;

			move_uploaded_file($this->request->files[$image]['tmp_name'], DIR_IMAGE .'users-profiles/' . $file);

			// Hide the uploaded file name so people can not link to it directly.
			$this->load->model('tool/upload');

			$json['code'] = $this->model_tool_upload->addUpload($filename, $file);

			$json['success'] = $this->language->get('text_upload');
		}

		return $json;

		/*$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));*/
	}

	public function verifyNumber()
	{
		# code...
	}

	public static function doCurl($url , $data)
	{
		//
		// A very simple PHP example that sends a HTTP POST to a remote site
		//		
		$ch = curl_init();

		//curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // added later
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);

		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

		// in real life you should use something like:
		// curl_setopt($ch, CURLOPT_POSTFIELDS, 
		//          http_build_query(array('postvar1' => 'value1')));

		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec ($ch);

		curl_close ($ch);
		return $server_output;
		// further processing ....
		if ($server_output == "OK") { 
			return $server_output;
		} else { 
			return 'Error';
		}


	}

	public static function doHttpNormal($url , $data)
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
