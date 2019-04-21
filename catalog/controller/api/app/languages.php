<?php
class ControllerApiAppLanguages extends Controller {

	public function index()
	{
		//$this->load->language('common/language');
		
		$this->load->model('localisation/language');		

		$results = $this->model_localisation_language->getLanguages();

		$languages = array();

		foreach ($results as $result) {
			if ($result['status']) {
				//direction - is_default : not founded : so : 
				if ($result['code'] == 'ar') {
					//$result['direction'] = 'rtl'; // Not used in android app
					$result['is_default'] = 1;
					$result['image'] = 'catalog/language/ar/ar.png';
				}elseif($result['code'] == 'en-gb'){
					$result['image'] = 'catalog/language/en-gb/en-gb.png';
				}
				else{
					$result['image'] = 'image/' . $result['image'] ;
				}				
				
				$languages[] = $result;
			}
		}

		if($languages){
			$json = array('success'=>'1', 'languages'=>$languages , 'message'=>"Successfull.");
		}
		else{
			$json = array('success'=>'0', 'languages'=> array() , 'message'=>"Failed.");
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

	
