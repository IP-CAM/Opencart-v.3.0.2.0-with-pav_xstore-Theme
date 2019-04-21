<?php

class ControllerExtensionModulePavreassurance extends Controller {

	private $error = array();

	private $data;

	public function index() {
		

		$this->language->load('extension/module/pavreassurance');
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('design/layout');
		$this->load->model('setting/module');
		$this->load->model('setting/extension');
		
		$this->document->addScript('view/javascript/summernote/summernote.js');
  		$this->document->addStyle('view/javascript/summernote/summernote.css');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$action = isset($this->request->post["action"]) ? $this->request->post["action"] : "";
			unset($this->request->post['action']);

			$search = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
			$replace = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');
	
		 	$data = array();
			foreach ($this->request->post['pavreassurance_module'] as $key => $value) {

		 	 	$data = $value;	 
		 	  	foreach( $data['pavreassurances'] as $key => $val  ){ 	
		 	  		$tmp = array();

		 	  		foreach( $val['detail'] as $dkey => $dval ){
		 	  			$dval = preg_replace("#\s+\s+\+#", "", $dval);
		 	  			$val['detail'][$dkey] =  str_replace( "\r\n", "", $dval );
		 	  		}
		 	  		foreach( $val['caption'] as $dkey => $dval ){
		 	  			$search = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
						$replace = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');

		 	  			$val['caption'][$dkey] =  str_replace( "\r\n", "", $dval );
		 	  		}
		 	  		$tmp = $val; 
		 	  		$data['pavreassurances'][$key] = $tmp;
		 	  	}
		 	 	break;
		 	}

			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('pavreassurance', $data );
			} else {
				$this->model_setting_module->editModule( $this->request->get['module_id'], $data );
			}


			$this->session->data['success'] = $this->language->get('text_success');
			if( isset($this->request->get['module_id']) ) {
				$this->response->redirect($this->url->link('extension/module/pavreassurance', 'module_id='.$this->request->get['module_id'].'&user_token=' . $this->session->data['user_token'], 'SSL'));
			}else{
				$this->response->redirect($this->url->link('extension/module/pavreassurance', 'user_token=' . $this->session->data['user_token'], 'SSL'));
			}
		}

		$this->data['user_token'] = $this->session->data['user_token'];

		$languages = $this->model_localisation_language->getLanguages();
		$this->data['tab_module_info'] = $this->language->get( 'tab_module_info' );
		$this->data['languages'] = $languages;

		if (isset($this->request->post['pavreassurance_status'])) {
			$this->data['pavreassurance_status'] = $this->request->post['pavreassurance_status'];
		} else {
			$this->data['pavreassurance_status'] = $this->config->get('pavreassurance_status');
		}

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}



		$this->getStore();

	 
		$this->data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=module', 'SSL');

		$this->_breadcrumbs();


		$this->data['modules'] = array();
		if (isset($this->request->post['pavreassurance_module'])) {
			$this->data['modules'] = $this->request->post['pavreassurance_module'];
		}

		if ($this->config->get('pavreassurance_module')) {
			$this->data['modules'] = $this->config->get('pavreassurance_module');
		}


		$d = array(
			'prefix'    	  => '',
			'layout_id'       => '1',
			'position'        => 'content_top',
			'status'          => '1',
			'sort_order'      => '2',
			'module_layout'   => '1',
			'pavreassurances' => array(),
			'name'	=> ''
		);

		  
		if( isset($d['pavreassurances']) && !empty($d['pavreassurances']) ){
			foreach( $d['pavreassurances'] as &$reassurance ){
				$reassurance['select_icon']             = isset($reassurance['select_icon']) ? $reassurance['select_icon'] : "";
				$reassurance['reassurance_prefixclass'] = isset($reassurance['reassurance_prefixclass']) ? $reassurance['reassurance_prefixclass'] : "";
				$reassurance['title']                   = isset($reassurance['title']) ? $reassurance['title'] : "";
				$reassurance['caption']                 = isset($reassurance['caption']) ? $reassurance['caption'] : "";
				$reassurance['detail']                  = isset($reassurance['detail']) ? $reassurance['detail'] : "";
			}
		}


		$default = array(
			'prefix'    	  => '',
			'layout_id'       => '1',
			'position'        => 'content_top',
			'status'          => '1',
			'sort_order'      => '2',
			'module_layout'   => '1',
			'pavreassurances' => array(),
			'name' => '',
		);
			
		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
			$module_info = array_merge( $default, (array)$module_info );
			$this->data['selectedid'] = $this->request->get['module_id'];

			
			$this->data['subheading'] = $this->language->get('text_edit_module') . $module_info['name'];
			$this->data['action']     = $this->url->link('extension/module/pavreassurance', 'module_id='.$this->request->get['module_id'].'&user_token=' . $this->session->data['user_token'], 'SSL');
		}else {
			$module_info = $default;
			$this->data['selectedid'] = 0;
			$this->data['subheading'] = $this->language->get('text_create_new_module');
			$this->data['action'] = $this->url->link('extension/module/pavreassurance', 'user_token=' . $this->session->data['user_token'], 'SSL');
		}
 	
	 
		$modules = array( 0=> $module_info );
		
		
		$moduletabs = $this->model_setting_module->getModulesByCode( 'pavreassurance' );
	 	$this->data['link']      = $this->url->link('extension/module/pavreassurance', 'user_token=' . $this->session->data['user_token'] . '', 'SSL');
 		$this->data['modules']   = $modules;
 		$this->data['module']    = $module_info;

 		$this->data['moduletabs'] = $moduletabs;

  
 		$this->data['olang']  = $this->language; 

 	
		$this->data['header']       = $this->load->controller('common/header');
		$this->data['column_left']  = $this->load->controller('common/column_left');
		$this->data['footer']       = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/pavreassurance', $this->data));
	}


	public function _breadcrumbs(){
		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=module', 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/module/pavreassurance', 'user_token' . $this->session->data['user_token'], 'SSL'),
			'separator' => ' :: '
		);

	}

	public function getStore(){
		//Get data Store
		$this->load->model('setting/store');
		$action = array();
		$action[] = array(
			'text' => $this->language->get('text_edit'),
			'href' => $this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'], 'SSL'),
		);
		$store_default = array(
			'store_id' => 0,
			'name'     => $this->config->get('config_name') . $this->language->get('text_default'),
			'url'      => HTTP_CATALOG,
		);
		$stores = $this->model_setting_store->getStores();
		array_unshift($stores, $store_default);
		foreach ($stores as &$store) {
			$url = '';
			if ($store['store_id'] > 0 ) {
				$url = '&store_id='.$store['store_id'];
			}
			$store['option'] = $this->url->link('extension/module/pavreassurance', $url.'&user_token=' . $this->session->data['user_token'], 'SSL');
		}

		$this->data['stores'] = $stores;
		$store_id = isset($this->request->get['store_id'])?$this->request->get['store_id']:0;
		$this->data['store_id'] = $store_id;
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/pavreassurance')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

}
?>