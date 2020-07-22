<?php
class ModelExtensionModuleSendInBlue extends Model {

	public function syncContacts() {
		$limit = 500; // int | Number of documents per page
		$offset = 0; // int | Index of the first document of the page
		// \DateTime | Filter (urlencoded) the contacts modified after a given UTC date-time (YYYY-MM-DDTHH:mm:ss.SSSZ). Prefer to pass your timezone in date-time format for accurate result.
		//$modifiedSince = new \DateTime(date("Y-m-d\TH:i:sP", strtotime("-12 hours")));
		//exit(print_r($modifiedSince,1));
		//$modifiedSince = '2018-09-24T12:09:06.863-05:00';
		$modifiedSince = date("Y-m-d\TH:i:sP", strtotime("-12 hours"));
		
		$class = 'ContactsApi';
		$mailin = $this->init($class);
		
		try {
			$results = $mailin->getContacts($limit, $offset, $modifiedSince);
			//$this->printr($results);
		} catch (Exception $e) {
			echo 'Exception when calling ContactsApi->getContacts: ', $e->getMessage(), PHP_EOL;
			exit();
		}
		
		// Sync the customer table with details from sendinblue
		$cart_attribute_map = $this->config->get('sendinblue_cart_attribute_map');
		$this->load->model('account/customer');
		$j = 0;

		foreach ($results['contacts'] as $r) {
			//exit(print_r($r,1));
			if (!isset($r['attributes'])) { $r['attributes'] = array(); }
			$attribs = json_decode(json_encode($r['attributes']), true);
			$customer_info = $this->model_account_customer->getCustomerByEmail($r['email']);
			$customer_array = array();
			if ($customer_info) {
				// Set newsletter to false if blacklisted
				if ($r['emailBlacklisted']) {
					$customer_info['newsletter'] = 0;
				} else {
					$customer_info['newsletter'] = 1;
				}
				
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
			}
			//$this->printr($customer_array);
			$sql = "UPDATE " . DB_PREFIX . "customer SET";
			if ($customer_array) {
				foreach ($customer_array as $k => $v) {
					$sql .= " `$k` = '" . $this->db->escape($v) . "',";
				}
			}
			$sql .= " newsletter = '" . (int)$customer_info['newsletter'] . "' WHERE email = '" . $this->db->escape($r['email']) . "'";
			//echo $sql ."<br>";
			$this->db->query($sql);
			$j++;
		}
		
		exit($j . " Contacts have been imported to your cart from SendinBlue");
}
	
