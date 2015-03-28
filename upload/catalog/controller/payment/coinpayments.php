<?php
class ControllerPaymentCoinpayments extends Controller {
	
	private function cps_get_currency_code($code) {
		if ($code == 'DOG') { return 'DOGE'; }
		if ($code == 'MNT') { return 'MINT'; }
		if ($code == 'NBL') { return 'NOBL'; }
		if ($code == 'DAS') { return 'DASH'; }
		if ($code == 'CLK') { return 'CLOAK'; }
		if ($code == 'HTM') { return 'HTML5'; }
		if ($code == 'HYP') { return 'HYPER'; }
		if ($code == 'OPL') { return 'OPAL'; }
		if ($code == 'SRT') { return 'START'; }
		if ($code == 'ZEI') { return 'ZEIT'; }
		return $code;
	}
	
	protected function index() {
		$this->language->load('payment/coinpayments');
    	
		$this->data['button_confirm'] = $this->language->get('button_confirm');

 		$this->data['action'] = 'https://www.coinpayments.net/index.php';

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		if ($order_info) {
			$this->data['merchant'] = $this->config->get('coinpayments_merchant_id');
			$this->data['item_name'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');				
					
			//$subtotal = $this->currency->format($this->cart->getSubTotal(), $order_info['currency_code'], false, false);
			//$shipping = $this->currency->format($order_info['total'] - $this->cart->getSubTotal(), $order_info['currency_code'], false, false);
			
			//there doesn't seem to be an easy way to separate the shipping and tax into individual values, so we don't bother.
			//if we combined them into one of the tax or shipping fields people might get confused and say the shipping cost is wrong
			$total = $this->currency->format($order_info['total'], $order_info['currency_code'], false, false);
			
			$this->data['amount'] = $total;
			$this->data['shipping'] = 0.00;
			$this->data['tax'] = 0.00;
			//OpenCart has already collected the shipping info, no need for us to do it as well
			$this->data['want_shipping'] = 0;
			$this->data['currency_code'] = $this->cps_get_currency_code($order_info['currency_code']);
			$this->data['first_name'] = html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8');	
			$this->data['last_name'] = html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');	
			$this->data['address1'] = html_entity_decode($order_info['payment_address_1'], ENT_QUOTES, 'UTF-8');	
			$this->data['address2'] = html_entity_decode($order_info['payment_address_2'], ENT_QUOTES, 'UTF-8');	
			$this->data['city'] = html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8');	
			$this->data['state'] = html_entity_decode($order_info['shipping_zone_code'], ENT_QUOTES, 'UTF-8');	
			$this->data['zip'] = html_entity_decode($order_info['payment_postcode'], ENT_QUOTES, 'UTF-8');	
			$this->data['phone'] = html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');				
			$this->data['country'] = $order_info['payment_iso_code_2'];
			$this->data['email'] = $order_info['email'];
			$this->data['invoice'] = $this->session->data['order_id'] . ' - ' . html_entity_decode($order_info['payment_firstname'], ENT_QUOTES, 'UTF-8') . ' ' . html_entity_decode($order_info['payment_lastname'], ENT_QUOTES, 'UTF-8');
			$this->data['success_url'] = $this->url->link('checkout/success');
			$this->data['ipn_url'] = $this->url->link('payment/coinpayments/callback', '', 'SSL');
			$this->data['cancel_url'] = $this->url->link('checkout/checkout', '', 'SSL');
			
			$this->data['custom'] = $this->session->data['order_id'];
		
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/coinpayments.tpl')) {
				$this->template = $this->config->get('config_template') . '/template/payment/coinpayments.tpl';
			} else {
				$this->template = 'default/template/payment/coinpayments.tpl';
			}
	
			$this->render();
		}
	}
	
