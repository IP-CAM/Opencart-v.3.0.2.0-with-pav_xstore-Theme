<?php
class ControllerApiAppBanners extends Controller {

	public function index()
	{
		$this->load->model('design/banner');		

		$banners = array();
		
		//TODO get name or id from mobile extension
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner WHERE name = 'Home Page Slideshow' ");
		$query  = $query->row;

		$results = $this->model_design_banner->getBanner($query['banner_id']);

		foreach ($results as $result) {
			$banners[]= array(
				'id'		=>		$result['banner_image_id'],
				'title'		=>		$result['title'],
				'url'		=>		'1', // like category number : string
				'image'		=>		'image/'.$result['image'],
				'type'		=>		'category', // not found / string : like : category
			);
		}		

		if($banners){
			$json = array('success'=>'1', 'data'=>$banners , 'message'=>"Banners are returned successfull.");
		}
		else{
			$json = array('success'=>'0', 'data'=> array() , 'message'=>"No banners Found .");
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

	