    public function editBlacklist($email, $value) {
        //Initialize ContactsApi
        $class = 'ContactsApi';
        $mailin = $this->init($class);
        
        $exists = true;
        try {
            $res = $mailin->getContactInfo($email);
        } catch (Exception $e) {
            //$result = json_decode($e->getResponseBody(),1);
            $exists = false;
            //$this->printr($result);
        }
        //exit(print_r($data,1));
        
		if ($exists) {
		
			if (!$value) {
				$value = true;            
			} else {
				$value = false;
			}
			
			$data = array(
				"emailBlacklisted" => $value,
				"smsBlacklisted" => $value
			);
			        
            $updateContact = new \SendinBlue\Client\Model\UpdateContact($data);
            $result = $mailin->updateContact($email, $updateContact);
        } else {
			$this->addRecord($email);
		}
    }
	
	
	public function moveUserFromDoubleOpt($email) {
		
		//Initialize ContactsApi
		$class = 'ContactsApi';
		$mailin = $this->init($class);
		
		// Removefromlist api doesn't seem to work so going to remove user completely and add back
		// Get Active lists in an integer array
		$active_lists = $this->config->get('sendinblue_active_lists');
		$intArray = array();
		if (!empty($active_lists)) {
			foreach ($active_lists as $value) {
				$intArray[] = intval($value);
			}
		}
		
		$listId = $this->config->get('sendinblue_double_optin_list_id');
		$contactEmails = new \SendinBlue\Client\Model\RemoveContactFromList();
		$contactEmails['emails'] = (array)$email;
        try {
			$result = $mailin->removeContactFromList($listId, $contactEmails);
		} catch (Exception $e) {
			$result = $e->getMessage();
		}
		file_put_contents(DIR_LOGS . 'sendinblue_contact_activity.txt', "\r\nMoving $email out of TEMP - Double Opt list\r\nResult: " . print_r($result,1), FILE_APPEND);
		
		// add to active lists
		$listId = $this->config->get('sendinblue_default_list_id');
		$contactEmails = new \SendinBlue\Client\Model\AddContactToList();
		$contactEmails['emails'] = (array)$email;
		try {
			$result = $mailin->addContactToList($listId, $contactEmails);
		} catch (Exception $e) {
			$result = $e->getMessage();
		}
		file_put_contents(DIR_LOGS . 'sendinblue_contact_activity.txt', "\r\nMoving $email into default list: $listId \r\nResult: " . print_r($result,1), FILE_APPEND);
		
		// Set contact attribute "DOUBLE_OPT-IN" to YES (1)
		$attribs['DOUBLE_OPT-IN'] = 1;
		$data = array(
			"attributes" => $attribs,
		);

		$updateContact = new \SendinBlue\Client\Model\UpdateContact($data);
		//exit(print_r($updateContact,1));
		try {
			$result = $mailin->updateContact($email, $updateContact);
		} catch (Exception $e) {
			$result = $e->getMessage();
		}
		file_put_contents(DIR_LOGS . 'sendinblue_contact_activity.txt', "\r\nSetting $email DOUBLE_OPT-IN attribute to YES (" . $attribs['DOUBLE_OPT-IN'] . ")\r\nResult: " . print_r($result,1), FILE_APPEND);
		
		/*
		try {
			$mailin->deleteContact(urlencode(urldecode($email)));
		} catch (Exception $e) {
			$result = json_decode($e->getResponseBody(),1);
			exit(print_r($result,1) . urlencode((urldecode($email))));
		}		
		$this->addRecord($email);
		*/
	}
    
