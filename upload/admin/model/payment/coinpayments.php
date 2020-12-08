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
        require_once(DIR_SYSTEM . 'library/coinpayments.php');
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
     * @param $client_id
     * @return bool
     * @throws Exception
     */
    public function validateInvoice($client_id)
    {
        $invoice = $this->coinpayments->createSimpleInvoice($client_id);
        return !empty($invoice['id']);
    }

    /**
     * @param $client_id
     * @param $client_secret
     * @return bool
     * @throws Exception
     */
    public function validateWebhook($client_id, $client_secret)
    {

        $valid = false;

        $webhooks_list = $this->coinpayments->getWebhooksList($client_id, $client_secret);
        if (!empty($webhooks_list)) {

            $webhooks_urls_list = array();
            if (!empty($webhooks_list['items'])) {
                $webhooks_urls_list = array_map(function ($webHook) {
                    return $webHook['notificationsUrl'];
                }, $webhooks_list['items']);
            }

            if (!in_array($this->coinpayments->getNotificationUrl(), $webhooks_urls_list)) {
                if (!empty($this->coinpayments->createWebHook($client_id, $client_secret, $this->coinpayments->getNotificationUrl()))) {
                    $valid = true;
                }
            } else {
                $valid = true;
            }
        }

        return $valid;
    }
}
