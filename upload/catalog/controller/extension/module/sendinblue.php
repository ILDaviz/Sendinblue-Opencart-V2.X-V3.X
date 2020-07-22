<?php
class ControllerExtensionModuleSendinBlue extends Controller {
	/*	
	// http://example.com/index.php?route=extension/module/sendinblue&email=john@doe.com&firstname=john&lastname=doe
	public function index() {
		$classname = 'sendinblue';
		
		if (isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
			$email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
			$firstname = $lastname = 'NA';
			if (isset($_GET['firstname'])) {
				$firstname = $this->db->escape($_GET['firstname']);
			}
			if (isset($_GET['lastname'])) {
				$lastname = $this->db->escape($_GET['lastname']);
			}				
		} else {
			exit('bad email');
		}
		
		// 0 - no confirm, 1 - simple (signup and notify), 2 - double (customer must click in email)
		$db_query = $this->db->query("SELECT email FROM " . DB_PREFIX . "sendinblue_data WHERE email = '" . $this->db->escape($email) . "'");
		if (!$db_query->num_rows) {
			if ($this->config->get($classname . '_confirmation') == 1) { // Simple
				$this->addCustomer($email, $firstname, $lastname);
				$this->notifyCustomer($email, $firstname, $lastname, $this->config->get($classname . '_simple_confirmation_template'));
			} elseif ($this->config->get($classname . '_confirmation') == 2) { // Double Opt
				$this->notifyCustomer($email, $firstname, $lastname, $this->config->get($classname . '_doubleoptintemplate'));
			} else { //0
				$this->addCustomer($email, $firstname, $lastname);
			}
		}
	}*/
	
	public function cron() {
		$classname = 'sendinblue';
		
		// SMS Notify threshold
		if ($this->config->get($classname . '_smsnotify') && $this->config->get($classname . '_threshold') && $this->config->get($classname . '_alertemail')) {
			$account = $this->get_account();
			$email_left = $account['data'][0]['credits'];
			$sms_left = $account['data'][1]['credits'];
			
			if ($sms_left < $this->config->get($classname . '_threshold')) {
				mail($this->config->get($classname . '_alertemail'), 'SendinBlue: Remaining SMS below the threshold', "Remaining SMS of $sms_left is below the alert threshold of " . $this->config->get($classname . '_threshold'));
				echo "mail sent";
			}			
		}
	}
	
	// Export contacts from SIB to OpenCart.
	public function syncContacts() {
		$this->load->model('extension/module/sendinblue');
		$this->model_extension_module_sendinblue->syncContacts();
	}
	
	// Event Triggered during account/customer/addCustomer/after
	public function addCustomer(&$route, &$args, &$output) {
	    if (!$this->config->get('sendinblue_contactmanager')) { return; }
		file_put_contents(DIR_LOGS . 'sib_event_triggered.txt', "\r\n" . __CLASS__ .'->'.__FUNCTION__, FILE_APPEND);
		//exit(print_r($args,1));
		//file_put_contents(DIR_LOGS . 'addorderargs.txt', 'route=' . print_r($route,1) . 'args=' . print_r($args,1) . 'output=' . print_r($output,1));
		//Q: sendinblue order submit
		$this->load->model('extension/module/sendinblue');
		$this->load->model('checkout/order');
		
		// Moving newsletter check to the model for blacklist purposes
		if (isset($args[0]['newsletter'])) {
			$newsletter = $args[0]['newsletter'];
		} else {
			$newsletter = 0;
		}
		if (!$newsletter) { return; }
	
		// Determine if customer needs to be opted in
		$email = $args[0]['email'];
		$firstname = $args[0]['firstname'];
		$lastname = $args[0]['lastname'];
		$db_query = $this->db->query("SELECT email FROM " . DB_PREFIX . "sendinblue_data WHERE email = '" . $this->db->escape($email) . "'");
		if (!$db_query->num_rows) {
			if ($this->config->get('sendinblue_transactionalemails') && $this->config->get('sendinblue_smtp_password')) {
				if ($this->config->get('sendinblue_confirmation') == 1) { // Simple
					//$this->model_extension_module_sendinblue->addCustomer($email, $firstname, $lastname);
					$this->model_extension_module_sendinblue->addRecord($email);
					$this->model_extension_module_sendinblue->notifyCustomer($email, $firstname, $lastname, $this->config->get('sendinblue_simple_confirmation_template'));
				} elseif ($this->config->get('sendinblue_confirmation') == 2) { // Double Opt
					$this->model_extension_module_sendinblue->addRecord($email, false, true);
					$this->model_extension_module_sendinblue->notifyCustomer($email, $firstname, $lastname, $this->config->get('sendinblue_doubleoptintemplate'));
				} else { //0
					//$this->model_extension_module_sendinblue->addCustomer($email, $firstname, $lastname);
					$this->model_extension_module_sendinblue->addRecord($email);
				}
			} else {
				$this->model_extension_module_sendinblue->addRecord($email);
			}
		} else { // If already in the system, just push the customer as there may be updates
			$this->model_extension_module_sendinblue->addRecord($email);
		}
	}
	
