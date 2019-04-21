<?php

class ControllerExtensionModulePavreassurance extends Controller {

	private $data = array();

	public function index($setting) {

		$this->data['config_theme'] = $this->config->get('theme_default_directory');

		$language = $this->config->get('config_language_id');
		$prefix_class = isset($setting['prefix']) ? $setting['prefix'] : '';

		$this->data['prefix_class'] = $prefix_class;
		$this->data['language'] = $language;



		$reassurances = isset($setting['pavreassurances'])?$setting['pavreassurances']:array();

		$result = array();
		if (!empty($reassurances)) {
			foreach ($reassurances as $key=>$value) {
				$result[$key]['select_icon']             = isset($value['select_icon']) ? $value['select_icon'] : '';
				$result[$key]['reassurance_prefixclass'] = isset($value['reassurance_prefixclass'])?$value['reassurance_prefixclass']:'';
				$result[$key]['title']                   = isset($value['title'][$language])?$value['title'][$language]:'';
				$result[$key]['caption']                 = isset($value['caption'][$language])?htmlspecialchars_decode($value['caption'][$language]):'';
				$result[$key]['detail']                  = isset($value['detail'][$language])?htmlspecialchars_decode($value['detail'][$language]):'';
			}
		}
		
		$this->data['pavreassurances'] = $result;
		
		return $this->load->view('extension/module/pavreassurance', $this->data);
		
	}
}