	public function callback() {
		$report = true;
		$error_msg = "";
		
		$auth_ok = false;
		$ipn_mode = isset($_POST['ipn_mode']) ? $_POST['ipn_mode']:'';
		if ($ipn_mode == 'httpauth') {
			if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && trim($_SERVER['PHP_AUTH_USER']) == trim($this->config->get('coinpayments_merchant_id')) && trim($_SERVER['PHP_AUTH_PW']) == trim($this->config->get('coinpayments_ipn_secret'))) {
				$auth_ok = true;
			} else {
				$error_msg = 'Invalid AUTH User/Pass (Merchant ID/IPN Secret)';
			}
		} else if ($ipn_mode == 'hmac') {
			if (isset($_SERVER['HTTP_HMAC']) && !empty($_SERVER['HTTP_HMAC'])) {
				$request = file_get_contents('php://input');
				if ($request !== FALSE && !empty($request)) {
					$hmac = hash_hmac("sha512", $request, trim($this->config->get('coinpayments_ipn_secret')));
					if ($hmac == $_SERVER['HTTP_HMAC']) {
						$auth_ok = true;
					} else {
						$error_msg = 'HMAC signature does not match';
					}
				} else {
					$error_msg = 'Error reading POST data';
				}
			} else {
				$error_msg = 'No HMAC signature sent.';
			}
		} else {
			$error_msg = 'Unknown IPN Verification Method';
		}
		
		if ($auth_ok) {
			if (isset($this->request->post['custom'])) {
				$order_id = $this->request->post['custom'];
			} else {
				$order_id = 0;
			}		
			
			$this->load->model('checkout/order');
					
			$order_info = $this->model_checkout_order->getOrder($order_id);
			
			if ($order_info) {								
				if ($this->request->post['ipn_type'] == "button") {
					if ($this->request->post['merchant'] == trim($this->config->get('coinpayments_merchant_id'))) {
						if ($this->request->post['currency1'] == $this->cps_get_currency_code($order_info['currency_code'])) {
							if ($this->request->post['amount1'] >= $this->currency->format($order_info['total'], $order_info['currency_code'], false, false)) {
								$report = false;
								$status = (int)$this->request->post['status'];
								if ($status >= 0 && $status < 100) {
									$order_status_id = $this->config->get('coinpayments_pending_status_id');
								} else if ($status < 0) {
									$order_status_id = $this->config->get('coinpayments_cancelled_status_id');
								} else {
									$order_status_id = $this->config->get('coinpayments_completed_status_id');
								}
								if (!$order_info['order_status_id']) {
									//always confirm/update for the first IPN
									$this->log->write('CoinPayments.net :: IPN : Confirming order '.$order_id.' with status: '.$order_status_id.' (ipn status code: '.$status.')');
									$this->model_checkout_order->confirm($order_id, $order_status_id);
								} else if ($order_status_id == $this->config->get('coinpayments_cancelled_status_id') || $order_status_id == $this->config->get('coinpayments_completed_status_id')) {
									$this->log->write('CoinPayments.net :: IPN : Updating order '.$order_id.' with status: '.$order_status_id.' (ipn status code: '.$status.')');
									$this->model_checkout_order->update($order_id, $order_status_id);
								}
							} else {
								$error_msg = "Amount received is less than the total!";
							}
						} else {
							$error_msg = "Original currency doesn't match!";
						}
					} else {
						$error_msg = "Merchant ID doesn't match!";
					}
				} else {
					$error_msg = "ipn_type != button";
				}
			} else {
				$error_msg = "Could not find order info for order: ".$order_id;
			}
		}
		
		if ($report) {
			$report = "AUTH User: ".$_SERVER['PHP_AUTH_USER']."\n";
			$report .= "AUTH Pass: ".$_SERVER['PHP_AUTH_PW']."\n\n";
			
			if (!empty($error_msg)) {
				$report .= "Error Message: ".$error_msg."\n\n";
			}
			
			$report .= "POST Fields\n\n";
			foreach ($this->request->post as $key => $value) {
				$report .= $key . '=' . html_entity_decode($value, ENT_QUOTES, 'UTF-8'). "\n";
			}
			
			$this->log->write('CoinPayments.net :: IPN : ' . $report);
			if ($this->config->get('coinpayments_debug_email')) { @mail($this->config->get('coinpayments_debug_email'), "CoinPayments.net Invalid IPN", $report); }
			die('IPN Error: '.$error_msg);
		} else {
			die('IPN OK');
		}
	}
}
?>