	// Event Triggered during checkout/confirm/addOrderHistory/after
	public function addOrder(&$route, &$args, &$output) {
	    if (!$this->config->get('sendinblue_contactmanager')) { return; }
		file_put_contents(DIR_LOGS . 'sib_event_triggered.txt', "\r\n" . __CLASS__ .'->'.__FUNCTION__, FILE_APPEND);
		//file_put_contents(DIR_LOGS . 'addorderargs.txt', 'route=' . print_r($route,1) . 'args=' . print_r($args,1) . 'output=' . print_r($output,1));
		//Q: sendinblue order submit
		$this->load->model('extension/module/sendinblue');
		$this->load->model('checkout/order');
		
		$order_id = $args[0];
		$order_status_id = $args[1];
		
		$order_info = $this->model_checkout_order->getOrder($order_id);
		
		// Determine if customer needs to be opted in
		$email = $order_info['email'];
		$firstname = $order_info['firstname'];
		$lastname = $order_info['lastname'];
		$db_query = $this->db->query("SELECT email FROM " . DB_PREFIX . "sendinblue_data WHERE email = '" . $this->db->escape($email) . "'");
		if (!$db_query->num_rows) {
			if ($this->config->get('sendinblue_transactionalemails') && $this->config->get('sendinblue_smtp_password')) {
				if ($this->config->get('sendinblue_confirmation') == 1) { // Simple
					//$this->model_extension_module_sendinblue->addCustomer($email, $firstname, $lastname);
					$this->model_extension_module_sendinblue->addRecord($email, $order_info['order_id']);
					$this->model_extension_module_sendinblue->notifyCustomer($email, $firstname, $lastname, $this->config->get('sendinblue_simple_confirmation_template'));
				} elseif ($this->config->get('sendinblue_confirmation') == 2) { // Double Opt
					$this->model_extension_module_sendinblue->addRecord($email, $order_info['order_id'], true);
					$this->model_extension_module_sendinblue->notifyCustomer($email, $firstname, $lastname, $this->config->get('sendinblue_doubleoptintemplate'));
				} else { //0
					//$this->model_extension_module_sendinblue->addCustomer($email, $firstname, $lastname);
					$this->model_extension_module_sendinblue->addRecord($email, $order_info['order_id']);
				}
			} else {
				$this->model_extension_module_sendinblue->addRecord($email, $order_info['order_id']);
			}
		} else { // If already in the system, just push the order
			//$results = $this->model_extension_module_sendinblue->addOrder($order_info['order_id']);
		    $results = $this->model_extension_module_sendinblue->addRecord($email, $order_info['order_id']);
		}
	}
	
