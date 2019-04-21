<?php
class ControllerExtensionModulePavlistingtabs extends Controller {
    private $data;
    public function index($setting) {
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('extension/module/pavlistingtabs');
        $this->load->model('tool/image');
        $this->load->language('extension/module/pavlistingtabs');
        $this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
        $this->document->addStyle('catalog/view/javascript/jquery/swiper/css/opencart.css');
        
        $this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.min.js');

        $a = array('interval'=> 8000,'auto_play'=>0,'loop' => 1 );
        $setting = array_merge( $a, $setting );
        $this->data['module_class'] = isset($setting['module_class'])?$setting['module_class']:'';
        $this->data['description']  = isset($setting['description'])?$setting['description']:'';
        $this->data['width']        = isset($setting['width']) ? (int)$setting['width'] : 200;
        $this->data['height']       = isset($setting['height']) ? (int)$setting['height'] : 200;
        $this->data['auto_play_mode'] = isset($setting['auto_play']) && $setting['auto_play'] == 1 ? "true" : "false";
        $this->data['interval']     = isset($setting['interval']) ? (int)$setting['interval']: 5000;
        $this->data['cols']         = isset($setting['cols']) ? (int)$setting['cols'] : 1;
        $this->data['itemsperpage'] = isset($setting['itemsperpage']) ? (int)$setting['itemsperpage'] : 2;
        $this->data['loop']			= isset($setting['loop']) && $setting['loop'] == 1 ?"true" : "false";
        $this->data['tooltip']      = isset($setting['tooltip'])?(int)$setting['tooltip']:0;
        $this->data['tooltip_placement'] = isset($setting['tooltip_placement'])?$setting['tooltip_placement']:'top';
        $this->data['tooltip_show'] = isset($setting['tooltip_show'])?(int)$setting['tooltip_show']:100;
        $this->data['tooltip_hide'] = isset($setting['tooltip_hide'])?(int)$setting['tooltip_hide']:100;

        $this->data['tooltip_width']  = isset($setting['tooltip_width'])?(int)$setting['tooltip_width']:200;
        $this->data['tooltip_height'] = isset($setting['tooltip_height'])?(int)$setting['tooltip_height']:200;

        $this->data['show_button']    = isset($setting['btn_view_more'])?$setting['btn_view_more']:0;
        if ( ! empty (  $setting['tabs'] ) ) {
        $setting['tabs'] = array_flip( $setting['tabs'] );
        }
        $this->data['tabs'] = array();

        $filter_data = array(
            'sort'  => 'p.date_added',
            'order' => 'DESC',
            'start' => 0,
            'limit' =>   isset($setting['limit']) ? $setting['limit'] : 10,
            'product' => isset($setting['product'])?$setting['product']:array(),
        );
        $categories = array();
        if ( ! empty (  $setting['tabss'] ) ) {

            foreach ( $setting['tabss'] as $cat_id ) {
                $getcategory = $this->model_catalog_category->getCategory($cat_id);
                $a = $this->model_extension_module_pavlistingtabs->getTopRatingProducts( $filter_data['limit'], $cat_id );
                $b = $this->model_extension_module_pavlistingtabs->getLatestProducts( $filter_data['limit'], $cat_id );
                $c = $this->model_extension_module_pavlistingtabs->getBestSellerProducts( $filter_data['limit'], $cat_id );
                $d = $this->model_extension_module_pavlistingtabs->getProductSpecials( $filter_data, $cat_id );
                $e = $this->model_extension_module_pavlistingtabs->getMostviewedProducts( $filter_data['limit'], $cat_id );

                $products = array();
                if( isset($setting['tabs']['toprating']) && $a ){
                    $this->data['heading_title'] = $this->language->get('text_toprating');
                    $products = $this->getProducts( $a,$setting);
                }
                if( isset($setting['tabs']['latest']) && $b ){
                    $this->data['heading_title'] = $this->language->get('text_latest');
                    $products = $this->getProducts($b,$setting);
                }
                if( isset($setting['tabs']['bestseller']) && $c ){
                    $this->data['heading_title'] = $this->language->get('text_bestseller');
                    $products = $this->getProducts($c,$setting);
                }
                if( isset($setting['tabs']['special']) && $d ){
                    $this->data['heading_title'] = $this->language->get('text_special');
                    $products = $this->getProducts($d,$setting);
                }
                if( isset($setting['tabs']['mostviewed']) && $e ){
                    $this->data['heading_title'] = $this->language->get('text_mostviewed');
                    $products = $this->getProducts($e,$setting);
                }
                $results = $this->model_catalog_category->getCategories();
                $first   = reset($results); 
                $categories[$cat_id] = array(
                    'category_id'       => isset($getcategory['category_id']) ? $getcategory['category_id'] : $first['category_id'],
                    'category_name'     => isset($getcategory['name']) ? $getcategory['name'] : $first['name'],
                    'link'              => $this->url->link('product/category','path=' .( isset($getcategory['category_id']) ? $getcategory['category_id'] : $first['category_id']) ),
                    'products'           => $products
                );
            }
        }

        $tabs = array(
            'latest' 	 => array(),
            'bestseller' => array(),
            'special'    => array(),
            'mostviewed' => array(),
            'toprating'  => array()
        );

        if( isset($setting['description'][$this->config->get('config_language_id')]) ) {
            $this->data['message'] = html_entity_decode($setting['description'][$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8');
        } else {
            $this->data['message'] = '';
        }

        if( isset($setting['title'][$this->config->get('config_language_id')]) ) {
            $this->data['title'] = html_entity_decode($setting['title'][$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8');
        }else {
            $this->data['title'] = '';
        }
        $this->data['categories'] = $categories;

        return $this->load->view('extension/module/pavlistingtabs', $this->data);
    }


    private function getProducts( $results, $setting ){
        $products = array();
        $tooltip_width = isset($setting['tooltip_width'])?(int)$setting['tooltip_width']:200;
        $tooltip_height = isset($setting['tooltip_height'])?(int)$setting['tooltip_height']:200;
        foreach ($results as $result) {
            if ($result['image']) {
                $image = $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']);
                // Image Attribute for product
                $product_images = $this->model_catalog_product->getProductImages($result['product_id']);
                if(isset($product_images) && !empty($product_images)) {
                    $thumb2 = $this->model_tool_image->resize($product_images[0]['image'], $setting['width'], $setting['height']);
                }
            } else {
                $image = false;
            }

            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price = false;
            }

            if ((float)$result['special']) {
                $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                $discount = floor((($result['price']-$result['special'])/$result['price'])*100);
            } else {
                $special = false;
            }

            if ($this->config->get('config_tax')) {
                $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
            } else {
                $tax = false;
            }
            if ($this->config->get('config_review_status')) {
                $rating = $result['rating'];
            } else {
                $rating = false;
            }

            $products[] = array(
                'product_id' => $result['product_id'],
                'thumb'   	 => $image,
                'name'    	 => $result['name'],
                'date_added'  => $result['date_added'],
                'discount'   => isset($discount)?'-'.$discount.'%':'',
                'price'   	 => $price,
                'special' 	 => $special,
                'rating'     => $rating,
                'description'=> (html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')),
                'reviews'    => sprintf($this->language->get('text_reviews'), (int)$result['reviews']),
                'href'    	 => $this->url->link('product/product', 'product_id=' . $result['product_id']),
                'thumb2'     => isset($thumb2)?$thumb2:'',
            );
        }
        return $products;
    }
}

?>