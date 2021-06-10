<?php
class ControllerExtensionModuleSendinBlue extends Controller {
    private $error = array();
    private $_api_url = 'https://api.sendinblue.com/v2.0';
    private $_api_key = '';
    private $version = "1.0.2";

    public function index() {

        $extension_type = 'extension/module';
        $classname = 'sendinblue';

        $this->load->model('setting/setting');
        $this->load->language('extension/module/' .$classname);

        if (isset($this->session->data['user_token'])) {
            $data['token'] = $this->session->data['user_token'];
            $data['token_key'] = $token_key = 'user_token';
            $extension_path = 'marketplace/extension';
        } else {
            $data['token'] = $this->session->data['token'];
            $data['token_key'] = $token_key = 'token';
            $extension_path = 'extension/extension';
        }

        $settings = $this->model_setting_setting->getSetting($classname);

        // Edit database command for send email
        if (isset($settings[$classname . '_' . 'transactionalemails'])){
            // Check status transactional emails status
            if ($settings[$classname . '_' . 'transactionalemails']){
                // Disable default events for order.
                $this->db->query("UPDATE `" . DB_PREFIX . "event` SET status = 0 WHERE `code` = 'mail_order_add'");
                $this->db->query("UPDATE `" . DB_PREFIX . "event` SET status = 0 WHERE `code` = 'mail_order_alert'");
            } else {
                // Enable default events for order.
                $this->db->query("UPDATE `" . DB_PREFIX . "event` SET status = 1 WHERE `code` = 'mail_order_add'");
                $this->db->query("UPDATE `" . DB_PREFIX . "event` SET status = 1 WHERE `code` = 'mail_order_alert'");
            }
        }

        if (isset($settings[$classname . '_' . 'api_key'])) {
            $this->_api_key = $settings[$classname . '_' . 'api_key'];
        }

        // Account Details
        $data['version'] = $this->version;
        $data['sms_left'] = '0';
        $data['email_left'] = '0';
        $data['account'] = false;
        $account = $this->get_account();
        //$this->printr($account);
        //$this->printr($account);
        $data['hasSMTP'] = false;
        $data['full_account'] = false;
        $data['account_details'] = array();

        $data['lists'] = array();
        $default_list_id = 0;
        $double_optin_list_id = 0;
        $folders = array();

        if (isset($account['code'])) {
            $data['account'] = $account['message'];
        } else {
            $data['email_left'] = $account['plan'][0]['credits'];
            $data['sms_left'] = $account['plan'][1]['credits'];
            if (isset($account['relay']['enabled']) && $account['relay']['enabled']) {
                $data['hasSMTP'] = true;
                $this->create_default_templates();
            }
            $this->create_order_attributes();

            $data['full_account'] = $account;

            // Displayed Account array of filtered details
            $data['account_details']['Email'] = $account['email'];
            $data['account_details']['Credits'] = $data['email_left'];
            $data['account_details']['SMS Credits'] = $data['sms_left'];
            $data['account_details']['SMTP Enabled'] = !empty($account['relay']['enabled']) ? 'True' : 'False';
            $data['account_details']['Automation Enabled'] = !empty($account['marketingAutomation']['key']) ? 'True' : 'False';
            $data['full_account'] = false;

            // Template Details
            $data['templates'] = array();
            $templates = $this->get_templates('Double', true);
            if (isset($templates['code'])) {
                // show error
            } elseif (!isset($templates['count']) || !$templates['count']) {
                $data['templates'] = array();
            } else {
                $data['templates'] = $templates['templates'];
            }

            // Template Details
            $data['doubleopt_templates'] = array();
            $dotemplates = $this->get_templates('Double');
            if (isset($dotemplates['code'])) {
                // show error
            } elseif (!isset($dotemplates['count']) || !$dotemplates['count']) {
                $data['doubleopt_templates'] = array();
            } else {
                $data['doubleopt_templates'] = $dotemplates['templates'];
            }

            // Folders - Add "form" folder for temp - doubleoptin list
            $folders = $this->get_folders();
            $formfolderid = 1;
            $bFormFolderFound = false;
            if (!empty($folders) && !empty($folders['folders'])) {
                foreach ($folders['folders'] as $folder) {
                    if ($folder['name'] == 'Form') {
                        $bFormFolderFound = true;
                        $formfolderid = $folder['id'];
                        break;
                    }
                }
            }

            if (!$bFormFolderFound) {
                $class = 'ContactsApi';
                try {
                    $mailin = $this->init($class);
                    $createFolder = new \SendinBlue\Client\Model\CreateUpdateFolder(array('name' => 'Form'));
                    $results = json_decode($mailin->createFolder($createFolder), true);
                    $formfolderid = $results['id'];
                } catch (Exception $e) {
                    $this->printr($e->getMessage());
                }
            }

            // Add it to the view so that it saves to the db
            $data['hidden']['form_form_id'] = $formfolderid;

            // Lists
            $lists = $this->get_lists();

            if (isset($lists['code'])) {
                // todo show error
            } else {
                $data['lists'] = $lists['lists'];

                // Create default opencart list
                $bOCFound = false;
                $bTMPFound = false;

                foreach ($lists['lists'] as $list) {

                    if ($list['name'] == 'OpenCart') {
                        $bOCFound = true;
                        $default_list_id = $list['id'];
                    }
                    if ($list['name'] == 'Temp - DOUBLE OPTIN') {
                        $bTMPFound = true;
                        $double_optin_list_id = $list['id'];
                    }
                }
                if (!$bOCFound) {
                    $class = 'ContactsApi';
                    try {
                        $mailin = $this->init($class);
                        $createList = new \SendinBlue\Client\Model\CreateList(array('name' => 'OpenCart', 'folderId' => 1));
                        $results = json_decode($mailin->createList($createList), true);
                        $default_list_id = $results['id'];
                        $lists = $this->get_lists();
                        $data['lists'] = $lists['lists'];
                    } catch (Exception $e) {
                        $this->printr(json_decode($e->getResponseBody(), true));
                    }
                }

                if (!$bTMPFound) {
                    $class = 'ContactsApi';
                    try {
                        $mailin = $this->init($class);
                        $createList = new \SendinBlue\Client\Model\CreateList(array('name' => 'Temp - DOUBLE OPTIN', 'folderId' => $formfolderid));
                        $results = json_decode($mailin->createList($createList), true);
                        $double_optin_list_id = $results['id'];
                        $lists = $this->get_lists();
                        $data['lists'] = $lists['lists'];
                    } catch (Exception $e) {
                        $this->printr(json_decode($e->getResponseBody(), true));
                    }
                }

            }

        }

        // Add lists to the view so they are saved to the db
        $data['hidden']['default_list_id'] = $default_list_id;
        $data['hidden']['double_optin_list_id'] = $double_optin_list_id;

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            // disable some settings if not SMTP
            if (!$data['hasSMTP']) {
                $this->request->post['transactionalemails'] = false;
                $this->request->post['order_status_email'] = array();
                $this->request->post['usefinaltemplate'] = false;
                $this->request->post['confirmation'] = 0;
                $this->request->post['simple_confirmation_template'] = false;
                $this->request->post['doubleoptintemplate'] = false;
                $this->request->post['finaltemplate'] = false;
            }

            // disable some settings if not Automation
            if (empty($account['marketingAutomation']['key'])) {
                $this->request->post['tracking_id'] = false;
                $this->request->post['automation'] = false;
            }

            // 2.3+ requires keys to have the name of the module prefixed (so dumb)
            foreach ($this->request->post as $key => $value) {
                $this->request->post[$classname.'_'.$key] = $this->request->post[$key];
                unset($this->request->post[$key]);
            }

            if ($data['account_details']) {
                $this->request->post['sendinblue_status'] = 1;
            } else {
                $this->request->post['sendinblue_status'] = 0;
            }

            $this->model_setting_setting->editSetting($classname, $this->request->post);

            //3.0 compatibility - must insert status and sort order with extension type prefix to satisfy some validation
            if (version_compare(VERSION, '3.0', '>=')) {
                foreach ($this->request->post as $key => $value) {
                    if ($key == $classname . '_status' || $key == $classname . '_sort_order') {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = '" . $this->db->escape($classname) . "', `key` = '" . $this->db->escape(str_replace("extension/", "", $extension_type).'_'.$key) . "', `value` = '" . $this->db->escape($value) . "'");
                    }
                }
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/module/' . $classname, $token_key . '=' . $data['token'] . '&type=module', true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $token_key . '=' . $data['token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link($extension_path, $token_key . '=' . $data['token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/' . $classname, $token_key . '=' . $data['token'], true)
        );

        $this->document->setTitle($this->language->get('heading_title_text'));
        $data['heading_title'] = $this->language->get('heading_title');
        $data['heading_title_text'] = $this->language->get('heading_title_text');
        //$data['orderStatuses']    = $this->getAllOrderStatuses();
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['action'] = $this->url->link('extension/module/' . $classname, $token_key . '=' . $data['token'], true);

        $data['cancel'] = $this->url->link($extension_path, $token_key . '=' . $data['token'] . '&type=module', true);

        // Get all countries for SMS list
        $this->load->model('localisation/country');
        $data['countries'] = $this->model_localisation_country->getCountries();

        $fields = array();

        $fields[] = array(
            'name' => 'api_key',
            'default' => ''
        );

        $fields[] = array(
            'name' => 'contactmanager',
            'default' => 0
        );

        $fields[] = array(
            'name' => 'active_lists',
            'default' => array(),
            'dependency_fields' => array('contactmanager' => true)
        );

        $fields[] = array(
            'name' => 'confirmation',
            'default' => 0,
            'dependency_fields' => array('transactionalemails' => true)
        );

        $fields[] = array(
            'name' => 'doubleoptintemplate',
            'default' => 0,
            'dependency_fields' => array('confirmation' => 2, 'transactionalemails' => true)
        );

        $fields[] = array(
            'name' => 'simple_confirmation_template',
            'default' => 0,
            'dependency_fields' => array('transactionalemails' => true)
        );

        $fields[] = array(
            'name' => 'useredirect',
            'default' => false
        );

        $fields[] = array(
            'name' => 'redirecturl',
            'default' => HTTPS_CATALOG
        );

        $fields[] = array(
            'name' => 'usefinaltemplate',
            'default' => false
        );

        $fields[] = array(
            'name' => 'finaltemplate',
            'default' => 0
        );

        $fields[] = array(
            'name' => 'smtp_password',
            'default' => '',
            'dependency_fields' => array('transactionalemails' => true)
        );

        $fields[] = array(
            'name' => 'smsnotify',
            'default' => 0,
            'dependency_fields' => array('transactionalemails' => true)
        );

        $fields[] = array(
            'name' => 'alertemail',
            'default' => $this->config->get('config_email')
        );

        $fields[] = array(
            'name' => 'usesmscountry',
            'default' => 0
        );

        $fields[] = array(
            'name' => 'smscountry',
            'default' => false,
            'dependency_fields' => array('usesmscountry' => true)
        );

        $fields[] = array(
            'name' => 'threshold',
            'default' => 5
        );

        $fields[] = array(
            'name' => 'transactionalemails',
            'default' => false
        );

        $fields[] = array(
            'name' => 'automation',
            'default' => false
        );

        $fields[] = array(
            'name' => 'order_status_email',
            'default' => array(),
            'dependency_fields' => array('transactionalemails' => true)
        );

        $fields[] = array(
            'name' => 'cart_attribute_map',
            'default' => array(),
            'dependency_fields' => array('contactmanager' => true, 'attribute_mapping' => true)
        );

        $fields[] = array(
            'name' => 'attribute_mapping',
            'default' => false,
            'dependency_fields' => array('contactmanager' => true)
        );

        $fields[] = array(
            'name' => 'testemail',
            'default' => ''
        );

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $data['cart_attributes'] = array(
            'firstname',
            'lastname',
            'telephone',
            'fax',
            'payment_company',
            'payment_address_1',
            'payment_address_2',
            'payment_city',
            'payment_postcode',
            'payment_country',
            'payment_zone',
            'payment_zone_code',
        );

        $attributes = $this->get_attributes();

        $data['attributes'] = array();
        if (!empty($attributes['attributes'])) {
            foreach ( $attributes['attributes'] as $at ) {
                if ( $at['category'] == 'normal' ) {
                    $data['attributes'][] = array(
                        'name' => $at['name'],
                    );
                }
            }
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        foreach ($fields as $field) {

            if (isset($settings[$classname . '_' . $field['name']])) {
                $data[$field['name']] = $settings[$classname . '_' . $field['name']];
            } else {
                $data[$field['name']] = $field['default'];
            }
        }

        // disable some settings if not SMTP
        if (!$data['hasSMTP']) {
            $data['transactionalemails'] = false;
            $data['order_status_email'] = array();
            $data['usefinaltemplate'] = false;
            $data['confirmation'] = 0;
            $data['simple_confirmation_template'] = false;
            $data['doubleoptintemplate'] = false;
            $data['finaltemplate'] = false;
        }

        // disable some settings if not Automation
        if (!empty($account['marketingAutomation']['key'])) {
            $data['tracking_id'] = $account['marketingAutomation']['key'];
        } else {
            $data['tracking_id'] = false;
            $data['automation'] = false;
        }

        if (version_compare(VERSION, '2.0', '>=')) { // v2.0.x Compatibility
            $data['header'] = $this->load->controller('common/header');
            $data['menu'] = $this->load->controller('common/menu');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            if (version_compare(VERSION, '3.0', '>=')) { // v3.x Compatibility to support twig and tpl files
                $file = (DIR_TEMPLATE . $extension_type . '/' .$classname.'.twig');
                if (is_file($file)) {
                    $this->response->setOutput($this->load->view($extension_type . '/' .$classname, $data));
                } else {
                    $file = (DIR_TEMPLATE . $extension_type . '/' .$classname.'.tpl');
                    extract($data);
                    ob_start();
                    if (class_exists('VQMod')) { require(VQMod::modCheck(modification($file), $file));	} else { require(modification($file)); }
                    $this->response->setOutput(ob_get_clean());
                }
            } elseif (version_compare(VERSION, '2.2', '>=')) { // v2.2.x Compatibility
                $this->response->setOutput($this->load->view($extension_type . '/'. $classname, $data));
            } else { // 2.x
                $this->response->setOutput($this->load->view($extension_type . '/'.$classname.'.tpl', $data));
            }
        } elseif (version_compare(VERSION, '2.0', '<')) {  // 1.5.x Backwards Compatibility
            $this->data = array_merge($this->data, $data);
            $this->id       = 'content';
            $this->template = $extension_type . '/' . $classname . '.tpl';

            $this->children = array(
                'common/header',
                'common/footer'
            );
            $this->response->setOutput($this->render(TRUE));
        }
    }

    public function install() {
        $sql = "
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "sendinblue_data` (
		  `sib_id` int(11) NOT NULL AUTO_INCREMENT,
		  `email` varchar(32) NULL,
		  PRIMARY KEY (`sib_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
        $this->db->query($sql);

        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_order_add'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_order_add', `trigger` = 'catalog/model/checkout/order/addOrderHistory/after', `action` = 'extension/module/sendinblue/addOrder', `status` = '1', `sort_order` = '0'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_customer_add'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_customer_add', `trigger` = 'catalog/model/account/customer/addCustomer/after', `action` = 'extension/module/sendinblue/addCustomer', `status` = '1', `sort_order` = '0'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_subscription_check'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_subscription_check', `trigger` = 'catalog/model/account/customer/editNewsletter/after', `action` = 'extension/module/sendinblue/checkSubscription', `status` = '1', `sort_order` = '0'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_identify'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_track_identify', `trigger` = 'catalog/controller/common/header/after', `action` = 'extension/module/sendinblue/identify', `status` = '1', `sort_order` = '0'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_page'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_track_page', `trigger` = 'catalog/controller/common/header/after', `action` = 'extension/module/sendinblue/trackPage', `status` = '1', `sort_order` = '0'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_atc'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_track_atc', `trigger` = 'catalog/controller/checkout/cart/add/after', `action` = 'extension/module/sendinblue/trackEventAddToCart', `status` = '1', `sort_order` = '0'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_uc'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_track_uc', `trigger` = 'catalog/controller/checkout/cart/after', `action` = 'extension/module/sendinblue/trackEventAddToCart', `status` = '1', `sort_order` = '0'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_rfc'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_track_rfc', `trigger` = 'catalog/controller/checkout/cart/remove/after', `action` = 'extension/module/sendinblue/trackEventAddToCart', `status` = '1', `sort_order` = '0'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_order_conf'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_track_order_conf', `trigger` = 'catalog/model/checkout/order/addOrderHistory/after', `action` = 'extension/module/sendinblue/trackEventOrderConfirmation', `status` = '1', `sort_order` = '0'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_mail_order_add'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_mail_order_add', `trigger` = 'catalog/model/checkout/order/addOrderHistory/before', `action` = 'mail/sendinblue_order', `status` = '1', `sort_order` = '0'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_mail_order_alert'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_mail_order_alert', `trigger` = '	catalog/model/checkout/order/addOrderHistory/before', `action` = 'mail/sendinblue_order/alert', `status` = '1', `sort_order` = '0'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "event` SET `code` = 'sendinblue_track_order_change', `trigger` = 'catalog/model/checkout/order/addOrderHistory/before', `action` = 'extension/module/sendinblue/trackEventChangeOrderStatus', `status` = '1', `sort_order` = '0'");
    }

    public function uninstall() {
        // Enable default events for order
        $this->db->query("UPDATE `" . DB_PREFIX . "event` SET status = 1 WHERE `code` = 'mail_order_add'");
        $this->db->query("UPDATE `" . DB_PREFIX . "event` SET status = 1 WHERE `code` = 'mail_order_alert'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_mail_order_add'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_mail_order_alert'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_order_add'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_customer_add'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_subscription_check'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_identify'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_page'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_atc'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_uc'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_rfc'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_order_conf'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'sendinblue_track_order_change'");
    }

    public function ajaxsave() {

        $classname = 'sendinblue';
        $json = array();

        file_put_contents(DIR_LOGS . 'ajaxsave.txt', "\r\nGET" . print_r($_GET,1) . "\r\nPOST" . print_r($_POST,1));
        if (!empty($_GET['name'])) {

            $code = $classname;
            $key = $_GET['name'];
            $value = $_GET['value'];
            $store_id = 0;

            //if (empty($value) && (string)$value === "") { // remember 0 = empty so we might want to change this
            // Do nothing
            //	$res = "Doing nothing";
            //} else {
            // 2.3+ requires keys to have the name of the module prefixed (so dumb)
            foreach ($this->request->post as $key => $value) {
                //if (is_array($value)) { $this->request->post[$key] = implode(',', $value); }
                $this->request->post[$classname.'_'.$key] = $this->request->post[$key];
                unset($this->request->post[$key]);
            }
            //$this->load->model('setting/setting');
            //$this->model_setting_setting->editSetting($classname, $this->request->post);
            foreach ($this->request->post as $key => $value) {
                //if (substr($key, 0, strlen($code)) == $code) {
                $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "'");
                if (!$_GET['delete']) {
                    if (!is_array($value)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
                    } else {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
                    }
                    $res = "Updated $key elements";
                } else {
                    $res = "Deleted all $key elements";
                }
                //}
            }



            //}

            $json['success'] = $res;

        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }


    public function printr($arr) {
        exit('<pre>' . print_r($arr, 1) . '</pre>');
    }

    public function ajax_send_test_mail() {

        $account = $this->get_account();

        //$this->printr($smtp);
        $json = array();
        if (!$this->config->get('sendinblue_smtp_password')) {
            $json['error'] = 'Please enter an SMTP password and click save before sending a test email';
        } elseif (!filter_var($this->request->post['testemail'], FILTER_VALIDATE_EMAIL)) {
            $json['error'] = 'Invalid email.';
        } elseif (!isset($account['relay']['enabled']) || $account['relay']['enabled'] != '1') {
            $json['error'] = 'SMTP relay is not supported on your SendinBlue account';
        } else {
            //exit(print_r($account['relay']));
            $mail = new Mail('smtp');
            $mail->protocol = 'smtp';
            $mail->parameter = '';
            $mail->smtp_hostname = $account['relay']['data']['relay'];
            $mail->smtp_username = $account['relay']['data']['userName'];
            $mail->smtp_password = html_entity_decode($this->config->get('sendinblue_smtp_password'), ENT_QUOTES, 'UTF-8');
            $mail->smtp_port = $account['relay']['data']['port'];
            $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

            $mail->setTo($this->request->post['testemail']);
            $mail->setFrom($this->request->post['testemail']);
            $mail->setSender(html_entity_decode('SIB Test', ENT_QUOTES, 'UTF-8'));
            $mail->setSubject(html_entity_decode('SIB Test Email', ENT_QUOTES, 'UTF-8'));
            //$mail->setHtml($this->load->view('mail/order', $data));
            $mail->setText('sibtest');
            file_put_contents(DIR_LOGS . 'sibmail.txt', print_r($mail,1));

            try {
                $mail->send();
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
            if (empty($json['error'])) {
                $json['success'] = 'Test Mail Sent.';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }

    public function tester() {
        $class = 'ContactsApi';
        $mailin = $this->init($class);
        try {
            $res = $mailin->getContactInfo('dssstrainer@gmail.com');
        } catch (Exception $e) {
            //$this->printr(json_decode($e->getResponseBody(),1));

        }
    }

    private function getContacts($offset = 0, $limit = 0, $time_offset = '-12 hours') {
        $class = 'ContactsApi';
        $mailin = $this->init($class);

        $modifiedSince = date("Y-m-d\TH:i:sP", strtotime($time_offset));
        try {
            $results = $mailin->getContacts($limit, $offset, $modifiedSince);
            //$this->printr($results);
        } catch (Exception $e) {
            echo 'Exception when calling ContactsApi->getContacts: ', $e->getMessage(), PHP_EOL;
            exit();
        }
    }

    // Export contacts from SIB to OpenCart.
    public function ajax_sync_contacts() {
        $limit = 500; // int | Number of documents per page
        $offset = 0; // int | Index of the first document of the page
        // \DateTime | Filter (urlencoded) the contacts modified after a given UTC date-time (YYYY-MM-DDTHH:mm:ss.SSSZ). Prefer to pass your timezone in date-time format for accurate result.
        //$modifiedSince = new \DateTime(date("Y-m-d\TH:i:sP", strtotime("-12 hours")));
        //exit(print_r($modifiedSince,1));
        //$modifiedSince = '2018-09-24T12:09:06.863-05:00';
        $modifiedSince = date("Y-m-d\TH:i:sP", strtotime("-22 hours"));
        $class = 'ContactsApi';
        $mailin = $this->init($class);

        try {
            $results = $mailin->getContacts($limit, $offset, $modifiedSince);
            //$this->printr($results);
        } catch (Exception $e) {
            echo 'Exception when calling ContactsApi->getContacts: ', $e->getMessage(), PHP_EOL;
            exit();
        }
        //$this->printr($results);
        $json = array();

        // Sync the customer table with details from sendinblue
        $cart_attribute_map = $this->config->get('sendinblue_cart_attribute_map');
        $this->load->model('customer/customer');
        $j = 0;
        file_put_contents(DIR_LOGS . 'contacts_synced_from_sib.txt', print_r($results['contacts'],1));
        foreach ($results['contacts'] as $r) {
            if (!isset($r['attributes'])) { $r['attributes'] = array(); }
            $attribs = json_decode(json_encode($r['attributes']), true);
            $newsletter = (int)!$r['emailBlacklisted'];
            $customer_info = $this->model_customer_customer->getCustomerByEmail($r['email']);
            $customer_array = array();
            if ($customer_info) {
                // Merge in cart attribute maps
                if ($this->config->get('sendinblue_attribute_mapping') && $cart_attribute_map) {
                    foreach ($cart_attribute_map as $map) {
                        //$this->printr($attribs[$map['sib']]);
                        if (empty($attribs[$map['sib']])) { continue; }
                        if (empty($customer_info[$map['field']])) { continue; }
                        $customer_array[$map['field']] = $attribs[$map['sib']];
                    }
                }
                file_put_contents(DIR_LOGS . 'sendinblue_customerpull.txt', print_r($customer_array,1));
                $json['data'] = $customer_array;
            }
            //$this->printr($customer_array);
            $sql = "UPDATE " . DB_PREFIX . "customer SET";
            if ($customer_array) {
                foreach ($customer_array as $k => $v) {
                    $sql .= " `$k` = '" . $this->db->escape($v) . "',";
                }
            }
            $sql .= " newsletter = '" . $newsletter . "' WHERE email = '" . $this->db->escape($r['email']) . "'";
            //file_put_contents(DIR_LOGS . 'synced_from_sib_sql.txt', "\r\n$sql", FILE_APPEND);
            $this->db->query($sql);
            //echo $sql . "\r\n";
            $j++;
        }

        if ($j) {
            $json['success'] = $j . " Contacts have been imported to your cart from SendinBlue";
        } else {
            $json['error'] = "No records with the filtered range available for sync";
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));

    }

    public function create_order_attributes() {

        $class = 'AttributesApi';
        $mailin = $this->init($class);

        $attributes = $this->get_attributes();

        //$this->printr($attributes);

        // Insert Category Attributes
        $data = array(
            "type" => "category",
            "data" => array(
                'DOUBLE_OPT-IN' => array(
                    'type' => "category",
                    'enum' => array(
                        '0' => array(
                            'value' => 1,
                            'label' => 'Yes'
                        ),
                        '1' => array(
                            'value' => 2,
                            'label' => 'No'
                        ),
                    )
                )
            )
        );

        foreach ($data['data'] as $name => $type) {
            $bFound = false;
            if (!empty($attributes['attributes'])) {
                foreach ($attributes['attributes'] as $at) {
                    if ($at['name'] == $name) {
                        $bFound = true;
                        break;
                    }
                }
            }
            if (!$bFound) {
                $createAttribute = new \SendinBlue\Client\Model\CreateAttribute(array('type' => $type['type'], 'enumeration' => $type['enum']));
                //$this->printr($createAttribute);
                try {
                    $res = $mailin->createAttribute($data['type'], $name, $createAttribute);
                    $this->printr($res);
                } catch (Exception $e) {
                    //echo 'Exception when calling ContactsApi->getContacts: ', $e->getMessage(), PHP_EOL;
                    //exit();
                }
            }
        }

        // Insert Normal Attributes
        $data = array(
            "type" => "normal",
            "data" => array(
                'FIRSTNAME' => "text",
                'LASTNAME'=> "text",
                'SMS'=> "text"
            )
        );

        foreach ($data['data'] as $name => $type) {
            $bFound = false;
            if (!empty($attributes['attributes'])) {
                foreach ($attributes['attributes'] as $at) {
                    //$this->printr($at);
                    //if ($at['category'] == $data['type']) { // not needed since attributes need to be unique across all categories apparently
                    if ($at['name'] == $name) {
                        $bFound = true;
                        break;
                    }
                    //}
                }
            }
            if (!$bFound) {
                $createAttribute = new \SendinBlue\Client\Model\CreateAttribute(array('type' => $type));
                try {
                    $res = $mailin->createAttribute($data['type'], $name, $createAttribute);
                    //$this->printr($results);
                } catch (Exception $e) {
                    //echo 'Exception when calling ContactsApi->getContacts: ', $e->getMessage(), PHP_EOL;
                    //exit();
                }
            }
        }


        // Insert Default Transactional Attributes
        $data = array(
            "type" => "transactional",
            "data" => array(
                'ORDER_ID' => 'id',
                'ORDER_DATE'=> 'date',
                'ORDER_PRICE'=> 'float'
            )
        );

        $calledhome = false;

        //$this->printr($attributes['attributes']);
        foreach ($data['data'] as $name => $type) {
            $bFound = false;
            if (!empty($attributes['attributes'])) {
                foreach ($attributes['attributes'] as $at) {
                    //if ($at['category'] == $data['type']) { // not needed since attributes need to be unique across all categories apparently
                    if ($at['name'] == $name) {
                        $bFound = true;
                        break;
                    }
                    //}
                }
            }
            if (!$bFound) {

                // Since we create attribs on the first run, we'll use this area to do the call home
                // Really I should save it to the db
                // On the First successful notice, do a call home to sib for install tracking:
                //Call home to SendinBlue to keep track of when the module is installed
                if (!$calledhome) {
                    $data_string = json_encode(array("partnerName" => "OpenCart"));
                    $ch = @curl_init('https://api.sendinblue.com/v3/account/partner');
                    @curl_setopt($ch, CURLOPT_PORT, 443);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'api-key: ' . $this->config->get('sendinblue_api_key')
                    ));
                    @curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    @curl_setopt($ch, CURLOPT_POST, true);
                    @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                    @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                    @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                    @curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                    @curl_exec($ch);
                    if (@curl_error($ch)) {
                        $this->log->write(@curl_error($ch));
                    }
                    @curl_close($ch);
                    $calledhome = true;
                    file_put_contents(DIR_LOGS . 'sib_callhome.txt', "\r\n" . date('Ymd-His'), FILE_APPEND);
                }
                //

                $createAttribute = new \SendinBlue\Client\Model\CreateAttribute(array('type' => $type));
                try {
                    $res = $mailin->createAttribute($data['type'], $name, $createAttribute);
                    //$this->printr($results);
                } catch (Exception $e) {
                    //echo 'Exception when calling ContactsApi->getContacts: ', $e->getMessage(), PHP_EOL;
                    //exit();
                }
            }
        }

        // Insert Default Calculated Attributes
        $data = array(
            "type" => "calculated",
            "data" => array(
                'OPENCART_CA_USER' => "SUM[ORDER_PRICE]",
                'OPENCART_LAST_30_DAYS_CA'=> "SUM[ORDER_PRICE,ORDER_DATE,>,NOW(-30)]",
                'OPENCART_ORDER_TOTAL'=> "COUNT[ORDER_ID]"
            )
        );
        //$this->printr($attributes['attributes']);
        foreach ($data['data'] as $name => $value) {
            $bFound = false;
            if (!empty($attributes['attributes'])) {
                foreach ($attributes['attributes'] as $at) {
                    //if ($at['category'] == $data['type']) { // not needed since attributes need to be unique across all categories apparently
                    if ($at['name'] == $name) {
                        $bFound = true;
                        break;
                    }
                    //}
                }
            }
            if (!$bFound) {
                $createAttribute = new \SendinBlue\Client\Model\CreateAttribute(array('value' => $value));
                try {
                    $res = $mailin->createAttribute($data['type'], $name, $createAttribute);
                    //$this->printr($results);
                } catch (Exception $e) {
                    //echo 'Exception when calling ContactsApi->getContacts: ', $e->getMessage(), PHP_EOL;
                    //exit();
                }
            }
        }

        // Insert Default Global Attributes
        $data = array(
            "type" => "global",
            "data" => array(
                'OPENCART_CA_TOTAL' => "SUM[OPENCART_CA_USER]",
                'OPENCARTS_CA_LAST_30DAYS'=> "SUM[OPENCART_LAST_30_DAYS_CA]",
                'OPENCART_ORDERS_COUNT'=> "SUM[OPENCART_ORDER_TOTAL]"
            )
        );
        //$this->printr($attributes['attributes']);
        foreach ($data['data'] as $name => $value) {
            $bFound = false;
            if (!empty($attributes['attributes'])) {
                foreach ($attributes['attributes'] as $at) {
                    //if ($at['category'] == $data['type']) { // not needed since attributes need to be unique across all categories apparently
                    if ($at['name'] == $name) {
                        $bFound = true;
                        break;
                    }
                    //}
                }
            }
            if (!$bFound) {
                $createAttribute = new \SendinBlue\Client\Model\CreateAttribute(array('value' => $value));
                try {
                    $res = $mailin->createAttribute($data['type'], $name, $createAttribute);
                    //$this->printr($results);
                } catch (Exception $e) {
                    //echo 'Exception when calling ContactsApi->getContacts: ', $e->getMessage(), PHP_EOL;
                    //exit();
                }
            }
        }

    }

    public function create_default_templates() {

        $templates = $this->get_templates();
        $account = $this->get_account();
        //$this->printr($account);
        $default_sender_email = $account['email'];

        //$this->printr($template_list);

        $class = 'SMTPApi';
        try {
            $mailin = $this->init($class);
        } catch (Exception $e) {
            return json_decode($e->getResponseBody(), true);
        }
        $files = glob(DIR_SYSTEM . "library/sendinblue_data/templates/*.{html}", GLOB_BRACE);

        foreach ($files as $file) {
            $bFound = false;
            $tpl_name = basename($file, '.html');
            if (isset($templates['count']) && $templates['count']) {
                $template_list = $templates['templates'];
                //$this->printr($template_list);
                foreach ($template_list as $tpl) {
                    if ($tpl_name == $tpl['name']) {
                        $bFound = true;
                        break;
                    }
                }
            }

            if (!$bFound) {

                //$sender = new \SendinBlue\Client\Model\CreateSmtpSender(array('name' => $default_sender_email,'email' => $default_sender_email));

                $data = array(
                    "sender" => array(
                        'name' => $default_sender_email,
                        'email' => $default_sender_email
                    ),
                    "templateName" => $tpl_name,
                    "htmlContent" => file_get_contents($file),
                    "subject" => str_replace("_", " ", $tpl_name),
                    "replyTo" => $default_sender_email,
                    // "toField" => "",
                    "isActive" => true,
                    // "attachmentUrl" => ""
                );

                $smtpTemplate = new \SendinBlue\Client\Model\CreateSmtpTemplate($data);

                $res = $mailin->createSmtpTemplate($smtpTemplate);
                //$this->printr($res);
            }
        }

        //return $templates['message'];
    }

    public function ajax_import_customers() {

        $class = 'ContactsApi';
        try {
            $mailin = $this->init($class);
        } catch (Exception $e) {
            return json_decode($e->getResponseBody(), true);
        }

        $filename = DIR_SYSTEM . 'library/sendinblue_data/csv/sib_import_customers.csv';

        // Import Customers
        $this->load->model('customer/customer');

        $records = $this->model_customer_customer->getCustomers(array('filter_newsletter' => '1'));

        if (!empty($_POST['cart_attribute_map'])) {
            $cart_attribute_map = $_POST['cart_attribute_map'];
        } else {
            $cart_attribute_map = $this->config->get('sendinblue_cart_attribute_map');
        }

        foreach ($records as $r => $row) {
            //$this->db->query("INSERT INTO " . DB_PREFIX . "sendinblue_data SET email = '" . $this->db->escape($email) . "' ON DUPLICATE KEY UPDATE email=email");
            $data[$r]['EMAIL'] = $row['email'];
            $data[$r]['LASTNAME'] = $row['lastname'];
            $data[$r]['FIRSTNAME'] = $row['firstname'];
            //$data[$r]['SMS'] = $row['telephone'];

            $address = $this->model_customer_customer->getAddress($row['address_id']);

            if (!empty($address)) {
                $row = array_merge($row, $address);
            }

            // Merge in cart attribute maps
            if ($this->config->get('sendinblue_attribute_mapping') && $cart_attribute_map) {

                foreach ($cart_attribute_map as $map) {
                    if (empty($map['sib'])) { continue; }
                    if (empty($row[$map['field']]) || empty($row[str_replace('payment_', '', $map['field'])])) { $row[$map['field']] = ''; }
                    if ($map['sib'] == 'SMS') { $row[$map['field']] = str_pad($row[$map['field']],10,"0"); }
                    $data[$r][$map['sib']] = !empty($row[str_replace('payment_', '', $map['field'])]) ? $row[str_replace('payment_', '', $map['field'])] : ''; // trim "payment_" prefix for customer address table
                }
            }
            file_put_contents(DIR_LOGS . 'sendinblue_customerpush.txt', print_r($data[$r],1), FILE_APPEND);
        }

        if (!empty($_POST['active_lists'])) {
            $active_lists = $_POST['active_lists'];
        } else {
            $active_lists = $this->config->get('sendinblue_active_lists');
        }

        $json = array();

        $intArray = array();
        if (!empty($active_lists)) {
            foreach ($active_lists as $value) {
                $intArray[] = intval($value);
            }
        }

        //exit($active_lists);
        if (empty($intArray)) {
            $json['error'] = 'Please check at least one list.';
        }

        if (!isset($json['error'])) {
            if (!empty($data)) {
                //if (file_exists($filename) || $this->createCSV($filename, $data)) {
                if ($this->createCSV($filename, $data)) {

                    $data = array(
                        "fileUrl" => HTTP_CATALOG . 'sendinblue/csv/' . basename($filename),
                        "listIds" => $intArray,
                        "notifyUrl" => HTTP_CATALOG . 'sendinblue/csv_callback.php'
                    );

                    $requestContactImport = new \SendinBlue\Client\Model\RequestContactImport($data);
                    //$this->printr($requestContactImport);


                    try {
                        $res = $mailin->importContacts($requestContactImport);

                        if (!empty($res['processId'])) {
                            $json['pid'] = $res['processId'];
                            $json['success'] = 'Import started... Process ID: ' . $res['processId'];
                        } else {
                            $json['error'] = 'Import error';
                        }
                    } catch (Exception $e) {
                        $json['error'] = $e->getMessage();
                    }

                } else {
                    $json['error'] = 'Import failed.';
                }
            } else {
                $json['error'] = 'Nothing to import.';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function ajax_import_orders() {
        //file_put_contents(DIR_LOGS . 'import_orders_fields.txt', print_r($_POST,1));
        $class = 'ContactsApi';
        try {
            $mailin = $this->init($class);
        } catch (Exception $e) {
            return json_decode($e->getResponseBody(), true);
        }

        $filename = DIR_SYSTEM . 'library/sendinblue_data/csv/sib_import_orders.csv';

        // Import Orders
        $this->load->model('sale/order');
        $this->load->model('customer/customer');

        $records = $this->model_sale_order->getOrders();

        if (!empty($_POST['cart_attribute_map'])) {
            $cart_attribute_map = $_POST['cart_attribute_map'];
        } else {
            $cart_attribute_map = $this->config->get('sendinblue_cart_attribute_map');
        }

        foreach ($records as $r => $row) {

            $order_data = $this->model_sale_order->getOrder($row['order_id']);
            // Ignore temp orders
            if ($order_data['order_status_id'] == 0) { continue; }

            // Check customer newsletter flag
            if ($order_data['customer_id']) {
                $customer_info = $this->model_customer_customer->getCustomer($order_data['customer_id']);
                if ($customer_info) {
                    if (!$customer_info['newsletter']) { continue; }
                }
            } else { // customer no longer exists. Skip them.
                continue;
            }

            // Continue the numbering from the previous array
            //$r += $idx;
            //$r += count($data)+1;

            $data[$r]['EMAIL'] = $order_data['email'];
            $data[$r]['ORDER_ID'] = $row['order_id'];
            $data[$r]['ORDER_PRICE'] = $row['total'];
            $data[$r]['ORDER_DATE'] =  date("Y-m-d", strtotime($row['date_added']));

            // Merge in cart attribute maps
            if ($this->config->get('sendinblue_attribute_mapping') && $cart_attribute_map) {
                file_put_contents(DIR_LOGS . 'map.txt', print_r($cart_attribute_map, 1), FILE_APPEND);
                foreach ($cart_attribute_map as $map) {
                    if (empty($map['sib'])) { continue; }
                    if (empty($row[$map['field']])) {$row[$map['field']] = ''; }
                    if (!empty($order_data[$map['field']])) {
                        $data[$r][$map['sib']] = $order_data[$map['field']];
                    } elseif (isset($row[$map['field']])) {
                        $data[$r][$map['sib']] = $row[$map['field']];
                    }
                }
            }
            file_put_contents(DIR_LOGS . 'sendinblue_orderpush.txt', print_r($data,1));
        }

        if (!empty($_POST['active_lists'])) {
            $active_lists = $_POST['active_lists'];
        } else {
            $active_lists = $this->config->get('sendinblue_active_lists');
        }

        $intArray = array();
        if (!empty($active_lists)) {
            foreach ($active_lists as $value) {
                $intArray[] = intval($value);
            }
        }

        $json = array();

        if (empty($intArray)) {
            $json['error'] = 'Please select at least one list.';
        }

        if (!isset($json['error'])) {
            if (!empty($data)) {
                if ($this->createCSV($filename, $data)) {

                    $data = array(
                        "fileUrl" => HTTP_CATALOG . 'sendinblue/csv/' . basename($filename),
                        "listIds" => $intArray,
                        "notifyUrl" => HTTP_CATALOG . 'sendinblue/csv_callback.php'
                    );

                    $requestContactImport = new \SendinBlue\Client\Model\RequestContactImport($data);

                    $res = $mailin->importContacts($requestContactImport);

                    if (!empty($res['processId'])) {
                        $json['pid'] = $res['processId'];
                        $json['success'] = 'Import started... Process ID: ' . $res['processId'];
                    } else {
                        $json['error'] = 'Import error';
                    }
                } else {
                    $json['error'] = 'Import failed.';
                }
            }  else {
                $json['error'] = 'Nothing to import.';
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function ajax_getprocess() {
        $pid = $_GET['pid'];

        $class = 'ProcessApi';
        try {
            $mailin = $this->init($class);

            $start_time = time();
            do {
                $res = $mailin->getProcess($pid);
            } while ($res['status'] != 'completed' && (time() - $start_time) < 30);

            $json['pid'] = $pid;
            if ($res['status'] == 'completed') {
                $json['success'] = 1;
            } else {
                $json['error'] = 1;
            }
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } catch (Exception $e) {
            return json_decode($e->getResponseBody(), true);
        }

    }

    private function createCSV($filename = 'somefile.csv', $data, $return_data = false) {

        if (file_exists($filename)) { unlink($filename); }

        $fp = fopen($filename, 'w');

        // Header Row
        $header_row = array_keys($data[0]);
        fputcsv($fp, $header_row, ';');

        // Data Rows
        foreach ($data as $row) {
            fputcsv($fp, $row, ';');
        }

        fclose($fp);

        return true;

    }

    public function get_smtp_details() {
        $mailin = $this->init();
        return $mailin->get_smtp_details();
    }

    public function get_attributes() {
        $class = 'AttributesApi';
        try {
            $mailin = $this->init($class);
            return json_decode($mailin->getAttributes(), true);
        } catch (Exception $e) {
            file_put_contents(DIR_LOGS . 'sendinblue_error.txt', print_r(json_decode($e->getMessage(), true), 1), FILE_APPEND);
            return false;
        }
    }

    public function get_account() {

        $class = 'AccountApi';
        try {
            $mailin = $this->init($class);
            return json_decode($mailin->getAccount(), true);
        } catch (Exception $e) {
            return json_decode($e->getResponseBody(), true);
        }

    }

    private function get_template_by_name($template_name) {

        $mailin = $this->init();
        $data = array(
            'name' => $template_name
        );
        $result = $mailin->get_campaigns_v2($data);
        return $result;
    }

    public function get_templates($filter = false, $reverse_filter = false) {

        $class = 'SMTPApi';
        try {
            $mailin = $this->init($class);
            $templates = json_decode($mailin->getSmtpTemplates(), true);
            if (!isset($templates['count']) || !$templates['count']) {
                $templates['templates'] = array();
            }
            if ($filter) {
                //$this->printr($templates['templates']);
                foreach ($templates['templates'] as $k => $tpl) {
                    // if filter not found in template then remove from array
                    if ($reverse_filter) {
                        if (strpos($tpl['name'], $filter) !== false) {
                            unset($templates['templates'][$k]);
                        }
                    } else {
                        if (strpos($tpl['name'], $filter) === false) {
                            unset($templates['templates'][$k]);
                        }
                    }
                }
            }
            return $templates;
        } catch (Exception $e) {
            return json_decode($e->getResponseBody(), true);
        }
    }

    public function get_lists($page = 1) {

        // GET override
        if (isset($this->request->get['sib_page']) && is_numeric($this->request->get['sib_page'])) {
            $page = (int)$this->request->get['sib_page'];
        }

        $data = array(
            //"list_parent" => 5,
            "page" => $page,
            "page_limit" => 50
        );

        $class = 'ContactsApi';
        try {
            $mailin = $this->init($class);
            return json_decode($mailin->getLists(), true);
        } catch (Exception $e) {
            return json_decode($e->getResponseBody(), true);
        }

    }

    public function get_folders() {
        $class = 'ContactsApi';
        try {
            $mailin = $this->init($class);
            return json_decode($mailin->getFolders(50,0), true);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    private function init($class) {
        $classname = 'sendinblue';
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting($classname);
        //exit(print_r($settings,1));

        if (isset($settings[$classname . '_' . 'api_key'])) {
            $this->_api_key = $settings[$classname . '_' . 'api_key'];
        }

        require_once(DIR_SYSTEM . '/library/sendinblue/autoload.php');
        try {
            $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey("api-key", $this->_api_key);
            $ns = "SendinBlue\Client\Api\\" . $class;
            $apiInstance = new $ns(new GuzzleHttp\Client(), $config);
            return $apiInstance;
        } catch (Exception $e) {
            $this->printr($e);
            echo 'Exception: ', $e->getCode(), PHP_EOL;
            $this->error[] = 'Exception: ' . $e->getCode();
            //exit();
        }
    }


    protected function validate() {

        $extension_type = 'extension/module';
        $classname = 'sendinblue';

        if (!$this->user->hasPermission('modify', 'extension/module/' . $classname)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post['tracking_id']) && empty($this->request->post['tracking_id'])) {
            //	$this->error['tracking_id'] = 'Tracking ID required';
        }

        return !$this->error;
    }

}