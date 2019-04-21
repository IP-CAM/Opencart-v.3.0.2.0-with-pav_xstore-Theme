<?php
class ControllerApiAppChecklogin extends Controller {

	public function index()
	{
		$logStatus = $this->checkLogin();

		if($logStatus){
			$json = array('success'=>'1', 'data'=>$this->customer->getId() , 'message'=>"Customer is logged in.");
		}
		else{
			$json = array('success'=>'0', 'data'=> '0' , 'message'=>"Failed.");
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
	}

	public function logout()
	{
		$json = array();

		if ($this->customer->isLogged()) {
			// delete token 		
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET remember_token = NULL WHERE customer_id = '" . (int)$this->customer->getId()  . "'");

			$this->customer->logout();

			unset($this->session->data['shipping_address']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_address']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);

			//for Api
			unset($this->session->data['customer']);
		}else{
			// customer still Logged
			$json = array('success'=>'0', 'data'=> '' , 'message'=>"Failed. No Customer");
		}

		if (!$json && !$this->customer->isLogged()) {
			// customer logged out
			$json = array('success'=>'1', 'data'=>'' , 'message'=>"Customer is logged out .");
		}
		else if (!$json) {
			// customer still Logged
			$json = array('success'=>'0', 'data'=> '' , 'message'=>"Failed.");
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

	
