<?php

/**
 * CoinPayments Payment Model
 */
class ModelPaymentCoinpayments extends Model
{

    /** @var CoinPaymentsLibrary $coinpayments */
    private $coinpayments;

    /**
     * CoinPayments Payment Model Construct
     * @param Registry $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('payment/coinpayments');
        $this->load->library('coinpayments');
        $this->coinpayments = new Coinpayments($registry);
    }

    /**
     * Returns the CoinPayments Payment Method if available
     * @param array $address Customer billing address
     * @return array|void CoinPayments Payment Method if available
     */
    public function getMethod($address)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('coinpayments_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        // All Geo Zones configured or address is in configured Geo Zone
        if (!$this->config->get('coinpayments_geo_zone_id') || $query->num_rows) {
            return array(
                'code' => 'coinpayments',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('coinpayments_sort_order'),
            );
        }
    }

    /**
     * @param $order_info
     * @return bool|mixed|null
     * @throws Exception
     */
    public function createInvoice($order_info)
    {
        $invoice = null;


        $client_id = $this->config->get('coinpayments_client_id');
        $client_secret = $this->config->get('coinpayments_client_secret');
        $invoice_id = sprintf('%s|%s', md5($this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER), $order_info['order_id']);

        $currency_code = $order_info['currency_code'];
        $coin_currency = $this->coinpayments->getCoinCurrency($currency_code);

        $amount = number_format($order_info['total'], $coin_currency['decimalPlaces'], '', '');;
        $display_value = $order_info['total'];

        $hostname = $this->coinpayments->getShopHostname();
        $admin_catalog = 'admin/';
        $url = new Url((defined('HTTP_CATALOG') ? HTTP_CATALOG : HTTP_SERVER) . $admin_catalog, $hostname . $admin_catalog);

        $invoice_params = array(
            'invoice_id' => $invoice_id,
            'currency_id' => $coin_currency['id'],
            'amount' => $amount,
            'display_value' => $display_value,
            'billing_data' => $order_info,
            'notes_link' => sprintf(
                "%s|Store name: %s|Order #%s",
                html_entity_decode($url->link('sale/order/info', 'order_id=' . $order_info['order_id'], true)),
                $this->config->get('config_name'),
                $order_info['order_id']),
            );

        if ($this->config->get('coinpayments_webhooks')) {
            $resp = $this->coinpayments->createMerchantInvoice($client_id, $client_secret, $invoice_params);
            $invoice = array_shift($resp['invoices']);
        } else {
            $invoice = $this->coinpayments->createSimpleInvoice($client_id, $invoice_params);
        }

        return $invoice;
    }

    public function checkDataSignature($signature, $content, $event)
    {

        $request_url = $this->coinpayments->getNotificationUrl($this->config->get('coinpayments_client_id'),$event);
        $client_secret = $this->config->get('coinpayments_client_secret');
        $signature_string = sprintf('%s%s', $request_url, $content);
        $encoded_pure = $this->coinpayments->encodeSignatureString($signature_string, $client_secret);
        return $signature == $encoded_pure;
    }

}
