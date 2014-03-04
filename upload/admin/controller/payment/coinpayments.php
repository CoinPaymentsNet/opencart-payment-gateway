<?php
class ControllerPaymentCoinpayments extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/coinpayments');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('coinpayments', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_all_zones'] = $this->language->get('text_all_zones');

		$this->data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
		$this->data['entry_ipn_secret'] = $this->language->get('entry_ipn_secret');
		$this->data['entry_debug_email'] = $this->language->get('entry_debug_email');
		$this->data['entry_total'] = $this->language->get('entry_total');	
		$this->data['entry_cancelled_status'] = $this->language->get('entry_cancelled_status');
		$this->data['entry_completed_status'] = $this->language->get('entry_completed_status');
		$this->data['entry_pending_status'] = $this->language->get('entry_pending_status');
		$this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

 		if (isset($this->error['merchant'])) {
			$this->data['error_merchant'] = $this->error['merchant'];
		} else {
			$this->data['error_merchant'] = '';
		}
 		if (isset($this->error['ipn'])) {
			$this->data['error_ipn'] = $this->error['ipn'];
		} else {
			$this->data['error_ipn'] = '';
		}

		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),      		
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('payment/coinpayments', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

		$this->data['action'] = $this->url->link('payment/coinpayments', 'token=' . $this->session->data['token'], 'SSL');

		$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['coinpayments_merchant_id'])) {
			$this->data['coinpayments_merchant_id'] = $this->request->post['coinpayments_merchant_id'];
		} else {
			$this->data['coinpayments_merchant_id'] = $this->config->get('coinpayments_merchant_id');
		}

		if (isset($this->request->post['coinpayments_ipn_secret'])) {
			$this->data['coinpayments_ipn_secret'] = $this->request->post['coinpayments_ipn_secret'];
		} else {
			$this->data['coinpayments_ipn_secret'] = $this->config->get('coinpayments_ipn_secret');
		}

		if (isset($this->request->post['coinpayments_debug_email'])) {
			$this->data['coinpayments_debug_email'] = $this->request->post['coinpayments_debug_email'];
		} else {
			$this->data['coinpayments_debug_email'] = $this->config->get('coinpayments_debug_email');
		}

		if (isset($this->request->post['coinpayments_total'])) {
			$this->data['coinpayments_total'] = $this->request->post['coinpayments_total'];
		} else {
			$this->data['coinpayments_total'] = $this->config->get('coinpayments_total'); 
		} 

		if (isset($this->request->post['coinpayments_cancelled_status_id'])) {
			$this->data['coinpayments_cancelled_status_id'] = $this->request->post['coinpayments_cancelled_status_id'];
		} else {
			$this->data['coinpayments_cancelled_status_id'] = $this->config->get('coinpayments_cancelled_status_id');
		}
		
		if (isset($this->request->post['coinpayments_completed_status_id'])) {
			$this->data['coinpayments_completed_status_id'] = $this->request->post['coinpayments_completed_status_id'];
		} else {
			$this->data['coinpayments_completed_status_id'] = $this->config->get('coinpayments_completed_status_id');
		}	
										
		if (isset($this->request->post['coinpayments_pending_status_id'])) {
			$this->data['coinpayments_pending_status_id'] = $this->request->post['coinpayments_pending_status_id'];
		} else {
			$this->data['coinpayments_pending_status_id'] = $this->config->get('coinpayments_pending_status_id');
		}

		$this->load->model('localisation/order_status');

		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['coinpayments_geo_zone_id'])) {
			$this->data['coinpayments_geo_zone_id'] = $this->request->post['coinpayments_geo_zone_id'];
		} else {
			$this->data['coinpayments_geo_zone_id'] = $this->config->get('coinpayments_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['coinpayments_status'])) {
			$this->data['coinpayments_status'] = $this->request->post['coinpayments_status'];
		} else {
			$this->data['coinpayments_status'] = $this->config->get('coinpayments_status');
		}
		
		if (isset($this->request->post['coinpayments_sort_order'])) {
			$this->data['coinpayments_sort_order'] = $this->request->post['coinpayments_sort_order'];
		} else {
			$this->data['coinpayments_sort_order'] = $this->config->get('coinpayments_sort_order');
		}

		$this->template = 'payment/coinpayments.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'payment/coinpayments')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['coinpayments_merchant_id']) {
			$this->error['merchant'] = $this->language->get('error_merchant');
		}
		if (!$this->request->post['coinpayments_ipn_secret']) {
			$this->error['ipn'] = $this->language->get('error_ipn');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
}
?>