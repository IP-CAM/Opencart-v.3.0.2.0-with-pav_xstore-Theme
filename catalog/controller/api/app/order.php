<?php
class ControllerApiAppOrder extends Controller {

	public function index()
	{
	}	

	public function addToOrder()
	{			
		// Initilize
		$this->load->language('api/order');
		$json = array();


		// check If Customer Logged
		if($this->checkLogin()){

			// payment Address
			if (!isset($this->session->data['payment_address'])) {
				if(!isset($json['error'])) $json['error'] = $this->language->get('error_payment_address');
			}
			// Payment Method
			if (!$json && isset($this->request->post['payment_method']) && !empty($this->request->post['payment_method'])) {
				if (isset($this->session->data['payment_methods']) && empty($this->session->data['payment_methods'])) {
					if(!isset($json['error'])) $json['error'] = $this->language->get('error_no_payment');
				} elseif (
					isset($this->request->post['payment_method']) && 
					!isset($this->session->data['payment_methods'][$this->request->post['payment_method']])) {
					if(!isset($json['error'])) $json['error'] = $this->language->get('error_payment_method');
				}

				if (!$json) {
					$this->session->data['payment_method'] = $this->session->data['payment_methods'][$this->request->post['payment_method']];
				}
			}
			if (!isset($this->session->data['payment_method'])) {
				if(!isset($json['error'])) $json['error'] = $this->language->get('error_payment_method');
			}
			//echo json_encode(array('success'=>'0', 'data'=> array() , 'message'=>"Failed .".json_encode($this->request->post['payment_method']) ) );die();			

			// Shipping
			if ($this->cart->hasShipping()) {
				// Shipping Address
				if (!isset($this->session->data['shipping_address'])) {
					if(!isset($json['error'])) $json['error'] = $this->language->get('error_shipping_address');
				}				

				// Shipping Method
				if (!$json && !empty($this->request->post['shipping_method'])) {
					if (empty($this->session->data['shipping_methods'])) {
						if(!isset($json['error'])) $json['error'] = $this->language->get('error_no_shipping');
					} else {
						$shipping = explode('.', $this->request->post['shipping_method']);

						if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
							if(!isset($json['error'])) $json['error'] = $this->language->get('error_shipping_method');
						}
					}

					if (!$json) {
						$this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
					}
				}



				// Shipping Method
				if (!isset($this->session->data['shipping_method'])) {
					if(!isset($json['error'])) $json['error'] = $this->language->get('error_shipping_method');
				}
			} else {
				unset($this->session->data['shipping_address']);
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				//NEW
				//if(!isset($json['error'])) $json['error'] = $this->language->get('error_shipping_method');
			}

			// Cart
			if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
				if(!isset($json['error'])) $json['error'] = $this->language->get('error_stock');
			}

			// Validate minimum quantity requirements.
			$products = $this->cart->getProducts();

