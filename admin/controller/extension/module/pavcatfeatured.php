<?php
class ControllerExtensionModulepavcatfeatured extends Controller {
	private $error = array();
	private $data;

	public function index() {

		$this->language->load('extension/module/pavcatfeatured');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/module');

		$this->load->model('catalog/category');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { 
			
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('pavcatfeatured', $this->request->post);
				$this->response->redirect($this->url->link('extension/module/pavcatfeatured', 'user_token=' . $this->session->data['user_token'], true));
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
				$this->response->redirect($this->url->link('extension/module/pavcatfeatured', 'user_token=' . $this->session->data['user_token'].'&module_id='.$this->request->get['module_id'], true));
			}

			$this->session->data['success'] = $this->language->get('text_success');
		}
		// COMMON
		$this->_language();
 		$this->_alert();
 		$this->_breadcrumbs();

 		//getCategories
		if( VERSION == '1.5.4' ){
			$categories = $this->model_catalog_category->getCategories(0);
		}else {
			$categories = $this->model_catalog_category->getCategories( array('limit' => 999999999, 'start'=>0 ) );
		}
		$this->data['categories'] = $categories;


 		// DATA
 		$this->_data();
		
		// RENDER
		$template = 'extension/module/pavcatfeatured';
		$this->data['header'] = $this->load->controller('common/header');
		$this->data['column_left'] = $this->load->controller('common/column_left');
		$this->data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view($template, $this->data));
	}

	public function _data(){
		// Module ID
		if (isset($this->request->get['module_id'])) {
			$module_id = $this->request->get['module_id'];
			$url = '&module_id='.$module_id;
		} else {
			$module_id = '';
			$url = '';
		}
		$this->data['module_id'] = $module_id;

		// action
		$this->data['delete'] = $this->url->link('extension/module/pavcatfeatured/ndelete', 'user_token=' . $this->session->data['user_token'].$url, true);
		$this->data['action'] = $this->url->link('extension/module/pavcatfeatured', 'user_token=' . $this->session->data['user_token'].$url, true);
		$this->data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=module', true);

		$this->data['extensions'] = $this->module("pavcatfeatured");

		// GET DATA SETTING
		$module_info = array();
		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}

		//Categories
		$this->load->model('catalog/category');
		if (isset($this->request->post['categories_featured'])) {
			$categories = $this->request->post['categories_featured'];
		} elseif (!empty($module_info)) {
			$categories = isset($module_info['categories_featured'])?$module_info['categories_featured']:array();
		} else {
			$categories = array();
		}

		$this->load->model('tool/image');
		
		foreach ($categories as &$category) {
			$img = isset($category['image'])?$category['image']:$this->model_tool_image->resize('no_image.png', 30, 30);
			if (is_file(DIR_IMAGE . $img)) {
				$image = $img;
				$thumb = $img;
			} else {
				$image = '';
				$thumb = 'no_image.png';
			}
			$category["thumb"] = $this->model_tool_image->resize($thumb, 30, 30);
		}
		$this->data['categories_featured'] = $categories;

		// Image
		$this->data['placeholder'] = $this->model_tool_image->resize('no_image.png', 30, 30);

		$d = array(
			'layout_style' => 'style-default',
		);

		$module_info = array_merge( $d, $module_info ); 
		// NAME
		if (isset($this->request->post['name'])) {
			$this->data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$this->data['name'] = $module_info['name'];
		} else {
			$this->data['name'] = '';
		}

		//STATUS MODULE Title and Description
        if (isset($this->request->post['titles'])) {
            $this->data['titles'] = $this->request->post['titles'];
        } elseif (!empty($module_info)) {
            $this->data['titles'] = $module_info['titles'];
        } else {
            $this->data['titles'] = 1;
        }
        $this->data['display_desc'] = $module_info['display_desc'];
	// Module Title and Description

	if (isset($this->request->post['module_infor'])) {
		$this->data['module_infor'] = $this->request->post['module_infor'];
	} elseif (!empty($module_info)) {
		$this->data['module_infor'] = $module_info['module_infor'];
	} else {
		$this->data['module_infor'] = array();
	}

		// STATUS
	if (isset($this->request->post['status'])) {
		$this->data['status'] = $this->request->post['status'];
	} elseif (!empty($module_info)) {
		$this->data['status'] = $module_info['status'];
	} else {
		$this->data['status'] = 1;
	}

	//LAYOUT-STYLE
	if (isset($this->request->post['layout_style'])) {
		$this->data['layout_style'] = $this->request->post['layout_style'];
	} elseif (!empty($module_info)) {
		$this->data['layout_style'] = $module_info['layout_style'];
	} else {
		$this->data['layout_style'] = 'style-default';
	}

        // LOOP
        if (isset($this->request->post['loop'])) {
            $this->data['loop'] = $this->request->post['loop'];
        } elseif (!empty($module_info)) {
            $this->data['loop'] = $module_info['loop'];
        } else {
            $this->data['loop'] = 0;
        }

        // AUTOPLAY

        if (isset($this->request->post['auto_play'])) {
            $this->data['auto_play'] = $this->request->post['auto_play'];
        } elseif (!empty($module_info)) {
            $this->data['auto_play'] = $module_info['auto_play'];
        } else {
            $this->data['auto_play'] = 1;
        }

        // Interval

        if (isset($this->request->post['interval'])) {
            $this->data['interval'] = $this->request->post['interval'];
        } elseif (!empty($module_info)) {
            $this->data['interval'] = $module_info['interval'];
        } else {
            $this->data['interval'] = '8000';
        }

        // PerView

        if (isset($this->request->post['perview'])) {
            $this->data['perview'] = $this->request->post['perview'];
        } elseif (!empty($module_info)) {
            $this->data['perview'] = $module_info['perview'];
        } else {
            $this->data['perview'] = 4;
        }

        //Rows

        if (isset($this->request->post['rows'])) {
            $this->data['rows'] = $this->request->post['rows'];
        } elseif (!empty($module_info)) {
            $this->data['rows'] = $module_info['rows'];
        } else {
            $this->data['rows'] = 1;
        }

        // showimg
		if (isset($this->request->post['showimg'])) {
			$this->data['showimg'] = $this->request->post['showimg'];
		} elseif (!empty($module_info)) {
			$this->data['showimg'] = isset($module_info['showimg'])?$module_info['showimg']:1;
		} else {
			$this->data['showimg'] = 0;
		}

		// Image sizes

		if (isset($this->request->post['img_width'])) {
			$this->data['img_width'] = $this->request->post['img_width'];
		} elseif (!empty($module_info)) {
			$this->data['img_width'] = $module_info['img_width'];
		} else {
			$this->data['img_width'] = '300';
		}

		if (isset($this->request->post['img_height'])) {
			$this->data['img_height'] = $this->request->post['img_height'];
		} elseif (!empty($module_info)) {
			$this->data['img_height'] = $module_info['img_height'];
		} else {
			$this->data['img_height'] = '300';
		}


		// CLASS
		if (isset($this->request->post['prefix'])) {
			$this->data['prefix'] = $this->request->post['prefix'];
		} elseif (!empty($module_info)) {
			$this->data['prefix'] = $module_info['prefix'];
		} else {
			$this->data['prefix'] = 'prefix';
		}

		$this->data['styles']		 = array(
			'style-default' => $this->language->get('text_style_default'),
			'style1' 	 		=> $this->language->get('text_style1'),
			'style2' 	 		=> $this->language->get('text_style2'),
			'style3' 			=> $this->language->get('text_style3'),
		);

	}

	public function _breadcrumbs(){
		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'].'&type=module', true),
      		'separator' => ' :: '
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/module/pavcatfeatured', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => ' :: '
   		);
	}

	public function _language(){
		$this->load->model('localisation/language');
		$this->data['languages'] = $this->model_localisation_language->getLanguages();
	}

	public function _alert(){
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
	}

	public function redirect( $url ){
		return $this->response->redirect( $url );
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/pavcatfeatured')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
	
	//delete module
	public function ndelete(){
		$this->load->model('setting/module');
		$this->load->language('extension/module/pavcatfeatured');
		if (isset($this->request->get['module_id'])) {
			$this->model_setting_module->deleteModule($this->request->get['module_id']);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/module/pavcatfeatured', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	public function module($extension){
		$module_data = array();
		$this->load->model('setting/extension');
		$this->load->model('setting/module');
		$extensions = $this->model_setting_extension->getInstalled('module');
		$modules = $this->model_setting_module->getModulesByCode($extension);
		foreach ($modules as $module) {
			$module_data[] = array(
				'module_id' => $module['module_id'],
				'name'      => $module['name'],
				'edit'      => $this->url->link('extension/module/pavcatfeatured', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $module['module_id'], true),
			);
		}
		$ex[] = array(
			'name'      => $this->language->get("create_module"),
			'module'    => $module_data,
			'edit'      => $this->url->link('extension/module/pavcatfeatured', 'user_token=' . $this->session->data['user_token'], true)
		);
		return $ex;
	}
}
?>
