<?php
/**
 * @package Pavothemer for Opencart 3.x
 * @version 1.0
 * @author http://www.pavothemes.com
 * @copyright	Copyright (C) Feb 2017 PavoThemes.com <@emai:pavothemes@gmail.com>.All rights reserved.
 * @license		GNU General Public License version 2
 */

class PA_Widget_Instagram extends PA_Widgets {

	public function fields(){
		return array(
			'mask'		=> array(
				'icon'	=> 'fa fa-instagram',
				'label'	=> $this->language->get( 'entry_instagram' )
			),
			'tabs'	=> array(
				'general'		=> array(
					'label'		=> $this->language->get( 'entry_general_text' ),
					'fields'	=> array(
						array(
							'type'	=> 'hidden',
							'name'	=> 'uniqid_id',
							'label'	=> $this->language->get( 'entry_row_id_text' ),
							'desc'	=> $this->language->get( 'entry_column_desc_text' )
						),
						array(
							'type'	=> 'text',
							'name'	=> 'extra_class',
							'label'	=> $this->language->get( 'entry_extra_class_text' ),
							'desc'	=> $this->language->get( 'entry_extra_class_desc_text' ),
							'default'	=> ''
						),
						array(
							'type'		=> 'text',
							'name'		=> 'title',
							'label'		=> $this->language->get( 'entry_title' ),
							'desc'		=> $this->language->get( 'entry_title_desc_text' ),
							'default'	=> '',
							'language'	=> true
						),
						array(
							'type'		=> 'editor',
							'name'		=> 'subtitle',
							'label'		=> $this->language->get( 'entry_subtitle_text' ),
							'default'	=> '',
							'language'	=> true
						),
						array(
							'type'	=> 'text',
							'name'	=> 'user_id',
							'label'	=> $this->language->get( 'entry_userid' ),
							'desc'	=> $this->language->get( 'entry_userid_desc' ),
							'default'	=> ''
						),
						array(
							'type'	=> 'text',
							'name'	=> 'client_id',
							'label'	=> $this->language->get( 'entry_clientid' ),
							'desc'	=> $this->language->get( 'entry_clientid_desc' ),
							'default'	=> ''
						),
						array(
							'type'	=> 'text',
							'name'	=> 'access_token',
							'label'	=> $this->language->get( 'entry_accesstoken' ),
							'desc'	=> $this->language->get( 'entry_accesstoken_desc' ),
							'default'	=> ''
						),
						array(
							'type'	=> 'select',
							'name'	=> 'image_size',
							'label'	=> $this->language->get( 'entry_image_size_wh_text' ),
							'default'	=> 'standard_resolution',
							'options'	=> array(
								array(
									'value'	=> 'thumbnail',
									'label'	=> '150 x 150'
								),
								array(
									'value'	=> 'low_resolution',
									'label'	=> '320 x 320'
								),
								array(
									'value'	=> 'standard_resolution',
									'label'	=> '612 x 612'
								),
							)
						),
						array(
							'type'	=> 'select',
							'name'	=> 'sortby',
							'label'	=> $this->language->get( 'entry_sortby' ),
							'default'	=> 'least-recent',
							'options'	=> array(
								array(
									'value'	=> 'most-recent',
									'label'	=> 'Newest to oldest'
								),
								array(
									'value'	=> 'least-recent',
									'label'	=> 'Oldest to newest'
								),
								array(
									'value'	=> 'most-liked',
									'label'	=> 'Most Liked'
								),
								array(
									'value'	=> 'least-liked',
									'label'	=> 'Least Liked'
								),
								array(
									'value'	=> 'most-commented',
									'label'	=> 'Most Commented'
								),
								array(
									'value'	=> 'least-commented',
									'label'	=> 'Least Commented'
								),
								array(
									'value'	=> 'random',
									'label'	=> 'Random'
								)
							)
						),
						array(
							'type'	  => 'number',
							'name'    => 'text_length',
							'label'	  => $this->language->get( 'entry_text_length' ),
							'desc'	  => $this->language->get( 'entry_text_length_desc' ),
							'default' => 50
						),
						array(
							'type'	  => 'number',
							'name'    => 'item',
							'label'	  => $this->language->get( 'entry_item_text' ),
							'desc'    => $this->language->get( 'entry_item_desc_text' ),
							'default' => 4
						),
						array(
							'type'		=> 'number',
							'name'		=> 'rows',
							'label'		=> $this->language->get( 'entry_rows_text' ),
							'default'	=> 1,
							'min'	=> 1,
							'max' => 2
						),
						array(
							'type'		=> 'number',
							'name'		=> 'limit',
							'label'		=> $this->language->get( 'entry_limit_text' ),
							'default'	=> 7
						),
						array(
							'type'		=> 'select',
							'name'		=> 'loop',
							'label'		=> $this->language->get( 'entry_loop' ),
							'desc'		=> $this->language->get( 'entry_loop_desc' ),
							'default' 	=> 'false',
							'options'	=> array(
								array(
									'value'	=> 'true',
									'label'	=> 'Enabled'
								),
								array(
									'value'	=> 'false',
									'label'	=> 'Disabled'
								)
							)
						),
						array(
							'type'		=> 'select',
							'name'		=> 'auto_play',
							'label'		=> $this->language->get( 'entry_auto_play' ),
							'default' 	=> 'false',
							'options'	=> array(
								array(
									'value'	=> 'true',
									'label'	=> 'Enabled'
								),
								array(
									'value'	=> 'false',
									'label'	=> 'Disabled'
								)
							)
						),
						array(
							'type'	  => 'number',
							'name'	  => 'auto_play_time',
							'label'	  => $this->language->get( 'entry_auto_play_time' ),
							'default' => 5000
						),
					)
				),
				'style'				=> array(
					'label'			=> $this->language->get( 'entry_styles_text' ),
					'fields'		=> array(
						array(
							'type'	=> 'layout-onion',
							'name'	=> 'layout_onion',
							'label'	=> 'entry_box_text'
						)
					)
				)
			)
		);
	}

