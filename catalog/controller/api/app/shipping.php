<?php
class ControllerApiAppShipping extends Controller {

	public function index()
	{
	}

	private $myError = '';	

	public function getRate()
	{		
		//var_dump($this->cart->getProducts());var_dump($this->request->post['products']);die();
		//$this->tax->unsetRates(215,3326);
		//$this->tax->setShippingAddress(215,3326);		
		//var_dump($this->tax->getTax(10,9));die();
		//var_dump($this->cart->getTaxes());die();
		// Initilize
		$result = array();
		$total_tax = 0;
		$products_taxes = array();
		// check If Customer Logged
		if($this->checkLogin()){

			// 1-add products to cart & delete old
			// check if there products  else throw error
			if (isset($this->request->post['products'])) {
				// unset old cart
				$this->cart->clear();
				// load product model
				$this->load->model('catalog/product');
				// add each product to the cart
				foreach ($this->request->post['products'] as $request_product) {
					// get product details from database (in request no minimum for example)
					$product = $this->model_catalog_product->getProduct($request_product['products_id']);
					// check minimum for every product 
					if ($product['minimum'] > $product['quantity']/*$product_total*/) {
						$this->myError = "The product '".$product['name']."' low than '".$product['minimum']."'";					
					}
					// calculate tax for the product
					/* ERROR NOT CORRECT @@@!!!
					if($product['tax_class_id']){
						$products_taxes[$request_product['products_id']] =  $this->tax->calculate($product['price'] * $request_product['products_quantity'] , $product['tax_class_id'], $this->config->get('config_tax'));
						$total_tax += $products_taxes[$request_product['products_id']];
					}*/
					
					if (isset($product['option'])) { // TODO Later
						$option = $product['option'];
					} else {
						$option = array();
					}					

					// IMPORTANT : products_quantity : mean the stock so we use : customers_basket_quantity .
					$this->cart->add($request_product['products_id'], 
						$request_product["customers_basket_quantity"], $option);
				}
			}
			else{				
				$this->myError = 'No Products Found !' ;
			}
			if(!$this->myError){
				// Totals
				$this->load->model('setting/extension');

				$totals = array();
				$taxes = $this->cart->getTaxes();
				$total = 0;

				// Because __call can not keep var references so we put them into an array. 
				$total_data = array(
					'totals' => &$totals,
					'taxes'  => &$taxes,
					'total'  => &$total
				);

				$results = $this->model_setting_extension->getExtensions('total');

				// Sorting the results
				$sort_order = array();
				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}
				array_multisort($sort_order, SORT_ASC, $results);
				// Sorting the results

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);
						
						// We have to put the totals in an array so that they pass by reference.
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}

				// Sorting the totals
				$sort_order = array();
				foreach ($totals as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}
				array_multisort($sort_order, SORT_ASC, $totals);
				// Sorting the totals

				//$json['totals'] = array();

				foreach ($totals as $total) {
					if($total['code'] == 'tax'){
						$total_tax += $total['value'] ;
					}
				}				
			}
			

			// 2- add shipping address id to session & delete old
			// unset old shipping address
			if(!$this->myError){
				unset($this->session->data['shipping_address']);
				// get default address / in app the customer default address set in shipping but not in billing
				$this->load->model('account/address');
				$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
			}

			// 3- return shipping methods with rates
			
			if (isset($this->session->data['shipping_address']) && !$this->myError) {
				// unset shipping / payment info after add address / cart
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);

				// Shipping Methods
				$method_data = array();

				// it loaded : $this->load->model('setting/extension');

				$results = $this->model_setting_extension->getExtensions('shipping');

				foreach ($results as $result) {
					if ($this->config->get('shipping_' . $result['code'] . '_status')) {
						$this->load->model('extension/shipping/' . $result['code']);

						$quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);						

						if ($quote) {
							$method_data[$result['code']] = array(
								'title'      => $quote['title'],
								'quote'      => $quote['quote'],
								'sort_order' => $quote['sort_order'],
								'error'      => $quote['error']
							);
						}
					}
				}

				// if found methods sort the array else error
				if($method_data){
					$sort_order = array();

					foreach ($method_data as $key => $value) {
						$sort_order[$key] = $value['sort_order'];
					}

					array_multisort($sort_order, SORT_ASC, $method_data);

					$this->session->data['shipping_methods'] = $method_data;
					
				}
				else
					$this->myError = 'Error in returning shipping address' ;
			} // end if shipping_address & !Error
			else{				
				$this->myError = 'No shipping_address Found !' ;
			}

			// if found method data rebuild result for mobile app else Error
			if($method_data && !$this->myError){				
				$shippingMethods = array();
				foreach ($method_data as $key => $value) {					
					$shipping_address[]=array(
						'shipping_method'				=> reset($value['quote'])['code'],
						'name'				=> $value['title'],
						'rate'				=> reset($value['quote'])['cost'],
						'currencyCode'		=> $this->session->data['currency'],
						//'code'	=> $key,
					);
				}
				//var_dump($shipping_address);die();
				$result = array(
					'tax' => $total_tax ,
					'shippingMethods' => $shipping_address
				);
			}
			else
				$this->myError = 'Error in fetching data' ;

		} // end if isLogged
		else
				$this->myError = 'Not logged' ;

		if(!$this->myError){
			$json = array('success'=>'1', 'data'=>$result , 'message'=>" successfull.");
		}
		else{
			$json = array('success'=>'0', 'data'=> array('tax' => 0 , 'shippingMethods' => array()) , 'message'=>"Failed ." . $this->myError);
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return;
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

	
