<?php

namespace Opencart\Catalog\Controller\Extension\Coinpayments\Payment;
use Opencart\System\Engine\Controller;
use Opencart\System\Helper\Extension\Coinpayments\CoinPaymentsApiHelper;

/**
 * CoinPayments Payment Controller
 */
class Coinpayments extends Controller
{
	public function __construct($registry)
	{
		parent::__construct($registry);

		$this->load->language('extension/coinpayments/payment/coinpayments');
	}

	public function index(): string
	{
		$data['language'] = $this->config->get('config_language');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		if (false === $order_info) {
			$this->response->redirect($this->url->link('checkout/cart'));
		}

		$this->load->model('extension/coinpayments/payment/coinpayments');

		$invoice = $this->model_extension_coinpayments_payment_coinpayments->createInvoice($order_info);

		if (isset($invoice['id'])) {
			$data['form_params'] = array(
				'invoice-id' => $invoice['id'],
				'success-url' => $this->url->link('checkout/success'),
				'cancel-url' => $this->url->link('checkout/checkout', '', 'SSL'),
			);
		} else {
			$data['error_coinpayments'] = $this->language->get('error_create_invoice');
		}

		$data['action'] = sprintf('%s/%s/', CoinPaymentsApiHelper::CHECKOUT_URL, CoinPaymentsApiHelper::API_CHECKOUT_ACTION);

		return $this->load->view('extension/coinpayments/payment/coinpayments', $data);
	}

	/**
	 * @return void
	 */
	public function confirm(): void
	{
		$json = [];
		if (!isset($this->session->data['order_id'])) {
			$json['error'] = $this->language->get('error_order');
		}

		if (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] != 'coinpayments.coinpayments') {
			$json['error'] = $this->language->get('error_payment_method');
		}

		if (!$json) {
			$this->load->model('checkout/order');
			$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_coinpayments_pending_status'));
			$json['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function callback()
	{
		$content = file_get_contents('php://input');

		if ($this->config->get('payment_coinpayments_webhooks') && !empty($this->request->server['HTTP_X_COINPAYMENTS_SIGNATURE'])) {

			$signature = $this->request->server['HTTP_X_COINPAYMENTS_SIGNATURE'];
			$request_data = json_decode($content, true);
			$this->load->model('extension/coinpayments/payment/coinpayments');

			if ($this->model_extension_coinpayments_payment_coinpayments->checkDataSignature($signature, $content, $request_data['invoice']['state']) && isset($request_data['invoice']['invoice_id'])) {
				$invoice_str = $request_data['invoice']['invoice_id'];
				$invoice_str = explode('|', $invoice_str);
				$host_hash = array_shift($invoice_str);
				$invoice_id = array_shift($invoice_str);

				if ($host_hash == md5(defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER) && $invoice_id) {
					$this->load->model('checkout/order');
					$order_info = $this->model_checkout_order->getOrder($invoice_id);
					if ($order_info) {
						$status = $request_data['invoice']['state'];
						if ($status == CoinPaymentsApiHelper::PAID_EVENT) {
							if (!$order_info['order_status_id'] || $order_info['order_status_id'] != $this->config->get('payment_coinpayments_completed_status')) {
								$this->model_checkout_order->addHistory($order_info['order_id'], $this->config->get('payment_coinpayments_completed_status'), 'Status: ' . $status);
							}
						} elseif ($status == CoinPaymentsApiHelper::CANCELLED_EVENT) {
							if (!$order_info['order_status_id'] || $order_info['order_status_id'] != $this->config->get('payment_coinpayments_cancelled_status')) {
								$this->model_checkout_order->addHistory($order_info['order_id'], $this->config->get('payment_coinpayments_cancelled_status'), 'Status: ' . $status);
							}
						}
					}
				}
			}
		}

	}
}
