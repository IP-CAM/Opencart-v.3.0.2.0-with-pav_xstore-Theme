<?php

class ControllerExtensionModulePavlistingtabs extends Controller
{
    private $error = array();
    private $data;
    public function index()
    {

        $this->language->load('extension/module/pavlistingtabs');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/category');
        $this->load->model('setting/module');
        $this->document->addScript('view/javascript/summernote/summernote.js');
		$this->document->addStyle('view/javascript/summernote/summernote.css');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (!isset($this->request->get['module_id'])) {
                $this->model_setting_module->addModule('pavlistingtabs', $this->request->post);
                $this->response->redirect($this->url->link('extension/module/pavlistingtabs', 'user_token=' . $this->session->data['user_token'], 'SSL'));
            } else {
                $this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
                $this->response->redirect($this->url->link('extension/module/pavlistingtabs', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], 'SSL'));
            }

            $this->session->data['success'] = $this->language->get('text_success');

        }

        //ALERT
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        if (isset($this->error['dimension'])) {
            $this->data['error_dimension'] = $this->error['dimension'];
        } else {
            $this->data['error_dimension'] = array();
        }

        // BREADCRUMBS
        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', 'SSL'),
            'separator' => ' :: '
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/pavlistingtabs', 'user_token=' . $this->session->data['user_token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->load->model('localisation/language');
        $this->data['languages'] = $this->model_localisation_language->getLanguages();
        $this->data['user_token'] = $this->session->data['user_token'];

        // DATA
        if (!isset($this->request->get['module_id'])) {
            $this->data['action'] = $this->url->link('extension/module/pavlistingtabs', 'user_token=' . $this->session->data['user_token'], 'SSL');
        } else {
            $this->data['action'] = $this->url->link('extension/module/pavlistingtabs', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], 'SSL');
        }

        $this->data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', 'SSL');

        // GET DATA SETTING
        if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
        }


        // products
        $this->load->model('catalog/product');


        // NAME
        if (isset($this->request->post['name'])) {
            $this->data['name'] = $this->request->post['name'];
        } elseif (!empty($module_info)) {
            $this->data['name'] = $module_info['name'];
        } else {
            $this->data['name'] = 'Module Name';
        }

        // STATUS
        if (isset($this->request->post['status'])) {
            $this->data['status'] = $this->request->post['status'];
        } elseif (!empty($module_info)) {
            $this->data['status'] = $module_info['status'];
        } else {
            $this->data['status'] = 1;
        }

        // LOOP

        if (isset($this->request->post['loop'])) {
            $this->data['loop'] = $this->request->post['loop'];
        } elseif (!empty($module_info)) {
            $this->data['loop'] = $module_info['loop'];
        } else {
            $this->data['loop'] = 1;
        }

        if (isset($this->request->post['title'])) {
            $this->data['title'] = $this->request->post['title'];
        } elseif (!empty($module_info)) {
            $this->data['title'] = $module_info['title'];
        } else {
            $this->data['title'] = '';
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

        // DESCRIPTION
        if (isset($this->request->post['description'])) {
            $this->data['description'] = $this->request->post['description'];
        } elseif (!empty($module_info)) {
            $this->data['description'] = $module_info['description'];
        } else {
            $this->data['description'] = '';
        }

        // CLASS
        if (isset($this->request->post['module_class'])) {
            $this->data['module_class'] = $this->request->post['module_class'];
        } elseif (!empty($module_info)) {
            $this->data['module_class'] = $module_info['module_class'];
        } else {
            $this->data['module_class'] = 'module_class';
        }

        // CLASS
        if (isset($this->request->post['tabs'])) {
            $this->data['tabs'] = $this->request->post['tabs'];
        } elseif (!empty($module_info)) {
            $this->data['tabs'] = $module_info['tabs'];
        } else {
            $this->data['tabs'] = array(1 => 'bestseller');
        }


        $results = $this->model_catalog_category->getCategories();
        // CLASS
        $first   = reset($results);
        if (isset($this->request->post['tabss'])) {
            $this->data['tabss'] = $this->request->post['tabss'];
        } elseif (!empty($module_info)) {
            $this->data['tabss'] = $module_info['tabss'];
        } else {
            $this->data['tabss'] = array(1 => $first['category_id']);
        }



        // width
        if (isset($this->request->post['width'])) {
            $this->data['width'] = $this->request->post['width'];
        } elseif (!empty($module_info)) {
            $this->data['width'] = $module_info['width'];
        } else {
            $this->data['width'] = '600';
        }

        // height
        if (isset($this->request->post['height'])) {
            $this->data['height'] = $this->request->post['height'];
        } elseif (!empty($module_info)) {
            $this->data['height'] = $module_info['height'];
        } else {
            $this->data['height'] = '666';
        }

        // itemsperpage
        if (isset($this->request->post['itemsperpage'])) {
            $this->data['itemsperpage'] = $this->request->post['itemsperpage'];
        } elseif (!empty($module_info)) {
            $this->data['itemsperpage'] = $module_info['itemsperpage'];
        } else {
            $this->data['itemsperpage'] = '4';
        }

        // cols
        if (isset($this->request->post['cols'])) {
            $this->data['cols'] = $this->request->post['cols'];
        } elseif (!empty($module_info)) {
            $this->data['cols'] = $module_info['cols'];
        } else {
            $this->data['cols'] = '1';
        }

        // limit
        if (isset($this->request->post['limit'])) {
            $this->data['limit'] = $this->request->post['limit'];
        } elseif (!empty($module_info)) {
            $this->data['limit'] = $module_info['limit'];
        } else {
            $this->data['limit'] = '4';
        }

        $tmptabs = array(
            'latest' => $this->language->get('text_latest'),
            'bestseller' => $this->language->get('text_bestseller'),
            'special' => $this->language->get('text_special'),
            'mostviewed' => $this->language->get('text_mostviewed'),
            'toprating' => $this->language->get('text_toprating'),
        );
        $this->data['tmptabs'] = $tmptabs;
        $this->data['results'] = $results;

        // RENDER
        $this->data['header'] = $this->load->controller('common/header');
        $this->data['column_left'] = $this->load->controller('common/column_left');
        $this->data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/pavlistingtabs', $this->data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/pavlistingtabs')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}