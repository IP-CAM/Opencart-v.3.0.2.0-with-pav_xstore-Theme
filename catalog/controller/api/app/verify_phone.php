<?php
use \Firebase\JWT\JWT;

class ControllerApiAppVerifyPhone extends Controller {
	public function index() {		
	}

	public function verifyPhoneNumber() {		
		
		$json = array();

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->checkLogin() && isset($this->request->post['phone_token'])) {

			$firebasePublicKey = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com' ;
			try{	    
			$customers_id = $this->customer->getId();
			// get the token from POST request
		    $token = $this->request->post['phone_token'];
		    // initilize variables
		    $decoded = 0; $message=''; 
		    // get firebase public keys : reset(array) to get first element value and for the second : next(array); ,  WE CAN USE array_slice too .   
		    $publicKeys = (array) $this->doHttpNormal($firebasePublicKey , array() , 'get');	    
		    $publicKey2 = reset($publicKeys); // first key
	    	$publicKey = next($publicKeys); // second key
		    
		    // variable for debug
		    $worked_key = '--first_no_error--'; 

		    try{
		    	// for error : time difference
		    	// tokenFirebase\JWT\BeforeValidException: Cannot handle token prior
		    	// Allows a 12000 to avoid the 3 hours error second tolerance on timing checks
		    	//JWT::$leeway = 12000; 
		    	//Or change time zone
		    	//date_default_timezone_set('Asia/Kuwait');// //America/Los_Angeles
		    	//var_dump(date_default_timezone_get());var_dump(date('y-m-d h:i:sa'));die();
		    	// cehck if first worked
		    	$decoded = JWT::decode($token, $publicKey , array('RS256'));  
		    }
		    catch(\Exception $e){
		    	$worked_key = '--first_has_error_second_maybe_worked--';  
		    	try{
			      $decoded = JWT::decode($token, $publicKey2 , array('RS256'));  
			    }
			    catch(\Exception $e){$worked_key = '--first_and_second_have_error_--';  
			          //https://firebase.google.com/docs/auth/admin/verify-id-tokens
			         $message = 'invalid token' .$e ;    
			    }
		    }
		        
		    if($decoded){
		    	$message = 'Verification Success' ;
		    	$this->db->query("UPDATE " . DB_PREFIX . "customer SET is_number_verify = '1' WHERE customer_id = '" . (int)$this->customer->getId() . "'");
		      	$json = array('success' => '1' ,'data' => $decoded->phone_number , 'message' => $message);

		    }
		    else{	    	
		      	$json = array('success' => '0' ,'data' => '0000' , 'message' => $message);
		    }
			}catch(\Exception $e){
				$message = 'Verification Failed' ;
				// data string in android app
				$json = array('success' => '0' ,'data' => (String)$e  , 'message' => $message);
			}			
		}
		else
		{
			$json = array('success' => '0' ,'data' => '', 'message' => 'request');
		}		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
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
	

	private function doHttpNormal($url , $data)
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