	public function addRecord($email, $order_id = false, $doubleopt = false) {
		
	    $classname = 'sendinblue';
	    
		$data = array();
		// Add Customer records
		$this->load->model('account/customer');
		$this->load->model('account/address');
		$record = $this->model_account_customer->getCustomerByEmail($email);
		
		if (!$record) { return; }
		//$this->db->query("INSERT INTO " . DB_PREFIX . "sendinblue_data SET email = '" . $this->db->escape($email) . "' ON DUPLICATE KEY UPDATE email=email");
		$data['EMAIL'] = $email;
		//$data['LASTNAME'] = $row['lastname'];
		//$data['FIRSTNAME'] = $row['firstname'];
		//$data[$r]['SMS'] = $row['telephone'];
	
		$address = $this->model_account_address->getAddress($record['address_id']);
		
		if (!empty($address)) {
			$record = array_merge($record, $address);
		}
	
		// Merge in cart attribute maps
		if ($this->config->get('sendinblue_attribute_mapping') && $this->config->get('sendinblue_cart_attribute_map')) {
			foreach ($this->config->get('sendinblue_cart_attribute_map') as $map) {
			    if (empty($map['sib'])) { continue; }
				if (empty($record[$map['field']])) { 
					if (!empty($record[str_replace('payment_', '', $map['field'])])) {
						$record[$map['field']] = $record[str_replace('payment_', '', $map['field'])];
					} else {
						$record[$map['field']] = '';
					}
				}
			    $data[$map['sib']] = $record[$map['field']]; // trim "payment_" prefix for customer address table
			}
		}
		
		// Check newsletter flag for blacklist
		// Have to add the customer first and just blacklist them in case they want to be added later so we can simply remove the blacklist
		$newsletter = $record['newsletter'];
		
		if (!$newsletter) {
            $blacklist = true;            
        } else {
            $blacklist = false;
        }
		
		// Add Order records
		if ($order_id) {
		    $records = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
		} else {
		    $records = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE email = '" . $this->db->escape($email) . "' order by date_added DESC LIMIT 1");
		}
		
		$this->load->model('checkout/order');
		
		//Initialize ContactsApi
		$class = 'ContactsApi';
		$mailin = $this->init($class);
		
		foreach ($records->rows as $r => $row) {

			$order_data = $this->model_checkout_order->getOrder($row['order_id']);
		
			$data['email'] = $order_data['email'];
			$data['ORDER_ID'] = $order_data['order_id'];
			$data['ORDER_PRICE'] = $order_data['total'];
			$data['ORDER_DATE'] =  date("Y-m-d", strtotime($order_data['date_added']));
			
			// Merge in cart attribute maps
			if ($this->config->get('sendinblue_attribute_mapping') && $this->config->get('sendinblue_cart_attribute_map')) {
				foreach ($this->config->get('sendinblue_cart_attribute_map') as $map) {
					if (empty($map['sib'])) { continue; }
					if (empty($order_data[$map['field']])) { $order_data[$map['field']] = ''; }
					$data[$map['sib']] = $order_data[$map['field']];
				}
			}
		}
		
		file_put_contents(DIR_LOGS . 'sendinblue_addRecord.txt', print_r($data,1));	
				
		// Loop through data to manipulate some fields and create the attribs array
		$attribs = array();
		foreach ($data as $k => $v) {
			if ($k == 'SMS') { $v = str_pad($v,10,"0"); }
			if ($k == 'email') { continue; }
			if ($k == 'EMAIL') { continue; }
			$attribs[$k] = $v;
		}
		
		// Get Active lists in an integer array
		$active_lists = $this->config->get('sendinblue_active_lists');
		$intArray = array();
		if (!empty($active_lists)) {
			foreach ($active_lists as $value) {
				$intArray[] = intval($value);
			}
		}
		
		// if doubleoptin flag, override the active lists
		if ($doubleopt) {
			$intArray = array();
			$intArray[] = (int)$this->config->get('sendinblue_double_optin_list_id');
			
			// Set contact attribute "DOUBLE_OPT-IN" to NO (2)
			$attribs['DOUBLE_OPT-IN'] = 2;
		}
				
		// Default $intArray to 0 if no lists to avoid error.
		if (empty($intArray)) {
			$intArray[] = 0;
		}
		
		$data = array(
			"attributes" => $attribs,
			"listIds" => $intArray,
			"emailBlacklisted" => $blacklist,
            "smsBlacklisted" => $blacklist
		);
		
		
		$exists = true;
		try {
			$res = $mailin->getContactInfo($email);
		} catch (Exception $e) {
			$result = json_decode($e->getResponseBody(),1);
			$exists = false;
			//$this->printr($result);
		}
		//exit(print_r($data,1));
		
		if ($exists) {
			$updateContact = new \SendinBlue\Client\Model\UpdateContact($data);
			//exit(print_r($updateContact,1));
			try {
				$result = $mailin->updateContact($email, $updateContact);
			} catch (Exception $e) {
				$result = json_decode($e->getResponseBody(),1);
			}
		} else {
			$data["email"] = $email;
			$createContact = new \SendinBlue\Client\Model\CreateContact($data);		
			try {
				$result = $mailin->createContact($createContact);
			} catch (Exception $e) {
				$result = json_decode($e->getResponseBody(),1);
			}
		}
		
		//exit(print_r($data,1));
		//$this->db->query("INSERT INTO " . DB_PREFIX . "sendinblue_data SET email = '" . $this->db->escape($email) . "' ON DUPLICATE KEY UPDATE email=email");
				
		//return $result['message'];
		//exit(print_r($result,1));
		
		
	}	
	
	
	public function addCustomer($customer_id) {

		$classname = 'sendinblue';	
		$this->load->model('account/customer');
		$customer_info = $this->model_account_customer->getCustomer($customer_id);
		
		$active_lists = $this->config->get($classname . '_active_lists');
		$intArray = array();
		foreach ($active_lists as $value) {
			$intArray[] = intval($value);
		}
		
		$data = array( 
			"attributes" => array("FIRSTNAME" => $firstname, "LASTNAME" => $lastname),
			"listIds" => $intArray
		);
		
		$class = 'ContactsApi';
		$mailin = $this->init($class);
		
		//$contact_info = $mailin->getContactInfo($email);

		$exists = true;
		try {
			$res = $mailin->getContactInfo($email);
		} catch (Exception $e) {
			$result = json_decode($e->getResponseBody(),1);
			$exists = false;
			//$this->printr($result);
		}
		
		if ($exists) {
			$email = $data['email'];
			unset($data['email']);
			$updateContact = new \SendinBlue\Client\Model\UpdateContact($data);
			$result = $mailin->updateContact($email, $updateContact);
		} else {
			$data["email"] = $email;
			$createContact = new \SendinBlue\Client\Model\CreateContact($data);		
			$result = $mailin->createContact($createContact);
		}
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "sendinblue_data SET email = '" . $this->db->escape($email) . "' ON DUPLICATE KEY UPDATE email=email");
		
