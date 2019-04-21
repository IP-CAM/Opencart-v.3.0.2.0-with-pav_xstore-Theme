<?php
class ControllerApiAppSettings extends Controller {

	public function index()
	{
		$settings = array();

		//$settings = ( $this->doHttpNormal('http://localhost/karaam/api/siteSetting',array()) )->data;
		//TODO from Admin Page
		$settings []= array(
			"setting_id"							=> 1,
			"facebook_app_id"						=> "2086849514971819",
			"facebook_secret_id"					=> "c260070cd303fe17d218320260e5cd7f",
			"facebook_login"						=> 1,
			"google_login"							=> 1,
			"contact_us_email"						=> "info@karaam.com",
			"address"								=> "Al Bustan Al Jameel General Trading Co.LLC , Second floor 203 , Al Khaleej Road Dubai , U A E",
			"city"									=> "Dubai",
			"state"									=> "NY",
			"zip"									=> 10003,
			"country"								=> "USA",
			"latitude"								=> "25.286139",
			"longitude"								=> "55.320545",
			"phone_no"								=> "+971507880272",
			"fcm_android"							=> "AAAAYySomvM:APA91bFObyB570jvA0qW97RT3s1gJqmS9_76GHF7IJun1c-_lPJzecs2xKLGLKIEj2xgNhfifEGxcyZC6ShdzThwxKnHu2vZqC34-RqaDrsPBFH6gMVNAfAZoBcTLEaH1gQ_B753yPWv",
			"fcm_ios"								=> "",
			"fcm_desktop"							=> "",
			"app_logo"								=> "",
			"fcm_android_sender_id"					=> "425816791795",
			"fcm_ios_sender_id"						=> "",
			"app_name"								=> "Karaam Shop",
			"currency_symbol"						=> "$",
			"new_product_duration"					=> 20,
			"notification_title"					=> "Karaam Shop",
			"notification_text"						=> "A bundle of products waiting for you!",
			"lazzy_loading_effect"					=> "bubbles",
			"footer_button"							=> 1,
			"cart_button"							=> 1,
			"featured_category"						=> 0,
			"notification_duration"					=> "day",
			"home_style"							=> 1,
			"wish_list_page"						=> 1,
			"edit_profile_page"						=> 1,
			"shipping_address_page"					=> 1,
			"my_orders_page"						=> 1,
			"contact_us_page"						=> 1,
			"about_us_page"							=> 1,
			"news_page"								=> 1,
			"intro_page"							=> 1,
			"setting_page"							=> 1,
			"share_app"								=> 1,
			"rate_app"								=> 1,
			"site_url"								=> "karaam.com",
			"admob"									=> 0,
			"admob_id"								=> "",
			"ad_unit_id_banner"						=> "",
			"ad_unit_id_interstitial"				=> "",
			"category_style"						=> 1,
			"package_name"							=> "com.karaam.shop",
			"google_analytic_id"					=> "test",
			"default_notification"					=> "onesignal",
			"onesignal_app_id"						=> 'd1df49a5-c483-42f3-a8d2-2a8aa1e90e0c',
			// old one"df1efa71-aa32-4746-ba84-066c515e93c1",
			// my new one : 18cb5ac9-8c7f-40e5-be69-421c2bae3a71
			"onesignal_sender_id"					=> "425816791795",
			"ios_admob"								=> 0,
			"ios_admob_id"							=> "",
			"ios_ad_unit_id_banner"					=> "",
			"ios_ad_unit_id_interstitial"			=> ""
		);

		if($settings){
			$json = array('success'=>'1', 'data'=>$settings, 'message'=>"settings returned successfully.");	
		}
		else{
			$json = array('success'=>'0', 'data'=>array(), 'message'=>"No settings .");
		}
		//print_r((Array)$settings->data);die();
		
		
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

	
