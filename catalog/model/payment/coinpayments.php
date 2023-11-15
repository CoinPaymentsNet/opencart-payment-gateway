<?php

namespace Opencart\Catalog\Model\Extension\Coinpayments\Payment;

use Opencart\System\Engine\Model;
use Opencart\System\Engine\Registry;
use Opencart\System\Helper\Extension\Coinpayments\CoinPaymentsApiHelper;

class Coinpayments extends Model {
	/** @var CoinPaymentsApiHelper $apiHelper */
	private $apiHelper;

	public function __construct(Registry $registry) {
		parent::__construct($registry);

		$this->load->language('extension/coinpayments/payment/coinpayments');
		$this->load->helper('extension/coinpayments/CoinPaymentsApiHelper');
		$this->apiHelper = new CoinPaymentsApiHelper($registry);
	}


	public function getMethod(array $address = []): array
	{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_coinpayments_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

		// All Geo Zones configured or address is in configured Geo Zone
		if (!$this->config->get('payment_coinpayments_geo_zone_id') || $query->num_rows) {
			return [
				'code' => 'coinpayments',
				'title' => $this->language->get('text_title'),
				'sort_order' => $this->config->get('payment_coinpayments_sort_order'),
			];
		}

		return [];
	}

	public function getMethods(array $address = []): array
	{
		if ($this->cart->hasSubscription()) {
			$status = false;
		} elseif (!$this->config->get('config_checkout_payment_address')) {
			$status = true;
		} elseif (!$this->config->get('payment_coinpayments_geo_zone_id')) {
			$status = true;
		} else {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_coinpayments_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");
			if ($query->num_rows) {
				$status = true;
			} else {
				$status = false;
			}
		}

		$method_data = [];

		if ($status) {
			$option_data['coinpayments'] = [
				'code' => 'coinpayments.coinpayments',
				'name' => $this->language->get('text_title')
			];

			$method_data = [
				'code'       => 'coinpayments',
				'name'       => $this->language->get('text_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_coinpayments_sort_order')
			];
		}

		return $method_data;
	}

	public function createInvoice($order_info)
	{
		$client_id = $this->config->get('payment_coinpayments_client_id');
		$client_secret = $this->config->get('payment_coinpayments_client_secret');
		$invoice_id = sprintf('%s|%s', md5(defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER), $order_info['order_id']);

		$currency_code = $order_info['currency_code'];
		$coin_currency = $this->apiHelper->getCoinCurrency($currency_code);

		$order_info['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], 0, false);
		$amount = number_format($order_info['total'], $coin_currency['decimalPlaces'], '', '');;
		$display_value = $order_info['total'];

		$invoice_params = array(
			'invoice_id' => $invoice_id,
			'currency_id' => $coin_currency['id'],
			'amount' => $amount,
			'display_value' => $display_value,
			'billing_data' => $order_info,
			'notes_link' => sprintf(
				"%s|Store name: %s|Order #%s",
				html_entity_decode($this->url->link('sale/order/info', 'order_id=' . $order_info['order_id'], true)),
				$this->config->get('config_name'),
				$order_info['order_id']),
		);

		$resp = $this->apiHelper->createMerchantInvoice($client_id, $client_secret, $invoice_params);
		$invoice = array_shift($resp['invoices']);

		return $invoice;
	}

	public function checkDataSignature($signature, $content, $event)
	{
		$request_url = $this->apiHelper->getNotificationUrl($this->config->get('payment_coinpayments_client_id'), $event);
		$client_secret = $this->config->get('payment_coinpayments_client_secret');
		$signature_string = sprintf('%s%s', $request_url, $content);
		$encoded_pure = $this->apiHelper->encodeSignatureString($signature_string, $client_secret);

		return $signature == $encoded_pure;
	}
}
