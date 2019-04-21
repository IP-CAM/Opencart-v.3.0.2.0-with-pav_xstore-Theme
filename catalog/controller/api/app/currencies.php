<?php
class ControllerApiAppCurrencies extends Controller {

	public function index()
	{	
		$language_id = (int)$this->config->get('config_language_id');
		$currency_rate = 1;

		$this->load->model('localisation/currency');

		$currencies = array();

		$results = $this->model_localisation_currency->getCurrencies();

		foreach ($results as $result) {
			if ($result['status']) {
				$currencies[] = array(					
					//
					'id' 				=> $result['currency_id'],
					'title' 			=> $result['title'],
					'code' 				=> $result['code'],
					'symbol_left' 		=> $result['symbol_left'],
					'symbol_right' 		=> $result['symbol_right'],
					'decimal_point' 	=> $result['decimal_place'], // not found / char
					'thousands_point' 	=> $result['decimal_place'], // not found / char
					'decimal_places' 	=> $result['decimal_place'],
					'value' 			=> $result['value'],
					'last_updated' 		=> $result['date_modified'],
					'language_id' 		=> $language_id, // not found / int
					'currency_rate' 	=> $currency_rate, // not found / float
				);
			}
		}
		
		if($currencies){
			$json = array('success'=>'1', 'data'=>$currencies , 'message'=>"currencies are returned successfull.");
		}
		else{
			$json = array('success'=>'0', 'data'=> array() , 'message'=>"No currencies Found.");
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
