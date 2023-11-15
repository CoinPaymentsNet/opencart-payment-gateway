<?php

namespace Opencart\Admin\Controller\Extension\Coinpayments\Payment;

use Opencart\System\Engine\Registry;

class Coinpayments extends \Opencart\System\Engine\Controller
{
	/** @var array $error Validation errors */
	private $error = array();

	/**
	 * CoinPayments Payment Admin Controller Constructor
	 * @param Registry $registry
	 */
	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->load->language('extension/coinpayments/payment/coinpayments');
		if (!empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->response->addHeader('Content-type: application/json');
		}
	}

	/**
	 * Primary settings page
	 * @return void
	 */
	public function index()
	{
		$this->load->model('setting/setting');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = $this->getBreadcrumbs();

		$data['save'] = $this->url->link('extension/coinpayments/payment/coinpayments.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

		$data['payment_coinpayments_client_id'] = $this->config->get('payment_coinpayments_client_id');
		$data['payment_coinpayments_client_secret'] = $this->config->get('payment_coinpayments_client_secret');
		$data['payment_coinpayments_webhooks'] = $this->config->get('payment_coinpayments_webhooks');

		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		$data['payment_coinpayments_geo_zone_id'] = $this->config->get('payment_coinpayments_geo_zone_id');
		$data['payment_coinpayments_sort_order'] = $this->config->get('payment_coinpayments_sort_order');
		$data['payment_coinpayments_status'] = $this->config->get('payment_coinpayments_status');

		// #ORDER STATUSES
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$data['payment_coinpayments_cancelled_status'] = $this->config->get('payment_coinpayments_cancelled_status');
		$data['payment_coinpayments_completed_status'] = $this->config->get('payment_coinpayments_completed_status');
		$data['payment_coinpayments_pending_status'] = $this->config->get('payment_coinpayments_pending_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/coinpayments/payment/coinpayments', $data));
	}

	private function getBreadcrumbs(): array
	{
		$breadcrumbsData = [
			['text' => 'text_home', 'route' => 'common/dashboard'],
			['text' => 'text_extension', 'route' => 'marketplace/extension', 'params' => ['type' => 'payment']],
			['text' => 'heading_title', 'route' => 'extension/coinpayments/payment/coinpayments'],
		];

		$defaultUrlParams = ['user_token' => $this->session->data['user_token']];
		$breadcrumbs = [];
		foreach ($breadcrumbsData as $data) {
			$breadcrumbs[] = [
				'text' => $this->language->get($data['text']),
				'href' => $this->url->link($data['route'], array_merge($defaultUrlParams, $data['params'] ?? []))
			];
		}

		return $breadcrumbs;
	}

	/**
	 * Install the extension by setting up some smart defaults
	 * @return void
	 */
	public function install()
	{
		$this->load->model('localisation/order_status');
		$orderStatusesData = $this->model_localisation_order_status->getOrderStatuses();
		$orderStatusesIds = array_column($orderStatusesData, 'order_status_id');
		$orderStatusesNames = array_column($orderStatusesData, 'name');
		$orderStatuses = array_combine($orderStatusesNames, $orderStatusesIds);

		$this->load->model('setting/setting');
		$default_settings = array(
			'payment_coinpayments_client_id' => '',
			'payment_coinpayments_client_secret' => '',
			'payment_coinpayments_webhooks' => 0,
			'payment_coinpayments_cancelled_status' => $orderStatuses['Canceled'] ?? 0,
			'payment_coinpayments_completed_status' => $orderStatuses['Complete'] ?? 0,
			'payment_coinpayments_pending_status' => $orderStatuses['Pending'] ?? 0,
			'payment_coinpayments_geo_zone_id' => '0',
			'payment_coinpayments_sort_order' => '',
		);
		$this->model_setting_setting->editSetting('payment_coinpayments', $default_settings);
	}

	/**
	 * Uninstall the extension by removing the settings
	 * @return void
	 */
	public function uninstall()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('payment_coinpayments');
	}

	public function save(): void
	{
		$this->response->addHeader('Content-Type: application/json');

		$json = [];
		// Validate the primary settings for the CoinPayments extension
		if (!$this->user->hasPermission('modify', 'extension/coinpayments/payment/coinpayments')) {
			$json['error'] = $this->language->get('warning_permission');
			$this->response->setOutput(json_encode($json));
			return;
		}

		$postedData = $this->request->post;
		$clientId = $postedData['payment_coinpayments_client_id'] ?? null;
		$isWebhooksEnabled = (bool)($postedData['payment_coinpayments_webhooks'] ?? false);
		$clientSecret = $postedData['payment_coinpayments_client_secret'] ?? null;
		if (empty($clientId)) {
			$json['error']['client_id'] = $this->language->get('error_client_id');
		}


		if ($isWebhooksEnabled && empty($clientSecret)) {
			$json['error']['client_secret'] = $this->language->get('error_client_secret');
		}

		if (!empty($json)) {
			$this->response->setOutput(json_encode($json));
			return;
		}

		$this->load->model('extension/coinpayments/payment/coinpayments');
		if (!$isWebhooksEnabled && !$this->model_extension_coinpayments_payment_coinpayments->validateInvoice($clientId, $clientSecret)) {
			$json['error']['client_id'] = $this->language->get('error_invalid_credentials');
			$json['error']['client_secret'] = $this->language->get('error_invalid_credentials');
		} elseif ($isWebhooksEnabled && !$this->model_extension_coinpayments_payment_coinpayments->validateWebhook($clientId, $clientSecret)) {
			$json['error']['webhooks'] = $this->language->get('error_could_not_setup_webhooks');
		}

		if (!empty($json)) {
			$json['error']['warning'] = $this->language->get('text_warning');
			$this->response->setOutput(json_encode($json));
			return;
		}

		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('payment_coinpayments', $postedData);
		$json['success'] = $this->language->get('text_success');
		$this->response->setOutput(json_encode($json));
	}
}
