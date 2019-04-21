<?php  
class ControllerExtensionModulePavcatfeatured extends Controller {
	
	private $mdata = array();

	public function index($setting) {
		$this->load->language('extension/module/pavcatfeatured');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
		$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/opencart.css');
		
		$this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.min.js');

		// Get Data Setting
		$this->mdata['prefix'] = isset($setting['prefix']) ? $setting['prefix'] : '';

		//Title and Description
		$this->mdata['box_heading'] = isset($setting['module_infor'][$this->config->get('config_language_id')]['title']) ? $setting['module_infor'][$this->config->get('config_language_id')]['title'] : '';
		$this->mdata['description'] = isset($setting['module_infor'][$this->config->get('config_language_id')]['description']) ? $setting['module_infor'][$this->config->get('config_language_id')]['description'] : '';
        $this->mdata['loop']		= isset($setting['loop']) && $setting['loop'] == 1 ? "true" : "false";
        $this->mdata['interval'] 	= isset($setting['interval']) ? (int)$setting['interval'] : 0;
        $this->mdata['auto_play_mode'] = isset($setting['auto_play']) && $setting['auto_play'] == 1 ? "true" : "false";
        $this->mdata['perview']   	= isset($setting['perview']) ? (int)$setting['perview'] : 0;
		$this->mdata['showimg'] 	= isset($setting['showimg'])?$setting['showimg']:0;
        $this->mdata['rows']   		= isset($setting['rows']) ? (int)$setting['rows'] : 1;
        $this->mdata['titles']   	= isset($setting['titles']) ? (int)$setting['titles'] :0;
        $this->mdata['layout_style']   	= isset($setting['layout_style']['styles']) ? $setting['layout_style']['styles'] :'';
        $this->mdata['display_desc']   	= isset($setting['display_desc']) ? $setting['display_desc'] : '';

		$categories = isset($setting['categories_featured']) ? $setting['categories_featured'] : array();

		$data = array();
		foreach ($categories as $category) {

			$check = $this->checkExistCategory($category['id']);
			if(!empty($check)) {

				$filter_data = $this->model_catalog_category->getCategory($category['id']);

				$datap = array(
					'filter_category_id'  => $filter_data['category_id'], 
					'filter_sub_category' => true
				);
				$product_total = $this->model_catalog_product->getTotalProducts($datap);
				$img = isset($category['image'])? $category['image']: '';
				$description = html_entity_decode( htmlspecialchars_decode( !empty($filter_data['description']) ? $filter_data['description'] : '' ), ENT_QUOTES, 'UTF-8' );
				$data[] = array(
					'items' => $product_total." ".$this->language->get('text_items'),
					'image' => $img,
					'thumb' => $this->model_tool_image->resize($img, $setting['img_width'], $setting['img_height']),
					'icon'  => $category['icon'],
					'category_id' => $filter_data['category_id'], 
					'description' => substr($description, 0, 150) . '...',
					'name'  => $filter_data['name'], 
					'href'  => $this->url->link('product/category', 'path=' . $filter_data['category_id'], true),
					'class' => isset($category['class'])? $category['class']: '' 
				);
			}
		}

		$this->mdata['categories'] = $data;

		// Template 		
		$template = 'extension/module/pavcatfeatured';
		return $this->load->view($template, $this->mdata);
	}
	
	public function checkExistCategory($category_id){
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$category_id . "'" );
		return $query->row;
	}

	public function _d($var){
		
	}
}
?>