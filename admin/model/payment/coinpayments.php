<?php

namespace Opencart\Admin\Model\Extension\Coinpayments\Payment;

use Opencart\System\Engine\Registry;
use Opencart\System\Helper\Extension\Coinpayments\CoinPaymentsApiHelper;

/**
 * CoinPayments Payment Model
 */
class Coinpayments extends \Opencart\System\Engine\Model
{
	/** @var CoinPaymentsApiHelper $apiHelper */
	private $apiHelper;

	/**
	 * CoinPayments Payment Model Construct
	 * @param Registry $registry
	 */
	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->load->helper('extension/coinpayments/CoinPaymentsApiHelper');
		$this->apiHelper = new CoinPaymentsApiHelper($registry);
	}

	/**
	 * Returns the CoinPayments Payment Method if available
	 * @param array $address Customer billing address
	 * @return array|void CoinPayments Payment Method if available
	 */
	public function getMethod($address)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payment_coinpayments_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		// All Geo Zones configured or address is in configured Geo Zone
		if (!$this->config->get('payment_coinpayments_geo_zone_id') || $query->num_rows) {
			return array(
				'code' => 'payment_coinpayments',
				'title' => $this->language->get('text_title'),
				'terms' => '',
				'sort_order' => $this->config->get('payment_coinpayments_sort_order'),
			);
		}
	}

	public function validateInvoice(string $clientId, string $clientSecret)
	{
		$invoices = $this->apiHelper->getInvoices($clientId, $clientSecret);

		return is_array($invoices) && isset($invoices['items']);
	}

	public function validateWebhook($clientId, $clientSecret): bool
	{
		$webhooksList = $this->apiHelper->getWebhooksList($clientId, $clientSecret);
		if (empty($webhooksList)) {
			return false;
		}

		$webhooksUrlsList = [];
		if (!empty($webhooksList['items'])) {
			$webhooksUrlsList = array_column($webhooksList['items'], 'notificationsUrl');
		}

		$paidUrl = $this->apiHelper->getNotificationUrl($clientId, CoinPaymentsApiHelper::PAID_EVENT);
		if (!in_array($paidUrl, $webhooksUrlsList)) {
			if (!$this->apiHelper->createWebHook($clientId, $clientSecret, CoinPaymentsApiHelper::PAID_EVENT)) {
				return false;
			}
		}

		$canceledUrl = $this->apiHelper->getNotificationUrl($clientId, CoinPaymentsApiHelper::CANCELLED_EVENT);
		if (!in_array($canceledUrl, $webhooksUrlsList)) {
			if (!$this->apiHelper->createWebHook($clientId, $clientSecret, CoinPaymentsApiHelper::CANCELLED_EVENT)) {
				return false;
			}
		}

		return true;
	}
}