	public function render( $settings = array(), $content = '' ) {
		$this->document->addScript('catalog/view/javascript/instafeed.js');
		$settings['subtitle'] = ! empty( $settings ) && ! empty( $settings['subtitle'] ) ? html_entity_decode( htmlspecialchars_decode( $settings['subtitle'] ), ENT_QUOTES, 'UTF-8' ) : '';
		$carouselOptions = array(
			'loop' => isset( $settings['loop'] ) && $settings['loop'] === 'true',
			'responsiveClass' => true,
			'items' => !empty($settings['item']) ? (int)$settings['item'] : 2,
			'rows' => !empty($settings['rows']) ? (int)$settings['rows'] : 1,
			'nav' => !empty($settings['nav']) && $settings['nav'] === 'true',
			'dots' => !empty($settings['pagination']) && $settings['pagination'] === 'true',
			'autoplay' => !empty($settings['auto_play']) && $settings['auto_play'] == 'true',
			'autoplayTimeout' => !empty($settings['auto_play_time']) ? $settings['auto_play_time'] : 2000,
			'responsive' => array(
				0 => array(
					'items' => !empty($settings['mobile']) ? $settings['mobile'] : 1,
					'nav' => true
				),
				481 => array(
					'items' => !empty($settings['table']) ? $settings['table'] : 1,
					'nav' => true
				),
				769 => array(
					'items' => !empty($settings['item']) ? $settings['item'] : 1,
					'nav' => !empty($settings['nav']) && $settings['nav'] === 'true'
				)
			),
			'margin' => !empty($settings['margin']) ? (int)$settings['margin'] : 0,
			'stagePadding' => !empty($settings['padding']) ? $settings['padding'] : 0
		);

		$settings['carousel'] = $carouselOptions;
		return $this->load->view( 'extension/module/pavobuilder/pa_instagram/pa_instagram', array( 'settings' => $settings, 'content' => $content ) );
	}
}