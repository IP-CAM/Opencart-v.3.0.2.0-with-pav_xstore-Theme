<?php
/******************************************************
 * @package Pavo User Popup Module for Opencart 3.x
 * @version 1.0
 * @author http://www.pavothemes.com
 * @copyright	Copyright (C) Feb 2017 PavoThemes.com <@emai:pavothemes@gmail.com>.All rights reserved.
 * @license		GNU General Public License version 2
*******************************************************/

class ControllerExtensionModulePavuserpopup extends Controller {

	private $error = array();

	/**
	 * install actions
	 * create new permission and tables
	 */
	public function install() {
		$this->load->model('setting/setting');
		$info = $this->model_setting_setting->getSetting( 'pavuserpopup' );

		if( empty($info) || !isset($info['pavuserpopup']) ){
			$this->load->model('localisation/language');

			$languages = $this->model_localisation_language->getLanguages();
			$data = array( 'pavuserpopup' => array() );

			$sample = '<h3>NEW MEMBER HERE?</h3>
					<p>Registration is free and easy!</p><ul><li>Faster checkout
					</li><li>Save multiple shipping addresses
					</li><li>View and track orders and more</li></ul>
					<div class="text-center"><a href="#" class="btn btn-primary">Register</a></div>';	
			foreach( $languages as $key => $value ){
				$data['pavuserpopup']['description'][$value['language_id']] = $sample;
			}

			$this->model_setting_setting->editSetting( 'pavuserpopup', $data, 0 );
		}
	}

	/**
	 * uninstall actions
	 * remove user permission
	 */
	public function uninstall() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting( 'pavuserpopup', 0 );
	}

	/**
	 * @action index to render content of setting form
	 */
	public function index() {
		$this->load->language('extension/module/pavuserpopup');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/module');
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$data = array();
			$data['pavuserpopup'] = $this->request->post; 		

			$this->model_setting_setting->editSetting( 'pavuserpopup', $data, 0 );
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/pavuserpopup', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/pavuserpopup', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/pavuserpopup', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/pavuserpopup', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}


		if (isset($this->request->post['description'])) {
			$data['description'] = $this->request->post['description'];
		} elseif (!empty($module_info)) {
			$data['description'] = $module_info['description'];
		} else {
			$data['description'] = array();
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
		}
		$info = $this->model_setting_setting->getSetting( 'pavuserpopup' );

		$data['description'] = array();  

		if( $info ){
			$data['description'] = $info['pavuserpopup']['description'];
		}

		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/pavuserpopup', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/pavuserpopup')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	
}