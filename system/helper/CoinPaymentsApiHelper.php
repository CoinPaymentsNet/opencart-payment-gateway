<?php

namespace Opencart\System\Helper\Extension\Coinpayments;

use DateTime;
use Exception;
use Opencart\System\Engine\Registry;
use Opencart\System\Library\Url;

class CoinPaymentsApiHelper
{
	const API_URL = 'https://api.coinpayments.net';
	const CHECKOUT_URL = 'https://checkout.coinpayments.net';

	const API_VERSION = '1';

	const API_SIMPLE_INVOICE_ACTION = 'invoices';
	const API_WEBHOOK_ACTION = 'merchant/clients/%s/webhooks';
	const API_MERCHANT_INVOICE_ACTION = 'merchant/invoices';
	const API_CURRENCIES_ACTION = 'currencies';
	const API_CHECKOUT_ACTION = 'checkout';

	const PAID_EVENT = 'Paid';
	const CANCELLED_EVENT = 'Cancelled';

	const WEBHOOK_NOTIFICATION_URL = 'extension/coinpayments/payment/coinpayments.callback';

	/** @var Registry */
	protected $registry;

	/** @var Url */
	private $urlHelper;

	public function __construct(Registry $registry) {
		$this->registry = $registry;
		$this->urlHelper = new Url(defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER);
	}

	public function __get(string $key): object {
		if ($this->registry->has($key)) {
			return $this->registry->get($key);
		} else {
			throw new Exception('Error: Could not call registry key ' . $key . '!');
		}
	}

	public function createWebHook(string $clientId, string $clientSecret, string $event)
	{
		$action = sprintf(self::API_WEBHOOK_ACTION, $clientId);
		$params = [
			"notificationsUrl" => $this->getNotificationUrl($clientId, $event),
			"notifications" => [sprintf("invoice%s", $event)],
		];

		$result = $this->sendRequest('POST', $action, $clientId, $params, $clientSecret);
		if (empty($result) || !isset($result['id'])) {
			return false;
		}

		return true;
	}

	public function getInvoices(string $clientId, string $clientSecret)
	{
		return $this->sendRequest('GET', self::API_MERCHANT_INVOICE_ACTION, $clientId, null, $clientSecret);
	}

	public function createMerchantInvoice(string $clientId, string $clientSecret, array $invoiceParams)
	{
		$params = $this->getInvoicePayload($invoiceParams);

		return $this->sendRequest('POST', self::API_MERCHANT_INVOICE_ACTION, $clientId, $params, $clientSecret);
	}

	private function getInvoicePayload(array $invoiceParams): array
	{
		$payload = array(
			'invoiceId' => $invoiceParams['invoice_id'],
			'amount' => array(
				'currencyId' => $invoiceParams['currency_id'],
				'displayValue' => $invoiceParams['display_value'],
				'value' => $invoiceParams['amount']
			),
			'notesToRecipient' => $invoiceParams['notes_link']
		);

		if (!empty($invoiceParams['billing_data'])) {
			$payload = $this->appendBillingData($payload, $invoiceParams['billing_data']);
		}

		return $this->appendInvoiceMetadata($payload);
	}

	function appendBillingData(array $requestPayload, array $billingData): array
	{
		$requestPayload['buyer'] = array(
			"companyName" => $billingData['payment_company'],
			"name" => array(
				"firstName" => $billingData['firstname'],
				"lastName" => $billingData['lastname'],
			),
			"emailAddress" => $billingData['email'],
			"phoneNumber" => $billingData['telephone'],
		);

		if (preg_match('/^([A-Z]{2})$/', $billingData['payment_iso_code_2'])
			&& !empty($billingData['payment_address_1'])
			&&!empty($billingData['payment_city'])
		) {
			$requestPayload['buyer']['address'] = array(
				'address1' => $billingData['payment_address_1'],
				'provinceOrState' => $billingData['payment_zone'],
				'city' => $billingData['payment_city'],
				'countryCode' => $billingData['payment_iso_code_2'],
				'postalCode' => $billingData['payment_postcode'],
			);
		}

		return $requestPayload;
	}

	public function getCoinCurrency(string $name)
	{
		$items = array();
		$listData = $this->getCoinCurrencies(['q' => $name]);
		if (!empty($listData['items'])) {
			$items = array_filter($listData['items'], function($currency) use ($name){
				return $currency['symbol'] == $name;
			});
		}

		return array_shift($items);
	}

	public function getCoinCurrencies(array $params = [])
	{
		return $this->sendRequest('GET', self::API_CURRENCIES_ACTION, false, $params);
	}

	public function getWebhooksList(string $clientId, string $clientSecret)
	{

		$action = sprintf(self::API_WEBHOOK_ACTION, $clientId);

		return $this->sendRequest('GET', $action, $clientId, null, $clientSecret);
	}

	public function getNotificationUrl(string $clientId, string $event): string
	{
		$params = ['clientId' => $clientId, 'event' => $event];

		return html_entity_decode($this->urlHelper->link(self::WEBHOOK_NOTIFICATION_URL, $params));
	}

	public function encodeSignatureString(string $signature, string $clientSecret): string
	{
		return base64_encode(hash_hmac('sha256', $signature, $clientSecret, true));
	}

	protected function appendInvoiceMetadata(array $requestData)
	{
		$requestData['metadata'] = array(
			"integration" => sprintf("OpenCart_v%s", VERSION),
			"hostname" => defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER,
		);

		return $requestData;
	}

	protected function createSignature(
		string $method,
		string $apiUrl,
		string $clientId,
		DateTime $date,
		string $clientSecret,
		?array $params
	): string {
		$signatureData = array(chr(239).chr(187).chr(191), $method, $apiUrl, $clientId, $date->format('Y-m-d\TH:i:s'));
		if (!empty($params)) {
			$signatureData[] = json_encode($params);
		}

		return $this->encodeSignatureString(implode('', $signatureData), $clientSecret);
	}

	protected function getApiUrl(string $action): string
	{
		return sprintf('%s/api/v%s/%s', self::API_URL, self::API_VERSION, $action);
	}

	protected function sendRequest(
		string $method,
		string $apiAction,
		string $clientId = null,
		array $params = null,
		string $clientSecret = null
	) {
		$response = false;

		$apiUrl = $this->getApiUrl($apiAction);
		$date = new Datetime();
		try {
			$curl = curl_init();
			$options = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYHOST => false,
			);

			$headers = ['Content-Type: application/json'];
			if ($clientSecret) {
				$signature = $this->createSignature($method, $apiUrl, $clientId, $date, $clientSecret, $params);
				$headers[] = 'X-CoinPayments-Client: ' . $clientId;
				$headers[] = 'X-CoinPayments-Timestamp: ' . $date->format('Y-m-d\TH:i:s');
				$headers[] = 'X-CoinPayments-Signature: ' . $signature;
			}

			$options[CURLOPT_HTTPHEADER] = $headers;
			if ($method == 'POST') {
				$options[CURLOPT_POST] = true;
				$options[CURLOPT_POSTFIELDS] = json_encode($params);
			} elseif ($method == 'GET' && !empty($params)) {
				$apiUrl .= '?' . http_build_query($params);
			}

			$options[CURLOPT_URL] = $apiUrl;
			curl_setopt_array($curl, $options);
			$result = curl_exec($curl);
			curl_close($curl);
			$response = json_decode($result, true);
		} catch (Exception $e) {
		}

		return $response;
	}
}