			foreach ($products as $product) {
				$product_total = 0;

				foreach ($products as $product_2) {
					if ($product_2['product_id'] == $product['product_id']) {
						$product_total += $product_2['quantity'];
					}
				}

				if ($product['minimum'] > $product_total) {
					if(!isset($json['error'])) $json['error'] = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);

					break;
				}
			}

			// if not json then => success so adding order now
			if (!$json) {
				$json['success'] = $this->language->get('text_success');				

				$order_data = array();

				// Store Details
				$order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
				$order_data['store_id'] = $this->config->get('config_store_id');
				$order_data['store_name'] = $this->config->get('config_name');
				$order_data['store_url'] = $this->config->get('config_url');

				// Customer Details
				$order_data['customer_id'] = $this->session->data['customer']['customer_id'];
				$order_data['customer_group_id'] = $this->session->data['customer']['customer_group_id'];
				$order_data['firstname'] = $this->session->data['customer']['firstname'];
				$order_data['lastname'] = $this->session->data['customer']['lastname'];
				$order_data['email'] = $this->session->data['customer']['email'];
				$order_data['telephone'] = $this->session->data['customer']['telephone'];
				$order_data['custom_field'] = $this->session->data['customer']['custom_field'];

				// Payment Details
				$order_data['payment_firstname'] = $this->session->data['payment_address']['firstname'];
				$order_data['payment_lastname'] = $this->session->data['payment_address']['lastname'];
				$order_data['payment_company'] = $this->session->data['payment_address']['company'];
				$order_data['payment_address_1'] = $this->session->data['payment_address']['address_1'];
				$order_data['payment_address_2'] = $this->session->data['payment_address']['address_2'];
				$order_data['payment_city'] = $this->session->data['payment_address']['city'];
				$order_data['payment_postcode'] = $this->session->data['payment_address']['postcode'];
				$order_data['payment_zone'] = $this->session->data['payment_address']['zone'];
				$order_data['payment_zone_id'] = $this->session->data['payment_address']['zone_id'];
				$order_data['payment_country'] = $this->session->data['payment_address']['country'];
				$order_data['payment_country_id'] = $this->session->data['payment_address']['country_id'];
				$order_data['payment_address_format'] = $this->session->data['payment_address']['address_format'];
				$order_data['payment_custom_field'] = (isset($this->session->data['payment_address']['custom_field']) ? $this->session->data['payment_address']['custom_field'] : array());

				if (isset($this->session->data['payment_method']['title'])) {
					$order_data['payment_method'] = $this->session->data['payment_method']['title'];
				} else {
					$order_data['payment_method'] = '';
				}

				if (isset($this->session->data['payment_method']['code'])) {
					$order_data['payment_code'] = $this->session->data['payment_method']['code'];
				} else {
					$order_data['payment_code'] = '';
				}

				// Shipping Details
				if ($this->cart->hasShipping()) {
					$order_data['shipping_firstname'] = $this->session->data['shipping_address']['firstname'];
					$order_data['shipping_lastname'] = $this->session->data['shipping_address']['lastname'];
					$order_data['shipping_company'] = $this->session->data['shipping_address']['company'];
					$order_data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
					$order_data['shipping_address_2'] = $this->session->data['shipping_address']['address_2'];
					$order_data['shipping_city'] = $this->session->data['shipping_address']['city'];
					$order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
					$order_data['shipping_zone'] = $this->session->data['shipping_address']['zone'];
					$order_data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
					$order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
					$order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
					$order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
					$order_data['shipping_custom_field'] = (isset($this->session->data['shipping_address']['custom_field']) ? $this->session->data['shipping_address']['custom_field'] : array());

					if (isset($this->session->data['shipping_method']['title'])) {
						$order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
					} else {
						$order_data['shipping_method'] = '';
					}

					if (isset($this->session->data['shipping_method']['code'])) {
						$order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
					} else {
						$order_data['shipping_code'] = '';
					}
				} else {
					$order_data['shipping_firstname'] = '';
					$order_data['shipping_lastname'] = '';
					$order_data['shipping_company'] = '';
					$order_data['shipping_address_1'] = '';
					$order_data['shipping_address_2'] = '';
					$order_data['shipping_city'] = '';
					$order_data['shipping_postcode'] = '';
					$order_data['shipping_zone'] = '';
					$order_data['shipping_zone_id'] = '';
					$order_data['shipping_country'] = '';
					$order_data['shipping_country_id'] = '';
					$order_data['shipping_address_format'] = '';
					$order_data['shipping_custom_field'] = array();
					$order_data['shipping_method'] = '';
					$order_data['shipping_code'] = '';
				}

				// Products
				$order_data['products'] = array();

				foreach ($this->cart->getProducts() as $product) {
					$option_data = array();

					foreach ($product['option'] as $option) {
						$option_data[] = array(
							'product_option_id'       => $option['product_option_id'],
							'product_option_value_id' => $option['product_option_value_id'],
							'option_id'               => $option['option_id'],
							'option_value_id'         => $option['option_value_id'],
							'name'                    => $option['name'],
							'value'                   => $option['value'],
							'type'                    => $option['type']
						);
					}

					$order_data['products'][] = array(
						'product_id' => $product['product_id'],
						'name'       => $product['name'],
						'model'      => $product['model'],
						'option'     => $option_data,
						'download'   => $product['download'],
						'quantity'   => $product['quantity'],
						'subtract'   => $product['subtract'],
						'price'      => $product['price'],
						'total'      => $product['total'],
						'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
						'reward'     => $product['reward']
					);
				}

				// Gift Voucher
				$order_data['vouchers'] = array();

				if (!empty($this->session->data['vouchers'])) {
					foreach ($this->session->data['vouchers'] as $voucher) {
						$order_data['vouchers'][] = array(
							'description'      => $voucher['description'],
							'code'             => token(10),
							'to_name'          => $voucher['to_name'],
							'to_email'         => $voucher['to_email'],
							'from_name'        => $voucher['from_name'],
							'from_email'       => $voucher['from_email'],
							'voucher_theme_id' => $voucher['voucher_theme_id'],
							'message'          => $voucher['message'],
							'amount'           => $voucher['amount']
						);
					}
				}

				// Order Totals
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

				$sort_order = array();

				foreach ($total_data['totals'] as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}

				array_multisort($sort_order, SORT_ASC, $total_data['totals']);

				$order_data = array_merge($order_data, $total_data);

				if (isset($this->request->post['comment'])) {
					$order_data['comment'] = $this->request->post['comment'];
				} else {
					$order_data['comment'] = '';
				}

				// Affiliate
				if (isset($this->request->post['affiliate_id'])) {
					$subtotal = $this->cart->getSubTotal();

					// Affiliate
					$this->load->model('account/customer');

					$affiliate_info = $this->model_account_customer->getAffiliate($this->request->post['affiliate_id']);

					if ($affiliate_info) {
						$order_data['affiliate_id'] = $affiliate_info['customer_id'];
						$order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
					} else {
						$order_data['affiliate_id'] = 0;
						$order_data['commission'] = 0;
					}

					// Marketing
					$order_data['marketing_id'] = 0;
					$order_data['tracking'] = '';
				} else {
					$order_data['affiliate_id'] = 0;
					$order_data['commission'] = 0;
					$order_data['marketing_id'] = 0;
					$order_data['tracking'] = '';
				}

				// Other
				$order_data['language_id'] = $this->config->get('config_language_id');
				$order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
				$order_data['currency_code'] = $this->session->data['currency'];
				$order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
				$order_data['ip'] = $this->request->server['REMOTE_ADDR'];

				if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
					$order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
				} elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
					$order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
				} else {
					$order_data['forwarded_ip'] = '';
				}

				if (isset($this->request->server['HTTP_USER_AGENT'])) {
					$order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
				} else {
					$order_data['user_agent'] = '';
				}

				if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
					$order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
				} else {
					$order_data['accept_language'] = '';
				}

				// Add the order and clear the cart
				$this->load->model('checkout/order');

				$json['order_id'] = $this->model_checkout_order->addOrder($order_data);

				// Set the order history
				/*if (isset($this->request->post['order_status_id'])) {
					$order_status_id = $this->request->post['order_status_id'];
				} else {
					$order_status_id = $this->config->get('config_order_status_id');
				}*/
				$telr_data = array(
					'order_url' => '',
					'order_ref'	=> '',
				);			
				if ($this->session->data['payment_method']['code'] == 'cod' ) {
					if( $this->config->get('payment_cod_order_status_id') != null )
						$order_status_id = $this->config->get('payment_cod_order_status_id') ;
					else // default order status id 
						$order_status_id = $this->config->get('config_order_status_id');

					//Add the Order
					$this->model_checkout_order->addOrderHistory($json['order_id'], $order_status_id);
					//clear cart since the order has already been successfully stored.
					$this->cart->clear();				
				}
				elseif ($this->session->data['payment_method']['code'] == 'telr'){
					$telr_data['order_url'] = 'http://google.com';
					// dont add order history or keep its status to zero so :
					$data = $this->payWithTelr($json['order_id']);
					if(isset($data['error']) || !isset($data['action'])){
						if(!isset($json['error'])) $json['error'] = 'Error in Payment';
						unset($json['success']);
					}else{
						if (empty($data['action'])) {
							if(!isset($json['error'])) $json['error'] = 'Error in Payment';
							unset($json['success']);
						}else{
							//$telr_data['order_ref'] = $data['telr_orderref'] ;
							$telr_data['order_url'] = $data['action'];
						}
					}					

					//$json['error'] = 'Failed to pay';
				}

				
				
			}// End of if not json
			
		} // end if isLogged
		else
			if(!isset($json['error'])) $json['error'] = $this->language->get('error_permission');

		if(isset($json['success'])){
			$json = array('success'=>'1', 'data'=>array() ,'telr_data'=> $telr_data , 'message'=> $json['success'] );
		}
		else{
			$json = array('success'=>'0', 'data'=> array() , 'message'=>"Failed .".json_encode($json) );
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		return;		
	}

	public function payWithTelr($order_id) {		

		$order_info = $this->model_checkout_order->getOrder($order_id);
		$testmode=$this->_testmode($this->config->get('payment_telr_test'));
		$amount= $this->currency->format(
			$order_info['total'],
			$order_info['currency_code'],
			$order_info['currency_value'],
			false);
		$cart_desc=trim($this->config->get('payment_telr_purdesc'));
		if (empty($cart_desc)) {
			$cart_desc='Order ID {order} ';
		}
		$order_id = trim($order_info['order_id']);
		$cart_id = $order_id.'~'.(string)time();
		$cart_desc=str_replace('{order}', $order_id, $cart_desc);
		$framed = false;
		
		//NEW
		// convert from Any currency to AED		
		if($order_info['currency_code'] != 'AED'){
			// First way :change by change currency and redirect
			$this->session->data['currency'] = 'AED';
			// First way : for website :
			// Setup message
			/*$telr_currency_message =  $this->language->get('telr_currency_message');			
			// This method change the sub page not all the page : header('Refresh:0');
			// So we replace it with Javascript
			echo '
			<script type="text/javascript">
			alert("'.$telr_currency_message.'");	
			location.reload(false);
			</script>
			';
			exit();*/
			// Second way : change in Telr post Only by convert curreny by currency class in system/library
			$amount = $this->currency->convert(
											$order_info['total'] , // value
											$order_info['currency_code'] , // from : $ Euro Pound
											'AED' // to
										);
			$order_info['currency_code'] = 'AED';			
		}		
		//NEW
		$session = $this->session->getId();
		$post_data = Array(
			'ivp_method'		=> 'create',
			'ivp_authkey'		=> $this->config->get('payment_telr_authkey'),
			'ivp_store'		=> $this->config->get('payment_telr_store'),
			'ivp_lang'		=> $this->config->get('payment_telr_lang'),
			'ivp_cart'		=> $cart_id,
			'ivp_amount'		=> $amount,
			'ivp_currency'		=> trim($order_info['currency_code']),
			'ivp_test'		=> $testmode,
			'ivp_desc'		=> trim($cart_desc),
			'ivp_source'		=> trim('OpenCart '.VERSION),
			'return_auth'		=> 	$this->url->link('api/app/telr/callback%26session='.$session , '', true),
			'return_can'		=>  'http://checkout/',
			'return_decl'		=>  $this->url->link('api/app/telr/callback%26session='.$session , '', true),
			'ivp_update_url'	=>  $this->url->link('api/app/telr/ivpcallback%26session='.$session , array('cart_id' => $order_id), true),
		);
		//encodeURIComponent('&'); => %26		

		//Billing details

		$post_data['bill_fname'] = trim($order_info['payment_firstname']);
		$post_data['bill_sname'] = trim($order_info['payment_lastname']);
		$post_data['bill_addr1'] = trim($order_info['payment_address_1']);
		$post_data['bill_addr2'] = trim($order_info['payment_address_2']);
		$post_data['bill_addr3'] = '';
		$post_data['bill_city'] = trim($order_info['payment_city']);
		$post_data['bill_region'] = trim($order_info['payment_zone']);
		$post_data['bill_zip'] = trim($order_info['payment_postcode']);
		$post_data['bill_ctry'] = trim($order_info['payment_iso_code_2']);
		$post_data['bill_email'] = trim($order_info['email']);
		$post_data['bill_phone1'] = trim($order_info['telephone']);

		if ($this->_isHttps() && $this->config->get('payment_telr_pay_mode') == '2') {
			$post_data['ivp_framed']='2';
			$framed = true;
			if ($this->customer->isLogged()) {
				$post_data['bill_custref']=$this->customer->getId();
			}
		}else{
			$post_data['ivp_framed']='0';
			$framed = false;
		}
		// for mobile always framed = false
		$framed = false ;

		$data = array();
		$returnData = $this->_requestGateway($post_data);

		$jobj='';
		$redirurl='';
		if(isset($returnData['order'])) {
			$jobj = $returnData['order'];
			$this->session->data['telr_orderref']=$jobj['ref'];
			//$data['telr_orderref']=$jobj['ref'];
			$redirurl=$jobj['url'];
		} elseif(isset($returnData['error'])) {
			$jobj = $returnData['error'];
			$data['error'] = $jobj['message'].' : '.$jobj['note'];
			return $data; //exit();
		}

		$data['action'] = $redirurl ;		

		return $data;		

	}

	//************** #Telr Functions# ************************\\
	private function _testmode($telr_testmode) {
		if (strcasecmp($telr_testmode,'live')==0) { return 0; }
		if (strcasecmp($telr_testmode,'no')==0) { return 0; }
		return 1;
	}

	private function _isHttps() {
		$url = $this->url->link('checkout/checkout', '', true);

		if (!isset($url)) { return false; }
		
		if (preg_match('#^https:#i', $url) === 1) {
			return true;
		}
		
		return false;
	}
	private function _requestGateway($post_data)
	{
		$url='https://secure.telr.com/gateway/order.json';
		$fields='';$fields_count = 0;
		foreach ($post_data as $k => $v) {
			$fields.=$k .'='.$v . '&';
			$fields_count++;
		}
		$fields = rtrim($fields, '&');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($fields)));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch,CURLOPT_POST, $fields_count /*count($fields)*/);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
		//curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,10);
		curl_setopt($ch,CURLOPT_TIMEOUT, 30);
		$returnData = json_decode(curl_exec($ch),true);
		//$returnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $returnData;
	}
	//************** #Telr Functions# ************************\\

	public function getOrders()
	{
		// Initilize
		$this->load->language('api/order');
		$json = array();

		// check If Customer Logged
		if($this->checkLogin()){
			$orders = array();$page = 1;

			$this->load->model('account/order');

			$this->load->model('catalog/myproduct');



			$order_total = $this->model_account_order->getTotalOrders();

			$results = $this->model_account_order->getOrders(($page - 1) * 10, 10);

			foreach ($results as $result) {
				$product_total = $this->model_account_order->getTotalOrderProductsByOrderId($result['order_id']);
				$voucher_total = $this->model_account_order->getTotalOrderVouchersByOrderId($result['order_id']);				
				
				// first get order details
				$order = $this->model_account_order->getOrder($result['order_id']);

				 // Setup orderd products
				 $products = $this->model_account_order->getOrderProducts($result['order_id']);
				 foreach ($products as $key => $value) {
				 	$product = $this->model_catalog_myproduct->getProduct($value['product_id']);
				 	$products[$key]['image'] =  $product['products_image'];
				 	$products[$key]['categories_id'] = $product['categories_id'];
				 	$products[$key]['categories_name'] = $product['products_image'];
				 	$products[$key]['attributes'] =  array(); // TODO
				 }

				// Totals				
				$order_sub_total = 0;
				$shipping_cost = 0 ;
				$total_tax = 0 ;
				$coupon_amount = 0 ;
				$order_total_price = 0;

				$totals = $this->model_account_order->getOrderTotals($result['order_id']);

				foreach ($totals as $total) {					
					//'text'  => //$this->currency->format($total['value'], $result['currency_code'], $result['currency_value']),					
					// check sub total
					if($total['code'] == 'sub_total')
						$order_sub_total += $total['value'];
					// check shipping cost
					if($total['code'] == 'shipping')
						$shipping_cost += $total['value'];
					// check taxes
					if($total['code'] == 'tax')
						$total_tax += $total['value'];
					// check coupon
					if($total['code'] == 'coupon')
						$coupon_amount += $total['value'];
					// check total price
					if($total['code'] == 'total')
						$order_total_price += $total['value'];					
				}
				 
				// Setup final Order Details
				$orders[] = array(
				 	"orders_id" => $order['order_id'],
					"customers_id" => $order['customer_id'],
				    "customers_name" => $order['firstname'] . $order['lastname'],

				    //Shipping Address
				    "delivery_name" => $order['shipping_firstname'] . $order['shipping_lastname'] ,
				    "delivery_company" => $order['shipping_company'],
				    "delivery_street_address" => $order['shipping_address_1'],
				    "delivery_suburb" => $order['shipping_address_2'],
				    "delivery_city" => $order['shipping_city'],
				    "delivery_postcode" => $order['shipping_postcode'],
				    "delivery_state" => $order['shipping_zone'], // - shipping_zone_id : Name - shipping_zone_code : Code
				    "delivery_country" => $order['shipping_country'], //  -shipping_country_id / _iso_code_2
				    // (int) IMPORTANT => in android app it get like int not string
				    "delivery_address_format_id" => (int)$order['shipping_address_format'],

				    //Billing Address
				    "billing_name" => $order['payment_firstname'] . $order['payment_lastname'] ,
				    "billing_company" => $order['payment_company'],
				    "billing_street_address" => $order['payment_address_1'],
				    "billing_suburb" => $order['payment_address_2'],
				    "billing_city" => $order['payment_city'],
				    "billing_postcode" => $order['payment_postcode'],
				    "billing_state" => $order['payment_zone'],
				    "billing_country" => $order['payment_country'],
				    // (int) IMPORTANT => to Avoid errors in android app it get int not string
				    "billing_address_format_id" => (int) $order['payment_address_format'],
				    
				    "payment_method" => $order['payment_method'],
				    "shipping_method" => $order['shipping_method'],

				    "last_modified" => $order['date_modified'], // Not used in app
				    "date_purchased" => $order['date_added'],
				    //"orders_date_finished" => $order['date_added'], // TODO but : not used in app
				    "currency" => $order['currency_code'], // Not used in app
				    "currency_value" => $order['currency_value'], // Not used in app				    
				    "customer_comments" => $order['comment'],

				    //NEW
				    "order_sub_total" => $order_sub_total,				    

				    // NOT FOUND				    
				    "total_tax" => $total_tax,
				    "shipping_cost" => $shipping_cost,
				    "coupon_amount" =>  $coupon_amount,
				    "order_price" => $order_total_price, // also can use :$order['total'],
				    "admin_comments" => $order['comment'],
				    "coupons" => array(), //list
				    "data" => $products, // products
				    "orders_status" => $result['status'] ,	

				    // NOT FOUND not used in App
				    /*"customers_company"  => 'XXX',
				    "customers_street_address" => 'XXX',
				    "customers_suburb" => 'XXX',
				    "customers_city" => 'XXX',
				    "customers_postcode" => 'XXX',
				    "customers_state" => 'XXX',
				    "customers_country" => 'XXX',
				    "customers_address_format_id" => 0,
				    
				    "cc_type" => 0,
				    "cc_owner" => 0,
				    "cc_number" => 0,
				    "cc_expires" => 0,*/

				    //"shipping_duration" => '',
				    //"order_information" => '',
				    //"is_seen" => 0,
				    //"coupon_code" => '',				    
				    //"exclude_product_ids" => '',
				    //"product_categories" => '',
				    //"excluded_product_categories" => '',
				    //"free_shipping" => '',
				    //"product_ids" => '',
				    //"orders_status_id" => '2',
				 );
				 
			}			
		} // end if isLogged
		else
			if(!isset($json['error'])) $json['error'] = $this->language->get('error_permission');
		
		if (!$json) {
			$json['success'] = $this->language->get('text_success');
		}

		if(isset($json['success'])){
			$json = array('success'=>'1', 'data'=> $orders , 'message'=> $json['success'] );
		}
		else{
			$json = array('success'=>'0', 'data'=> array() , 'message'=>"Failed .".json_encode($json) );
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

	private function checkSession()
	{
		/*
		unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
		*/
		if(!isset($this->session->data['shipping_address'])){
			return false;
		}elseif (!isset($this->session->data['shipping_address'])) {
			return false;
		}
		return true;

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

	
