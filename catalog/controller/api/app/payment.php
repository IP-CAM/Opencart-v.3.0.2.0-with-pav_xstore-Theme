<?php
class ControllerApiAppPayment extends Controller {

	public function index()
	{
	}

	private $myError = '';	

	public function getPaymentMethods()
	{			
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
					if ($product['minimum'] > $request_product['products_quantity']/*$product_total*/) {
						$this->myError = "The product '".$product['name']."' low than '".$product['minimum']."'";					
					}
					// calculate tax for the product
					if($product['tax_class_id']){
						$products_taxes[$request_product['products_id']] =  $this->tax->calculate($product['price'] * $request_product['products_quantity'] , $product['tax_class_id'], $this->config->get('config_tax'));
						$total_tax += $products_taxes[$request_product['products_id']];
					}
					
					if (isset($product['option'])) { // TODO Later
						$option = $product['option'];
					} else {
						$option = array();
					}

					$this->cart->add($request_product['products_id'], $request_product['products_quantity'], $option);
				}
			}
			else{
				// check if there was added product in get shipping methods
				if(!$this->cart->hasProducts())
					$this->myError = 'No Products Found !' ;
			}			
			// 2- add shipping address / payment address id to session & delete old
			// unset old shipping address & payment address
			if(!$this->myError){
				unset($this->session->data['shipping_address']);
				unset($this->session->data['payment_address']);
				// get default address / in app the customer default address set in shipping but not in billing
				$this->load->model('account/address');
				// Shipping Address is the default
				$this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
				// get Payment Address
				if(isset($this->request->post['payment_address_id']) && !empty($this->request->post['payment_address_id']) ){
					$billing_address_id = $this->request->post['payment_address_id'] ;
				}
				else
					$billing_address_id = $this->customer->getAddressId();
				// save it to session
				$this->session->data['payment_address']  = $this->model_account_address->getAddress($billing_address_id);
			}
			

			// 3- return Payment methods
			if (isset($this->session->data['shipping_address']) && isset($this->session->data['payment_address']) && !$this->myError) {

				// unset shipping info after add address / cart
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);

				// Totals
				$totals = array();
				$taxes = $this->cart->getTaxes();				
				$total = 0;

				// Because __call can not keep var references so we put them into an array. 
				$total_data = array(
					'totals' => &$totals,
					'taxes'  => &$taxes,
					'total'  => &$total
				);

				$this->load->model('setting/extension');

				$sort_order = array();

				$results = $this->model_setting_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);
						
						// We have to put the totals in an array so that they pass by reference.
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}


				$payment_methods = array();

				$this->load->model('setting/extension');

				$results = $this->model_setting_extension->getExtensions('payment');

				$recurring = $this->cart->hasRecurringProducts();

				foreach ($results as $result) {
					if ($this->config->get('payment_' . $result['code'] . '_status')) {
						$this->load->model('extension/payment/' . $result['code']);

						$method = $this->{'model_extension_payment_' . $result['code']}->getMethod($this->session->data['payment_address'], $total);

						if ($method) {
							if ($recurring) {
								if (property_exists($this->{'model_extension_payment_' . $result['code']}, 'recurringPayments') && $this->{'model_extension_payment_' . $result['code']}->recurringPayments()) {
									$payment_methods[$result['code']] = $method;
								}
							} else {
								$payment_methods[$result['code']] = $method;
							}
						}
					}
				}				

				$sort_order = array();

				foreach ($payment_methods as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}

				array_multisort($sort_order, SORT_ASC, $payment_methods);
				

				if ($payment_methods) {
					$this->session->data['payment_methods'] = $payment_methods;
				}else
					$this->myError = 'Error in returning payment methods';
				
			
			} // end if shipping_address && payment_methods & !Error
			else{
				$this->myError = $this->myError ?  $this->myError : 'no Addresses'  ;
			}
			
			// if found method data rebuild result for mobile app else Error
			if(!$this->myError){
				if(isset($payment_methods) && $payment_methods){
					$result = array();
					foreach ($payment_methods as $key => $value) {
						if(is_array($value)){
							$value['key'] = $key;
							$result[] = $value;
						}
					}	
				}else
					$this->myError = 'Error in fetching data' ;
			}			
			//echo json_encode(array('success'=>'0', 'data'=> array() , 'message'=>"Failed ." . $this->myError .  json_encode($result)  ));die();
			
		} // end if isLogged
		else{
			$this->myError = 'Error in Authenication';
		}
		if(!$this->myError){
			$json = array('success'=>'1', 'data'=>$result , 'message'=>" successfull.");
		}
		else{
			$json = array('success'=>'0', 'data'=> array() , 'message'=>"Failed ." . $this->myError);
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
		
		$result = json_decode($result);
		return $result;
	}

}

	
