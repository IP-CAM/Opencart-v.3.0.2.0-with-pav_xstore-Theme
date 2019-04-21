<?php
class ControllerApiAppNews extends Controller {

	public function index()
	{		
		// Pavo Blog
		//$this->load->language( 'extension/module/pavoblog' );
        //$this->load->model( 'extension/pavoblog/category' );
        $this->load->model( 'extension/pavoblog/post' );
        //$this->load->model( 'tool/image' );

        // Initialize
        $news = array();
        $news_number = 0;
        $data = array();

        // get page number
        if (isset($this->request->post['page_number']) && !empty($this->request->post['page_number'])) {
        	$data['limit'] = 20 ; // 20 default page contains
        	if((int) $this->request->post['page_number'] > 0)
        		$data['start'] = ($this->request->post['page_number'] - 1) * 10 ;        	
        }

        // get featured
        if (isset($this->request->post['is_feature']) && !empty($this->request->post['is_feature'])) {
        	$data['featured'] = (int) $this->request->post['is_feature'] ;
        }        

        // get category id
        if (isset($this->request->post['categories_id']) && !empty($this->request->post['categories_id'])) {
        	$data['category_id'] = (int) $this->request->post['categories_id'] ;
        }

        // get News
        $results = $this->model_extension_pavoblog_post->getPosts($data);        

        if($results){
        	// correct News for mobile response
        	foreach ($results as $result) {
        		// not found BUT not used in App
        		$categories_id = "";
        		$news_url = '/index.php?route=extension/pavoblog/single&pavo_post_id='.$result['post_id'] ;
        		// not found
	        	$news[] = array(
	        		'news_id'		=> $result['post_id'],
	        		'news_image'	=> 'image/'.$result['image'],
	        		'news_date_added'		=> $result['date_added'],
	        		'news_last_modified'	=> $result['date_modified'],
	        		'news_status'	=> $result['status'],
	        		'language_id'	=> $result['language_id'],
	        		'news_name'	=> $result['name'],
	        		//— Convert HTML entities to their corresponding characters
	        		'news_description'	=>  html_entity_decode($result['content'], ENT_QUOTES, 'UTF-8'), // description in app mean content here
	        		'news_url'	=> $news_url,
	        		'news_viewed'	=> $result['viewed'],
	        		'categories_id'	=> $categories_id,	        		
	        	);
        	}
        	// get count of news
        	$news_number = count($news);
        }        

        if($news){
			$json = array('success'=>'1', 'news_data'=>$news , 'message'=>"News Categories are returned successfull." , 'total_record' => $news_number );
		}
		else{
			$json = array('success'=>'0', 'news_data'=> array() , 'message'=>"No News Categories Found! ." , 'total_record' => 0);
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
		
	}

	public function allNewsCategories()
	{
		// Pavo Blog
		//$this->load->language( 'extension/module/pavoblog' );
        $this->load->model( 'extension/pavoblog/category' );
        //$this->load->model( 'extension/pavoblog/post' );
        //$this->load->model( 'tool/image' );

        // Initialize
        $categories = array();
        $categories_number = 0;
        $data = array();

        // get page number
        if (isset($this->request->post['page_number']) && !empty($this->request->post['page_number'])) {
        	$data['limit'] = 20 ; // 20 default page contains
        	if((int) $this->request->post['page_number'] > 0)
        		$data['start'] = ($this->request->post['page_number'] - 1) * 10 ;        	
        }

        // get Categories
        $results = $this->model_extension_pavoblog_category->getCategories($data);

        if($results){
        	// correct categories for mobile response
        	foreach ($results as $result) {	        	
	        	$categories[] = array(
	        		'id'	=> $result['category_id'],
	        		'image'	=> 'image/'.$result['image'],
	        		'name'	=> $result['name'],
	        	);
        	}
        	// get count of categories
        	$categories_number = count($categories);
        }        

        if($categories){
			$json = array('success'=>'1', 'data'=>$categories , 'message'=>"News Categories are returned successfull." , 'categories' => $categories_number );
		}
		else{
			$json = array('success'=>'0', 'data'=> array() , 'message'=>"No News Categories Found! ." , 'categories' => 0);
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

	
