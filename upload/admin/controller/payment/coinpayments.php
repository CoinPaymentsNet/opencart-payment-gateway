<?php

/**
 * CoinPayments Payment Admin Controller
 */
class ControllerPaymentCoinpayments extends Controller
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
        $this->load->language('payment/coinpayments');
    }

    /**
     * Primary settings page
     * @return void
     */
    public function index()
    {
        $this->load->model('setting/setting');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('coinpayments', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success') . " / status: " . ($this->request->post['coinpayments_status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'));
            $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['text_edit'] = $this->language->get('text_edit');
        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');
        $this->data['text_all_zones'] = $this->language->get('text_all_zones');

        $this->data['tab_general'] = $this->language->get('tab_general');
        $this->data['tab_order_status'] = $this->language->get('tab_order_status');

        $this->data['entry_client_id'] = $this->language->get('entry_client_id');
        $this->data['entry_webhooks'] = $this->language->get('entry_webhooks');
        $this->data['entry_client_secret'] = $this->language->get('entry_client_secret');
        $this->data['entry_cancelled_status'] = $this->language->get('entry_cancelled_status');
        $this->data['entry_completed_status'] = $this->language->get('entry_completed_status');
        $this->data['entry_pending_status'] = $this->language->get('entry_pending_status');
        $this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $this->data['help_client_id'] = $this->language->get('help_client_id');
        $this->data['help_client_secret'] = $this->language->get('help_client_secret');
        $this->data['help_webhooks'] = $this->language->get('help_webhooks');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

        $this->data['url_action'] = $this->url->link('payment/coinpayments', 'token=' . $this->session->data['token'], true);
        $this->data['url_cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'] . '&type=payment', true);

        $this->data['breadcrumbs'] = array();
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false,
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: ',
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/coinpayments', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: ',
        );

        $this->data['coinpayments_client_id'] = (isset($this->request->post['coinpayments_client_id'])) ? $this->request->post['coinpayments_client_id'] : $this->config->get('coinpayments_client_id');
        $this->data['coinpayments_client_secret'] = (isset($this->request->post['coinpayments_client_secret'])) ? $this->request->post['coinpayments_client_secret'] : $this->config->get('coinpayments_client_secret');
        $this->data['coinpayments_webhooks'] = (isset($this->request->post['coinpayments_webhooks'])) ? $this->request->post['coinpayments_webhooks'] : $this->config->get('coinpayments_webhooks');

        $this->load->model('localisation/geo_zone');
        $this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $this->data['coinpayments_geo_zone_id'] = (isset($this->request->post['coinpayments_geo_zone_id'])) ? $this->request->post['coinpayments_geo_zone_id'] : $this->config->get('coinpayments_geo_zone_id');
        $this->data['coinpayments_sort_order'] = (isset($this->request->post['coinpayments_sort_order'])) ? $this->request->post['coinpayments_sort_order'] : $this->config->get('coinpayments_sort_order');
        $this->data['coinpayments_status'] = (isset($this->request->post['coinpayments_status'])) ? $this->request->post['coinpayments_status'] : $this->config->get('coinpayments_status');

        $this->load->model('localisation/order_status');
        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $this->data['coinpayments_cancelled_status'] = (isset($this->request->post['coinpayments_cancelled_status'])) ? $this->request->post['coinpayments_cancelled_status'] : $this->config->get('coinpayments_cancelled_status');
        $this->data['coinpayments_completed_status'] = (isset($this->request->post['coinpayments_completed_status'])) ? $this->request->post['coinpayments_completed_status'] : $this->config->get('coinpayments_completed_status');
        $this->data['coinpayments_pending_status'] = (isset($this->request->post['coinpayments_pending_status'])) ? $this->request->post['coinpayments_pending_status'] : $this->config->get('coinpayments_pending_status');

        $this->data['error_warning'] = '';
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } elseif (isset($this->session->data['warning'])) {
            $this->data['error_warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        } else {
            $this->data['error_warning'] = '';
        }

        $this->data['success'] = '';
        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $this->data['error_status'] = '';
        if (isset($this->error['status'])) {
            $this->data['error_status'] = $this->error['status'];
        }

        $this->data['error_client_id'] = '';
        if (isset($this->error['client_id'])) {
            $this->data['error_client_id'] = $this->error['client_id'];
        }

        $this->data['error_client_secret'] = '';
        if (isset($this->error['client_secret'])) {
            $this->data['error_client_secret'] = $this->error['client_secret'];
        }

        $this->data['error_invalid_credentials'] = '';
        if (isset($this->error['invalid_credentials'])) {
            $this->data['error_invalid_credentials'] = $this->error['invalid_credentials'];
        }

        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->template = 'payment/coinpayments.tpl';
        $this->response->setOutput($this->render());
    }

    /**
     * Install the extension by setting up some smart defaults
     * @return void
     */
    public function install()
    {
        $this->load->model('localisation/order_status');
        $order_statuses = $this->model_localisation_order_status->getOrderStatuses();
        foreach ($order_statuses as $order_status) {
            if ($order_status['name'] == 'Complete') {
                $default_completed = $order_status['order_status_id'];
            }
            if ($order_status['name'] == 'Canceled') {
                $default_cancelled = $order_status['order_status_id'];
            }
            if ($order_status['name'] == 'Pending') {
                $default_pending = $order_status['order_status_id'];
            }
        }

        $this->load->model('setting/setting');
        $default_settings = array(
            'coinpayments_client_id' => '',
            'coinpayments_client_secret' => '',
            'coinpayments_webhooks' => 0,
            'coinpayments_cancelled_status' => $default_cancelled,
            'coinpayments_completed_status' => $default_completed,
            'coinpayments_pending_status' => $default_pending,
            'coinpayments_geo_zone_id' => '0',
            'coinpayments_sort_order' => null,
        );
        $this->model_setting_setting->editSetting('coinpayments', $default_settings);
    }

    /**
     * Uninstall the extension by removing the settings
     * @return void
     */
    public function uninstall()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('coinpayments');
    }

    /**
     * Validate the primary settings for the CoinPayments extension
     * @return boolean True if the settings provided are valid
     */
    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'payment/coinpayments')) {
            $this->error['warning'] = $this->language->get('warning_permission');
        }

        if (empty($this->request->post['coinpayments_client_id'])) {
            $this->error['client_id'] = $this->language->get('error_client_id');
        }

        if (!empty($this->request->post['coinpayments_webhooks']) && empty($this->request->post['coinpayments_client_secret'])) {
            $this->error['client_secret'] = $this->language->get('error_client_secret');
        }

        if (empty($this->error)) {

            $this->load->model('payment/coinpayments');

            if (empty($this->request->post['coinpayments_webhooks']) &&
                !$this->model_payment_coinpayments->validateInvoice($this->request->post['coinpayments_client_id'])
            ) {

                $this->error['invalid_credentials'] = $this->language->get('error_invalid_credentials');

            } elseif (!empty($this->request->post['coinpayments_webhooks']) &&
                !$this->model_payment_coinpayments->validateWebhook(
                    $this->request->post['coinpayments_client_id'],
                    $this->request->post['coinpayments_client_secret']
                )
            ) {

                $this->error['invalid_credentials'] = $this->language->get('error_invalid_credentials');

            }
        }

        return empty($this->error);
    }
}
