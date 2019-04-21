<?php
class ControllerApiAppProducts extends Controller {

	public function index()
	{		
		//$this->load->model('catalog/category');
		$this->load->model('catalog/myproduct');		

		//'language_id'		=>		$this->request->post['language_id'],
			//Not need because it must changed at session or config
		//'customers_id'		=>		$this->request->post['customers_id'],
			//not used / theres method for likedproducts (wishlist)


		//TODO	//$this->config->set('config_language_id') = '4';

		//if one product needed : get it and exit , not needed in data array : if we need one product there is a special method
		if(isset($this->request->post['products_id']) && $this->request->post['products_id']){
			$products_id = $this->request->post['products_id'];
			$result = $this->model_catalog_myproduct->getProduct($products_id);			
			if($result){
				$json = array('success'=>'1', 'product_data'=>array($result), 'message'=>"Returned product .", 'total_record'=>count($result));	
			}
			else{
				$json = array('success'=>'0', 'product_data'=>array(), 'message'=>"No proudct found.", 'total_record'=>0);
			}
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return ;
		}

		isset($this->request->post['type']) ? $type = $this->request->post['type']
		: $type = 'XXX';		

		//If wish list we dont need to continue
		if($type == 'wishlist' && $this->checkLogin()){		
			$this->load->model('account/wishlist');			
			$wishlist_r = $this->model_account_wishlist->getWishlist();
			$wishlist = array();
			foreach ($wishlist_r as $item) {
				$wishlist[]=$item['product_id'];
			}
			$result = array();

			if($wishlist){
				$query = $this->model_catalog_myproduct->getProducts(array());
				foreach ($query as $product) {
					if(in_array($product['products_id'],$wishlist)){
						$product['isLiked'] = '1';
						$result[] = $product;
					}
				}				
			}
			if($result){
				$json = array('success'=>'1', 'product_data'=>$result, 'message'=>"Returned all products.", 'total_record'=>count($result));	
			}
			else{				
				$json = array('success'=>'0', 'product_data'=>array(), 
					'message'=>" No product found.", 'total_record'=>0);
			}
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
									
		}elseif ($type == 'wishlist') {
			$json = array('success'=>'0', 'product_data'=>array(), 'message'=>" No product found.", 'total_record'=>0);			
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}		


		// Create filter data
		$filter_data = array() ;

		// category_id
		isset($this->request->post['categories_id']) ? $filter_category_id = $this->request->post['categories_id'] : $filter_category_id = '';
		$filter_data['filter_category_id'] = $filter_category_id;

		//if category check sub cat too 
		!empty($filter_category_id) ? $filter_sub_category = true : $filter_sub_category = false;
		$filter_data['filter_sub_category'] = $filter_sub_category;		

		//If filter add filters
		isset($this->request->post['filters']) ? $filter_filter = $this->request->post['filters']
		: $filter_filter = '' ;
		//$filter_data['filter_filter'] = $filter_filter; // TODO : array => implode

		// If number of products needed / old one 10
		$limit = 10;		
		if(isset($this->request->post['page_number']) && $this->request->post['page_number'] > 0 )
			$page = $this->request->post['page_number'];
		else 
			$page = 1 ;
		$filter_data['start'] = ($page - 1) * $limit ;
		$filter_data['limit'] = $limit ;

		// type :  sortby - sort_order - all - wishlist - special 				
		switch ($type) {
			case 'a to z':				
				$filter_data['sort'] = 'pd.name' ; $filter_data['order'] = 'ASC' ;
				$query = $this->model_catalog_myproduct->getProducts($filter_data);//default data = array()
			break;

			case 'z to a':
				$filter_data['sort'] = 'pd.name' ; $filter_data['order'] = 'DESC' ;
				$query = $this->model_catalog_myproduct->getProducts($filter_data);
			break;

			case 'low to high':
				$filter_data['sort'] = 'p.price' ; $filter_data['order'] = 'ASC' ;
				$query = $this->model_catalog_myproduct->getProducts($filter_data);
			break;

			case 'high to low':
				$filter_data['sort'] = 'p.price' ; $filter_data['order'] = 'DESC' ;
				$query = $this->model_catalog_myproduct->getProducts($filter_data);
			break;

			case 'all': // not find it in the app
				// delete all
				unset($filter_data['filter_category_id']);
				unset($filter_data['filter_sub_category']);
				unset($filter_data['filter_filter']);
				unset($filter_data['start']);
				unset($filter_data['limit']);
				$query = $this->model_catalog_myproduct->getProducts($filter_data);				
			break;			

			case 'top seller':
				$filter_data['sort'] = 'p.sort_order' ; $filter_data['order'] = 'ASC' ;
				$query = $this->model_catalog_myproduct->getBestSellerProducts($filter_data['limit']);
			break;

			case 'most liked':
				$filter_data['sort'] = 'p.sort_order' ; $filter_data['order'] = 'ASC' ;
				$query = $this->model_catalog_myproduct->getPopularProducts($filter_data['limit']);
			break;

			case 'special':
				$filter_data['sort'] = 'p.sort_order' ; $filter_data['order'] = 'ASC' ;
				$query = $this->model_catalog_myproduct->getProductSpecials($filter_data);
			break;
			
			default: // default & $type == newst
				$filter_data['sort'] = 'p.sort_order' ; $filter_data['order'] = 'ASC' ;
				$query = $this->model_catalog_myproduct->getProducts($filter_data);
			break;
		}			
		
		//DEBUG : $query = $this->db->query('SELECT * FROM '.DB_PREFIX.'product WHERE status = 1 AND quantity > 0 AND stock_status_id > 0 AND date_available <= NOW() ')->rows;
		

		$result = $query;

		// Check if filter price		
		if(isset($this->request->post['price']['minPrice']) && isset($this->request->post['price']['maxPrice']) && $this->request->post['price']['minPrice'] >= 0  && $this->request->post['price']['maxPrice'] >= 0 ){
			$result_old = $result;
			$result = array();
			// if the filter valid check products prices and unset the invalid ones
			foreach ($result_old as $result_old_element) {				
					if( ($result_old_element['products_price'] >= $this->request->post['price']['minPrice'] ) && ($result_old_element['products_price'] <= $this->request->post['price']['maxPrice']) ){						
						$result[] = $result_old_element; 
					}					
				}
		}

		// If login then correct : isLiked
		if($this->checkLogin()){
			// get wishlist
			$this->load->model('account/wishlist');
			$wishlist_r = $this->model_account_wishlist->getWishlist($this->customer->getId());
			$wishlist = array();
			foreach ($wishlist_r as $item) {
				$wishlist[]=$item['product_id'];
			}

			// change result
			if($wishlist){	
				//use key value to change the orginal array			
				foreach ($result as $key => $value) {
					if(in_array($result[$key]['products_id'],$wishlist)){
						$result[$key]['isLiked'] = '1';	
					}					
				}				
			}
		}		
		

		if($result){
			$json = array('success'=>'1', 'product_data'=>$result, 'message'=>"Returned all products.", 'total_record'=>count($result));	
		}
		else{
			$json = array('success'=>'0', 'product_data'=>array(), 'message'=>"No product found.", 'total_record'=>0);
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return ;		
	}

	public function likeProduct()
	{
		// result at default is false
		$result = false ;
		//Check if logged and isset product_id and bigger than zero
		if($this->checkLogin() && isset($this->request->post['liked_products_id']) && $this->request->post['liked_products_id'] > 0 ){	
			//Load model	
			$this->load->model('account/wishlist');
			// get product ID
			$product_id = $this->request->post['liked_products_id'];
			//add product to wishlist
			$this->model_account_wishlist->addWishlist($product_id);			
			//get Customer wishlist
			$result = $this->model_account_wishlist->getWishlist();
			//var_dump($result);die();			
		}		
		
		if($result){
			$json = array('success'=>'1', 'product_data'=> $result,  'message'=>"Product is liked.");
		}
		else{
			$json = array('success'=>'0', 'product_data'=> array(),  'message'=>"Failed !");
		}		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return ;
	}

	public function unlikeProduct()
	{
		// result at default is false
		$result = false ;
		//Check if logged and isset product_id and bigger than zero
		if($this->checkLogin() && isset($this->request->post['liked_products_id']) && $this->request->post['liked_products_id'] > 0 ){	
			//Load model	
			$this->load->model('account/wishlist');
			// get product ID
			$product_id = $this->request->post['liked_products_id'];
			//add product to wishlist
			$this->model_account_wishlist->deleteWishlist($product_id);			
			//get Customer wishlist
			$result = $this->model_account_wishlist->getWishlist();
			//var_dump($result);die();			
		}		
		
		if($result){
			$json = array('success'=>'1', 'product_data'=> $result,  'message'=>"Product is unliked.");
		}
		else{
			$json = array('success'=>'0', 'product_data'=> array(),  'message'=>"Failed !");
		}		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return ;
	}

	public function getFilters() // the right name get Category products attributes
	{
		/*$this->load->model('catalog/category');
		for ($i=0; $i < 100 ; $i++) { 
			$x = $this->model_catalog_category->getCategoryFilters($i);
			var_dump($x);
		}*/
		// category_id

		// result at default is false
		//$result = false;
		//Get category products
		/*$filter_data = array();
		if (isset($this->request->post['categories_id']) && !empty($this->request->post['categories_id']) ){
			$filter_category_id = $this->request->post['categories_id'] ;
			$filter_data['filter_sub_category'] = $filter_sub_category;		
		}
		$filter_data['filter_category_id'] = $filter_category_id
		$this->load->model('catalog/myproduct');
		$x = $this->model_catalog_myproduct->getProductOptions(30);
		var_dump($x);die();*/
		
		// For now we just return max price filter
		$max = $this->db->query("SELECT MAX(price) as max FROM " . DB_PREFIX . "product ");
		$max = $max->row['max'] ;
		if($max){
			$json = array('success'=>'1', 'filters'=> array(), 'message'=>"Returned all filters successfully.", 'maxPrice'=> $max);	
		}
		else{
			$json = array('success'=>'0', 'filters'=> array(), 'message'=>"Returned all filters successfully.", 'maxPrice'=> '1000');
		}
				
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return ;
		/*
		Filter1 / array (2 elements)
				option 1 / object
					name => {}
				values 1 / array ( multi elements)
					value 1 : {}
					value 2 : {}

		*/
	}

	public function getSearchData()
	{
		//Initilize 
		$find_something = false ;
		$this->load->model('catalog/category');
		$this->load->model('catalog/myproduct');
		// check search value
		if (isset($this->request->post['searchValue']) && !empty($this->request->post['searchValue'])) {
			// Set search details array : 4 arrays: mainCategories - subCategories - manufacturers - products
			$result = array();
			$searchValue = $this->request->post['searchValue'] ;
			// ### 1- main 2- sub Categories ###
			$result['mainCategories'] = array();
			$result['subCategories'] = array();

			$categories_1 = $this->model_catalog_category->getCategories(0);
			foreach ($categories_1 as $category_1) {				

				$categories_2 = $this->model_catalog_category->getCategories($category_1['category_id']);
				foreach ($categories_2 as $category_2) {
					if (stripos( $category_2['name'], $searchValue) !== false) {
						$find_something = true ;
						$result['subCategories'][] = array(
							'id' => $category_2['category_id'],
							'image'        => 'image/' . $category_2['image'],
							'name'    => $category_2['name']
						);
					}
				}
				if (stripos( $category_1['name'], $searchValue) !== false) {
					$find_something = true ;
					$result['mainCategories'][] = array(
						'id' => $category_1['category_id'],
						'image'        => 'image/' . $category_1['image'],
						'name'    => $category_1['name']
					);
				}
			}
			// ### 3- manufacturers not found ###
			$result['manufacturers'] = array();
			// ### 4- products ###
			// Set sort and order to default because not recived from request
			$sort = 'p.sort_order' ; 
			$order = 'ASC' ;
			// all search - tag - description have one value in app
			$search = $tag = $description = $searchValue ;
			$filter_data = array(
				'filter_name'         => $search,
				'filter_tag'          => $tag,
				'filter_description'  => $description,
				// no specified category in app request
				/*'filter_category_id'  => $category_id,
				'filter_sub_category' => $sub_category,*/
				'sort'                => $sort,
				'order'               => $order,
				//  no page recived from request too
				/*'start'               => ($page - 1) * $limit,
				'limit'               => $limit*/
			);

			$product_total = $this->model_catalog_myproduct->getTotalProducts($filter_data);	
			
			$result['products'] = array();
			if($product_total){
				$find_something = true ;
				$result['products'] = $this->model_catalog_myproduct->getProducts($filter_data);
			}

			//var_dump($result);die();
		}

		if($find_something){
			$json = array('success'=>'1', 'product_data'=>$result,  'message'=>"Returned all searched products.", 'total_record'=>$product_total);
		}
		else{
			$json = array('success'=>'0', 'product_data'=>$result,  'message'=>"Search result is not found.", 'total_record'=> 0);
		}
				
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return ;
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
		
		$result = json_decode($result);
		return $result;
	}

}

	
/*

down vote
accepted
I found my answer here: php $_POST array empty upon form submission. Retrofit automatically sets the Content-Type to "application/json; charset=UTF-8", which in PHP versions 5.0 - 5.19, there is a bug causing the request body to not be parsed into the $_POST variable. The server I am using is running PHP 5.10 (which I have no control over).

The workaround for this bug is to parse the JSON data yourself from the raw request body:

if($_SERVER['CONTENT_TYPE'] === 'application/json; charset=UTF-8') {
    $_POST = json_decode(file_get_contents('php://input'));
}
*/