	// Event Triggered during account/customer/editNewsletter/after
	public function checkSubscription(&$route, &$args, &$output) {
		$this->load->model('extension/module/sendinblue');
		if (!$this->config->get('sendinblue_contactmanager')) { return; }
		file_put_contents(DIR_LOGS . 'sib_event_triggered.txt', "\r\n" . __CLASS__ .'->'.__FUNCTION__, FILE_APPEND);
	    $newsletter = $args[0];
	    $email = $this->customer->getEmail();
		// Add this to add the customer if they weren't already added.
		$this->model_extension_module_sendinblue->addRecord($email);
		
	    $this->load->model('extension/module/sendinblue');
	    $this->model_extension_module_sendinblue->editBlacklist($email, $newsletter);
	}
	
			
	//http://example.com/index.php?route=extension/module/sendinblue/doubleopt_callback&email=john@doe.com&firstname=john&lastname=doe
	public function doubleopt_callback() {
		file_put_contents(DIR_LOGS . 'sendinblue_doubleoptcallback.txt', "\r\n" . print_r($_GET,1), FILE_APPEND);
		$classname = 'sendinblue';
	
		if (isset($_GET['email']) && filter_var(str_replace(' ', '+', $_GET['email']), FILTER_VALIDATE_EMAIL)) {
			$email = filter_var(str_replace(' ', '+', $_GET['email']), FILTER_VALIDATE_EMAIL);
			$this->load->model('account/customer');
			$customer_info = $this->model_account_customer->getCustomerByEmail($email);
			if (!$customer_info) { exit('customer info not found'); }
			$firstname = $customer_info['firstname'];
			$lastname = $customer_info['lastname'];
		} else {
			exit('invalid callback');
		}
			
		$this->load->model('extension/module/sendinblue');
		
		if (!empty($email)) {
			// Add record on callback
		    //$this->model_extension_module_sendinblue->addRecord($this->db->escape($_GET['email']));
			// Move record on callback
			$this->model_extension_module_sendinblue->moveUserFromDoubleOpt($this->db->escape($email));
		} else {
			$this->log->write('missing email on doubleopt callback');
		}
		
		// Final confirmation
		if ($this->config->get('sendinblue_usefinaltemplate') && $this->config->get('sendinblue_transactionalemails') && $this->config->get('sendinblue_smtp_password')) {
		    $this->model_extension_module_sendinblue->notifyCustomer($email, $firstname, $lastname, $this->config->get('sendinblue_finaltemplate'));
		}
		
		// Redirect
		if ($this->config->get($classname . '_useredirect')) {
		    $this->session->data['success'] = $this->language->get('text_success');
		    
		    $this->response->redirect($this->config->get($classname . '_redirecturl'));
		} else { // default to homepage
			$this->response->redirect(HTTP_SERVER);
		}
		
			/*
			$classname = 'sendinblue';
		if (isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
			$email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
			$firstname = $lastname = 'NA';
			if (isset($_GET['firstname'])) {
				$firstname = $this->db->escape($_GET['firstname']);
			}
			if (isset($_GET['lastname'])) {
				$lastname = $this->db->escape($_GET['lastname']);
			}				
			
			
						
			// Import last order if exists
			$this->load->model('checkout/order');
			$order_query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE email = '" . $this->db->escape($email) . "' ORDER BY order_id DESC LIMIT 1");
			//exit(print_r($order_query,1));
			if ($order_query->num_rows) {
				$order_id = $order_query->row['order_id'];
				$this->model_extension_module_sendinblue->addOrder($order_id);
			} else {
				$customer_query = $this->db->query("SELECT customer_id FROM `" . DB_PREFIX . "customer` WHERE email = '" . $this->db->escape($email) . "' ORDER BY customer_id DESC LIMIT 1");
				if ($customer_query->num_rows) {
					$customer_id = $customer_query->row['customer_id'];
					$this->model_extension_module_sendinblue->addCustomer($customer_id);
				}
			}
			
			// Final confirmation
			if ($this->config->get($classname . '_usefinaltemplate' && $this->config->get('sendinblue_transactionalemails') && $this->config->get('sendinblue_smtp_password'))) {
				$this->model_extension_module_sendinblue->notifyCustomer($email, $firstname, $lastname, $this->config->get($classname . '_finaltemplate'));
			}
			
			// Redirect
			if ($this->config->get($classname . '_useredirect')) {
				$this->session->data['success'] = $this->language->get('text_success');
			
				$this->response->redirect($this->config->get($classname . '_redirecturl'));
			}
		} else {
			$this->log->write('invalid email on doubleopt callback');
		}
		*/
	}
	
	
	
	// TRACKING EVENTS

    private function curlpost ($data, $method = 'identify') {
		
		if (!$this->config->get('sendinblue_automation')) { return; }
		if (!$this->config->get('sendinblue_contactmanager')) { return; }
		file_put_contents(DIR_LOGS . 'sib_event_triggered.txt', "\r\n" . __CLASS__ .'->'.$method, FILE_APPEND);
        $url = "https://in-automate.sendinblue.com/api/v2/$method";

        $headers = array(
            'Content-Type: application/json',
            'ma-key: ' . $this->config->get('sendinblue_tracking_id')
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
        ));

