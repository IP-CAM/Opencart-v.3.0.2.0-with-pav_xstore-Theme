<?php
class ControllerApiAppGetAllPages extends Controller {

	public function index()
	{
		// Initialize
		$this->load->language('common/footer');
		$this->load->model('catalog/information');
		$information = array();
		foreach ($this->model_catalog_information->getInformations() as $result) {
			$result = $this->model_catalog_information->getInformation($result['information_id']) ;
			//var_dump();die();
			$information [] = array(
				'page_id'				=> $result['information_id'],
				'slug'					=> $result['meta_title'],
				'status'				=> $result['status'],
				'page_description_id'	=> $result['information_id'], // Not FOUND : and not used in app
				'name'					=> $result['title'],
				'description'			=>  html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'), // content
				'language_id'			=> $result['language_id'],
			);
			/*if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}*/
		}		

		if($information){
			$json = array('success'=>'1', 'pages_data'=>$information , 'message'=>"successfull !");
		}
		else{
			$json = array('success'=>'0', 'pages_data'=> array() , 'message'=>"Failed !");
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

	