		//exit(print_r($result,1));		
	}
	
	public function addOrder($order_id) {
		$classname = 'sendinblue';	
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		
		$data = array(
			'email' => $order_info['email'],
			'ORDER_ID' => $order_info['order_id'],
			'ORDER_PRICE' => $order_info['total'],
			'ORDER_DATE' => date("Y-m-d", strtotime($order_info['date_added']))
		);
		
		// Merge in cart attribute maps
		if ($this->config->get('sendinblue_attribute_mapping') && $this->config->get('sendinblue_cart_attribute_map')) {
			foreach ($this->config->get('sendinblue_cart_attribute_map') as $map) {
				if (empty($map['sib'])) { continue; }
				if (empty($order_info[$map['field']])) { $order_info[$map['field']] = ''; }			
				$data[$map['sib']] = $order_info[$map['field']];
			}
		}
		
		//exit(print_r($data,1));
		file_put_contents(DIR_LOGS . 'sendinblue_addOrder.txt', print_r($data,1));
		
		foreach ($data as $k => $v) {
			if ($k == 'SMS') { $v = str_pad($v,10,"0"); }
			if ($k == 'email') { continue; }
			$attribs[$k] = $v;
		}
		
		$email = $order_info['email'];
		
		$active_lists = $this->config->get($classname . '_active_lists');
		$intArray = array();
		if (!empty($active_lists)) {
			foreach ($active_lists as $value) {
				$intArray[] = intval($value);
			}
		}

		// Default $intArray to 0 if no lists to avoid error.
		if (empty($intArray)) {
			$intArray[] = 0;
		}
		
		$data = array( 
			"attributes" => $attribs,
			"listIds" => $intArray
		);
		
		$class = 'ContactsApi';
		$mailin = $this->init($class);
		
		$contact_info = $mailin->getContactInfo($email);

		$exists = true;
		try {
			$res = $mailin->getContactInfo($email);
		} catch (Exception $e) {
			$result = json_decode($e->getResponseBody(),1);
			$exists = false;
			//$this->printr($result);
		}
		//exit(print_r($data,1));
		
		if ($exists) {
			$updateContact = new \SendinBlue\Client\Model\UpdateContact($data);
			$result = $mailin->updateContact($email, $updateContact);
		} else {
			$createContact = new \SendinBlue\Client\Model\CreateContact($data);		
			$result = $mailin->createContact($createContact);
		}
		
		//exit(print_r($data,1));
		//$this->db->query("INSERT INTO " . DB_PREFIX . "sendinblue_data SET email = '" . $this->db->escape($email) . "' ON DUPLICATE KEY UPDATE email=email");
				
		//return $result['message'];
		//exit(print_r($result,1));
	
	}
	
	private function get_account() {
		
		$class = 'AccountApi';
		try {
			$mailin = $this->init($class);
			return json_decode($mailin->getAccount(), true);
		} catch (Exception $e) {
			return json_decode($e->getResponseBody(), true);
		}			
    }
		
	public function notifyCustomer($email, $firstname, $lastname, $template_id) {
		
	    //Get account and check if smtp enabled
		$account = $this->get_account();
		if (!$account['relay']['enabled'] || !$this->config->get('sendinblue_transactionalemails')) {
		    return;
		}
		
		$shop_name = $this->config->get('config_name');
		
		// Get Template
		$html = '';
		$reply_to = 'no-reply@sendinblue.com';
		$from_name = 'no-reply@sendinblue.com';
		$from_email = 'no-reply@sendinblue.com';
		$subject = 'Confirmation Email';		
		
		$class = 'SMTPApi';
		$mailin = $this->init($class);
		$template = $mailin->getSmtpTemplate((int)$template_id);
		
		$doubleopt_callback_url = $this->url->link('extension/module/sendinblue/doubleopt_callback', "email=$email");
		
		if (!isset($template['code'])) {

			$html_content = $template['htmlContent'];
			$reply_to = $template['replyTo'];
			$from_name = $template['sender']['name'];
			$from_email = $template['sender']['email'];
			$subject = $template['subject'];
			//$subject = 'Double Opt-in';
						
			$html_content = str_replace('{title}', $subject, $html_content);
			$html_content = str_replace('https://[DOUBLEOPTIN]', '{subscribe_url}', $html_content);
			$html_content = str_replace('http://[DOUBLEOPTIN]', '{subscribe_url}', $html_content);
			$html_content = str_replace('[DOUBLEOPTIN]', '{subscribe_url}', $html_content);
			$html_content = str_replace('{site_domain}', $shop_name, $html_content);
			$html_content = str_replace('{unsubscribe_url}', $doubleopt_callback_url, $html_content);
			$html_content = str_replace('{subscribe_url}', $doubleopt_callback_url, $html_content);
			$html_content = str_replace('{firstname}', $firstname, $html_content);
			$html_content = str_replace('{lastname}', $lastname, $html_content);
			$html_content = str_replace('{email}', $email, $html_content);
				

			$mail = new Mail('smtp');
			$mail->protocol = 'smtp';
			$mail->parameter = '';
			$mail->smtp_hostname = $account['relay']['data']['relay'];
			$mail->smtp_username = $account['relay']['data']['userName'];
			$mail->smtp_password = html_entity_decode($this->config->get('sendinblue_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $account['relay']['data']['port'];
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
			$mail->setTo($email);
			$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
			$mail->setReplyTo($reply_to);
			$mail->setFrom($from_email);
			$mail->setSender(html_entity_decode($from_name, ENT_QUOTES, 'UTF-8'));
			$mail->setHtml($html_content);
			$mail->setText('sib confirmation');
			file_put_contents(DIR_LOGS . 'sibmail.txt', print_r($mail,1));
			try {
				$mail->send();
			} catch (Exception $e) {
				$this->log->write('SendinBlue SMTP Send Error: ' . $e->getMessage());
			}			
		} else {
			$this->log->write('SendinBlue Notify Error: ' . $template['message']);
		}
	}
			
	private function init($class) {
		$classname = 'sendinblue';
		$this->load->model('setting/setting');
		$settings = $this->model_setting_setting->getSetting($classname);
		//exit(print_r($settings,1));
		
		if (isset($settings[$classname . '_' . 'api_key'])) {
			$api_key = $settings[$classname . '_' . 'api_key'];
		}
		
		require_once(DIR_SYSTEM . 'library/sendinblue/autoload.php');
		try {
			$config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey("api-key", $api_key);
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
	
}