        $response = curl_exec($curl);
		file_put_contents(DIR_LOGS . 'sib_automation_tracking_curl_post.txt', "\r\n----------------\r\nMethod: $method\r\nData: " . json_encode($data) . "\r\nResult: " . print_r($response,1), FILE_APPEND);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            //echo $response;
        }
    }

    public function identify() {		
        if ($this->customer->isLogged() && $this->customer->getNewsletter()) {
            $email = $this->customer->getEmail();
        } elseif (isset($this->session->data['guest']['email'])) {
            $email = $this->session->data['guest']['email'];
        } else {
            return;
        }

	    $data = array(
	      'email' => $email
        );

	    $this->curlpost($data, 'identify');
    }

    public function trackPage() {
		if ($this->customer->isLogged() && $this->customer->getNewsletter()) {
            $email = $this->customer->getEmail();
        } elseif (isset($this->session->data['guest']['email'])) {
            $email = $this->session->data['guest']['email'];
        } else {
            return;
        }
	
        $data = array(
			'email' => $email,
            'page' => 'homepage',
            'ma_title' => $this->document->getTitle(),
            'ma_url' => $this->getCurrentURL(),
            'ma_path' => '/home'
        );

        $this->curlpost($data, 'trackPage');
    }
	
	public function trackEventOrderConfirmation(&$route, &$args, &$output) {
		file_put_contents(DIR_LOGS . 'trackEventOrderConf.txt', print_r($args,1));
		
		$event = 'order_completed';
		
		if ($this->customer->isLogged() && $this->customer->getNewsletter()) {
            $email = $this->customer->getEmail();
        } elseif (isset($this->session->data['guest']['email'])) {
            $email = $this->session->data['guest']['email'];
        } else {
            return;
        }
		
		$this->load->model('extension/module/sendinblue');
		$this->load->model('checkout/order');
		
		$order_id = $args[0];
		$order_status_id = $args[1];
		$order_info = $this->model_checkout_order->getOrder($order_id);
		
		$order_totals = $this->model_checkout_order->getOrderTotals($order_id);
		
		$shipping = 0;
		$subtotal = 0;
		$tax = 0;
		$discount = 0;
		foreach ($order_totals as $t) {
			if ($t['code'] == 'shipping') { $shipping = $t['value']; }
			if ($t['code'] == 'sub_total') { $subtotal = $t['value']; }
			if ($t['code'] == 'tax') { $tax += $t['value']; }
			if ($t['code'] == 'coupon') { $discount = $t['value']; }
		}
		
		$data = array(
            'email' => $email,
            'event' => $event,
            'properties' => array(
                'FIRSTNAME' => $this->customer->getFirstName(),
                'LASTNAME' => $this->customer->getLastName(),
            ),
            'eventdata' => array(
                'id' => $this->GUID(),
                'data' => array()
            )
        );

        $data['eventdata']['data']['Billing_Details'] = array(
			'billing_FIRST_NAME' => $order_info['payment_firstname'],
			'billing_LAST_NAME' => $order_info['payment_lastname'],
			'billing_COMPANY ' => $order_info['payment_company'],
			'billing_ADDRESS_1' => $order_info['payment_address_1'],
			'billing_ADDRESS_2' => $order_info['payment_address_2'],
			'billing_CITY' => $order_info['payment_city'],
			'billing_STATE' => $order_info['payment_zone'],
			'billing_POSTCODE' => $order_info['payment_postcode'],
			'billing_COUNTRY' => $order_info['payment_country'],
			'billing_PHONE' => $order_info['telephone'],
			'billing_EMAIL' => $order_info['email']
		);
		
		$data['eventdata']['data']['Shipping_Details'] = array(
			'shipping_FIRST_NAME' => $order_info['shipping_firstname'],
			'shipping_LAST_NAME' => $order_info['shipping_lastname'],
			'shipping_COMPANY ' => $order_info['shipping_company'],
			'shipping_ADDRESS_1' => $order_info['shipping_address_1'],
			'shipping_ADDRESS_2' => $order_info['shipping_address_2'],
			'shipping_CITY' =>$order_info['shipping_city'] ,
			'shipping_STATE' => $order_info['shipping_zone'],
			'shipping_POSTCODE' => $order_info['shipping_postcode'],
			'shipping_COUNTRY' => $order_info['shipping_country'],
			'shipping_METHOD_TITLE' => $order_info['shipping_method']
		);
		
		$data['eventdata']['data']['Order_Details'] = array(
			'order_ID' => $order_info['order_id'],
			'order_KEY' => $order_info['order_id'],
			'order_DISCOUNT ' => $discount ,
			'order_TAX' => $tax,
			'order_SHIPPING_TAX' => 0,
			'order_SHIPPING' => $shipping,
			'order_PRICE' => 0,
			'order_DATE' => $order_info['date_added'],
			'order_SUBTOTAL' => $subtotal,
			'order_DOWNLOAD_LINK' => ''
		);
		
		$data['eventdata']['data']['Miscalleneous'] = array(
			'cart_DISCOUNT' => '0',
			'cart_DISCOUNT_TAX' => '0',
			'customer_USER ' => $order_info['customer_id'],
			'payment_METHOD' => $order_info['payment_code'],
			'payment_METHOD_TITLE' => $order_info['payment_method'],
			'customer_IP_ADDRESS' => $order_info['ip'],
			'customer_USER_AGENT' => $order_info['user_agent'],
			'user_LOGIN' => '',
			'user_PASSWORD' => '',
			'refunded_AMOUNT' => 0
		);

        $this->curlpost($data, 'trackEvent');
		
		
	}

    public function trackEventAddToCart() {
		if ($this->customer->isLogged() && $this->customer->getNewsletter()) {
			$email = $this->customer->getEmail();
		} elseif (isset($this->session->data['guest']['email'])) {
			$email = $this->session->data['guest']['email'];
		} else {
			return;
		}
		
		$data = array(
			'email' => $email,
			'event' => '',
			'properties' => array(
				'FIRSTNAME' => $this->customer->getFirstName(),
				'LASTNAME' => $this->customer->getLastName(),
			),
			'eventdata' => array(
				'id' => $this->GUID(),
				'data' => array()
			)
		);
		
		$subtotal = $this->cart->getSubTotal();
		$total = $this->cart->getTotal();
		$tax_total = $total - $subtotal;
		
		$data['eventdata']['data']['subtotal'] = $subtotal;
		$data['eventdata']['data']['shipping'] = 0;
		$data['eventdata']['data']['total_before_tax'] = $subtotal;
		$data['eventdata']['data']['tax'] = $tax_total;
		$data['eventdata']['data']['discount'] = 0;
		$data['eventdata']['data']['total'] = $total;		
		$data['eventdata']['data']['url'] = str_replace('&amp;', '&', $this->url->link('checkout/checkout', '', 'SSL'));
		$data['eventdata']['data']['currency'] = $this->session->data['currency'];		
		
		if ($this->cart->hasProducts()) {
			if (isset($this->session->data['existing_cart'])) {
				$data['event'] = 'cart_updated';
			} else {
				$data['event'] = 'cart_created';
			}

			$this->load->model('catalog/product');
			
			$products = array();
			foreach ($this->cart->getProducts() as $product) {
				
				$product_info = $this->model_catalog_product->getProduct($product['product_id']);
				
				$products[] = array(
					'id' => $product['product_id'],
					'name' => $product['name'],
					'quantity' => $product['quantity'],
					'price' => $product['price'],
					'image' => HTTP_SERVER . 'image/' . $product['image'],
					'url' => str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $product['product_id']))
				);
			}

			$data['eventdata']['data']['products'] = $products;
		} else {
			$data['event'] = 'cart_deleted';
			unset($this->session->data['existing_cart']);
		}

		file_put_contents(DIR_LOGS . 'trackEventAddToCart.txt', print_r($data,1));
		
        $this->curlpost($data, 'trackEvent');
    }

    private function GUID() {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    private static function getCurrentURL() {
        $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === false ? 'http' : 'https';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'];
        $params = $_SERVER['QUERY_STRING'] == '' ? '' : '?' . $_SERVER['QUERY_STRING'];

        return $protocol . '://' . $host . $script . $params;
    }

	
}