<?php
class ControllerApiAppCategories extends Controller {

	public function index()
	{
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');

		// to get total products
		$filter_data = array(
			'filter_category_id'  => '',
			'filter_sub_category' => true
		);

		$main_cats = $this->model_catalog_category->getCategories();//default parent_id = 0
		$all_cats = array(); //= $main_cats ;		
		foreach ($main_cats as $main_cat) {
			
			// get main category products total
			$filter_data['filter_category_id'] = $main_cat['category_id'];
			$total_products = $this->model_catalog_product->getTotalProducts($filter_data);
			//assign the total
			$main_cat['total_products']	= $total_products ;
			// add it to all
			$all_cats[] = $main_cat ;
			// get sub categories
			$sub_cats = $this->model_catalog_category->getCategories($main_cat['category_id']);
			foreach ($sub_cats as $sub_cat) {
				// get sub category products total
				$filter_data['filter_category_id'] = $sub_cat['category_id'];
				$total_products_sub = $this->model_catalog_product->getTotalProducts($filter_data);
				//assign the total
				$sub_cat['total_products']	= $total_products_sub ;
				$all_cats[] = $sub_cat ;
				//delete from the main total to add to the one that used in app
				$total_products-=$total_products_sub;
			} 
			//add it again for the app
			$main_cat['parent_id'] = $main_cat['category_id'];
			$main_cat['total_products'] = $total_products; // new total
			$all_cats[] = $main_cat ;
		}
		// we get categories in 2 degree and we can take it more but the app have 2 degree just

		//Correct parameters for mobile request
		$all_categories = array();		
		foreach ($all_cats as $all_cat) {			
			$all_categories[]= array(
				'id'						=>		$all_cat['category_id'],
				'image'						=>		'image/'.$all_cat['image'],
				'icon'						=>		'image/'.$all_cat['image'], // not found / string
				'name'						=>		$all_cat['name'],
				'order'						=>		$all_cat['sort_order'],
				'parent_id'					=>		$all_cat['parent_id'],
				'total_products'			=>		$all_cat['total_products'],
			);			

		}		

		if($all_categories){
			$json = array('success'=>'1', 'data'=>$all_categories, 'message'=>"Returned all categories.", 'categories'=>count($all_cats));	
		}
		else{
			$json = array('success'=>'0', 'data'=>array(), 'message'=>"No category found.", 'categories'=>0);
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

	
