<?php

/**
 * Class Coinpayments
 */
class Coinpayments
{

    const API_URL = 'https://alpha-api.coinpayments.net';
    const API_VERSION = '1';

    const API_SIMPLE_INVOICE_ACTION = 'invoices';
    const API_WEBHOOK_ACTION = 'merchant/clients/%s/webhooks';
    const API_MERCHANT_INVOICE_ACTION = 'merchant/invoices';
    const API_CURRENCIES_ACTION = 'currencies';
    const API_CHECKOUT_ACTION = 'checkout';
    const FIAT_TYPE = 'fiat';

    const WEBHOOK_NOTIFICATION_URL = 'payment/coinpayments/callback';

    /** @var Registry $registry */
    protected $registry;

    /**
     * Coinpayments constructor.
     * @param $registry
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
    }

    /**
     * Magic getter for Registry items
     *
     * Allows use of $this->db instead of $this->registry->get('db') for example
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->registry->get($name);
    }

    /**
     * @param $client_id
     * @param $client_secret
     * @param $notification_url
     * @return bool|mixed
     * @throws Exception
     */
    public function createWebHook($client_id, $client_secret, $notification_url)
    {

        $action = sprintf(self::API_WEBHOOK_ACTION, $client_id);

        $params = array(
            "notificationsUrl" => $notification_url,
            "notifications" => [
                "invoiceCreated",
                "invoicePending",
                "invoicePaid",
                "invoiceCompleted",
                "invoiceCancelled",
            ],
        );

        return $this->sendRequest('POST', $action, $client_id, $params, $client_secret);
    }

    /**
     * @param $client_id
     * @param int $currency_id
     * @param string $invoice_id
     * @param int $amount
     * @param string $display_value
     * @return bool|mixed
     * @throws Exception
     */
    public function createSimpleInvoice($client_id, $currency_id = 5057, $invoice_id = 'Validate invoice', $amount = 1, $display_value = '0.01')
    {

        $action = self::API_SIMPLE_INVOICE_ACTION;

        $params = array(
            'clientId' => $client_id,
            'invoiceId' => $invoice_id,
            'amount' => [
                'currencyId' => $currency_id,
                "displayValue" => $display_value,
                'value' => $amount
            ],
        );

        $params = $this->appendInvoiceMetadata($params);
        return $this->sendRequest('POST', $action, $client_id, $params);
    }

    /**
     * @param $client_id
     * @param $client_secret
     * @param $currency_id
     * @param $invoice_id
     * @param $amount
     * @param $display_value
     * @return bool|mixed
     * @throws Exception
     */
    public function createMerchantInvoice($client_id, $client_secret, $currency_id, $invoice_id, $amount, $display_value)
    {

        $action = self::API_MERCHANT_INVOICE_ACTION;

        $params = array(
            "invoiceId" => $invoice_id,
            "amount" => [
                "currencyId" => $currency_id,
                "displayValue" => $display_value,
                "value" => $amount
            ],
        );

        $params = $this->appendInvoiceMetadata($params);
        return $this->sendRequest('POST', $action, $client_id, $params, $client_secret);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function getCoinCurrency(string $name)
    {

        $params = array(
            'types' => self::FIAT_TYPE,
            'q' => $name,
        );
        $items = array();

        $listData = $this->getCoinCurrencies($params);
        if (!empty($listData['items'])) {
            $items = $listData['items'];
        }

        return array_shift($items);
    }

    /**
     * @param array $params
     * @return bool|mixed
     * @throws Exception
     */
    public function getCoinCurrencies($params = array())
    {
        return $this->sendRequest('GET', self::API_CURRENCIES_ACTION, false, $params);
    }

    /**
     * @param $client_id
     * @param $client_secret
     * @return bool|mixed
     * @throws Exception
     */
    public function getWebhooksList($client_id, $client_secret)
    {

        $action = sprintf(self::API_WEBHOOK_ACTION, $client_id);

        return $this->sendRequest('GET', $action, $client_id, null, $client_secret);
    }

    /**
     * @return string
     */
    public function getNotificationUrl()
    {

        if (defined('HTTP_CATALOG')) {
            $url = new Url(HTTP_CATALOG, $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG);
        } else {
            $url = new Url(HTTP_SERVER, $this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER);
        }

        return $url->link(self::WEBHOOK_NOTIFICATION_URL);
    }

    /**
     * @param $signature_string
     * @param $client_secret
     * @return string
     */
    public function encodeSignatureString($signature_string, $client_secret)
    {
        return base64_encode(hash_hmac('sha256', $signature_string, $client_secret, true));
    }

    /**
     * @param $request_data
     * @return mixed
     */
    protected function appendInvoiceMetadata($request_data)
    {
        if (defined('HTTP_CATALOG')) {
            $hostname = $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG;
        } else {
            $hostname = $this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER;
        }

        $request_data['metadata'] = array(
            "integration" => sprintf("OpenCart_v%s", VERSION),
            "hostname" => $hostname,
        );

        return $request_data;
    }

    /**
     * @param $method
     * @param $api_url
     * @param $client_id
     * @param $date
     * @param $client_secret
     * @param $params
     * @return string
     */
    protected function createSignature($method, $api_url, $client_id, $date, $client_secret, $params)
    {

        if (!empty(json_encode($params))) {
            $params = json_encode($params);
        }

        $signature_data = array(
            chr(239),
            chr(187),
            chr(191),
            $method,
            $api_url,
            $client_id,
            $date->format('c'),
            $params
        );

        $signature_string = implode('', $signature_data);

        return $this->encodeSignatureString($signature_string, $client_secret);
    }

    /**
     * @param $action
     * @return string
     */
    protected function getApiUrl($action)
    {
        return sprintf('%s/api/v%s/%s', self::API_URL, self::API_VERSION, $action);
    }

    /**
     * @param $method
     * @param $api_action
     * @param $client_id
     * @param null $params
     * @param null $client_secret
     * @return bool|mixed
     * @throws Exception
     */
    protected function sendRequest($method, $api_action, $client_id, $params = null, $client_secret = null)
    {

        $response = false;

        $api_url = $this->getApiUrl($api_action);
        $date = new \Datetime();
        try {

            $curl = curl_init();

            $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
            );

            $headers = array(
                'Content-Type: application/json',
            );

            if ($client_secret) {
                $signature = $this->createSignature($method, $api_url, $client_id, $date, $client_secret, $params);
                $headers[] = 'X-CoinPayments-Client: ' . $client_id;
                $headers[] = 'X-CoinPayments-Timestamp: ' . $date->format('c');
                $headers[] = 'X-CoinPayments-Signature: ' . $signature;

            }

            $options[CURLOPT_HTTPHEADER] = $headers;

            if ($method == 'POST') {
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = json_encode($params);
            } elseif ($method == 'GET' && !empty($params)) {
                $api_url .= '?' . http_build_query($params);
            }

            $options[CURLOPT_URL] = $api_url;

            curl_setopt_array($curl, $options);

            $response = json_decode(curl_exec($curl), true);

            curl_close($curl);

        } catch (Exception $e) {

        }
        return $response;
    }

}
