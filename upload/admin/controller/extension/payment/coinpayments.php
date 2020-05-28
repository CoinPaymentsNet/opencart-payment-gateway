<?php

/**
 * CoinPayments Payment Admin Controller
 */
class ControllerExtensionPaymentCoinpayments extends Controller
{

    /** @var array $error Validation errors */
    private $error = array();

    /** @var boolean $ajax Whether the request was made via AJAX */
    private $ajax = false;

    /**
     * CoinPayments Payment Admin Controller Constructor
     * @param Registry $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('extension/payment/coinpayments');
        if (!empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->ajax = true;
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

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_coinpayments', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success') . " / status: " . ($this->request->post['payment_coinpayments_status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'));
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');


        $data['text_advanced'] = $this->language->get('text_advanced');

        $data['entry_client_id'] = $this->language->get('entry_client_id');
        $data['entry_webhooks'] = $this->language->get('entry_webhooks');
        $data['entry_client_secret'] = $this->language->get('entry_client_secret');
        $data['entry_cancelled_status'] = $this->language->get('entry_cancelled_status');
        $data['entry_completed_status'] = $this->language->get('entry_completed_status');
        $data['entry_pending_status'] = $this->language->get('entry_pending_status');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $data['help_client_id'] = $this->language->get('help_client_id');
        $data['help_client_secret'] = $this->language->get('help_client_secret');
        $data['help_webhooks'] = $this->language->get('help_webhooks');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['url_action'] = $this->url->link('extension/payment/coinpayments', 'user_token=' . $this->session->data['user_token'], 'SSL');
        $data['url_cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', 'SSL')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/coinpayments', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

        $data['payment_coinpayments_client_id'] = (isset($this->request->post['payment_coinpayments_client_id'])) ? $this->request->post['payment_coinpayments_client_id'] : $this->config->get('payment_coinpayments_client_id');
        $data['payment_coinpayments_client_secret'] = (isset($this->request->post['payment_coinpayments_client_secret'])) ? $this->request->post['payment_coinpayments_client_secret'] : $this->config->get('payment_coinpayments_client_secret');
        $data['payment_coinpayments_webhooks'] = (isset($this->request->post['payment_coinpayments_webhooks'])) ? $this->request->post['payment_coinpayments_webhooks'] : $this->config->get('payment_coinpayments_webhooks');

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $data['payment_coinpayments_geo_zone_id'] = (isset($this->request->post['payment_coinpayments_geo_zone_id'])) ? $this->request->post['payment_coinpayments_geo_zone_id'] : $this->config->get('payment_coinpayments_geo_zone_id');
        $data['payment_coinpayments_sort_order'] = (isset($this->request->post['payment_coinpayments_sort_order'])) ? $this->request->post['payment_coinpayments_sort_order'] : $this->config->get('payment_coinpayments_sort_order');
        $data['payment_coinpayments_status'] = (isset($this->request->post['payment_coinpayments_status'])) ? $this->request->post['payment_coinpayments_status'] : $this->config->get('payment_coinpayments_status');

        // #ORDER STATUSES
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $data['payment_coinpayments_cancelled_status'] = (isset($this->request->post['payment_coinpayments_cancelled_status'])) ? $this->request->post['payment_coinpayments_cancelled_status'] : $this->config->get('payment_coinpayments_cancelled_status');
        $data['payment_coinpayments_completed_status'] = (isset($this->request->post['payment_coinpayments_completed_status'])) ? $this->request->post['payment_coinpayments_completed_status'] : $this->config->get('payment_coinpayments_completed_status');
        $data['payment_coinpayments_pending_status'] = (isset($this->request->post['payment_coinpayments_pending_status'])) ? $this->request->post['payment_coinpayments_pending_status'] : $this->config->get('payment_coinpayments_pending_status');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['error_warning'] = '';
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } elseif (isset($this->session->data['warning'])) {
            $data['error_warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        } else {
            $data['error_warning'] = '';
        }

        $data['success'] = '';
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $data['error_status'] = '';
        if (isset($this->error['status'])) {
            $data['error_status'] = $this->error['status'];
        }

        $data['error_client_id'] = '';
        if (isset($this->error['client_id'])) {
            $data['error_client_id'] = $this->error['client_id'];
        }

        $data['error_client_secret'] = '';
        if (isset($this->error['client_secret'])) {
            $data['error_client_secret'] = $this->error['client_secret'];
        }

        $data['error_invalid_credentials'] = '';
        if (isset($this->error['invalid_credentials'])) {
            $data['error_invalid_credentials'] = $this->error['invalid_credentials'];
        }

        $this->response->setOutput($this->load->view('extension/payment/coinpayments', $data));
    }

    /**
     * Install the extension by setting up some smart defaults
     * @return void
     */
    public function install()
    {
        $this->load->model('localisation/order_status');
        $order_statuses = $this->model_localisation_order_status->getOrderStatuses();
        $default_paid = null;
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
            'payment_coinpayments_client_id' => '',
            'payment_coinpayments_client_secret' => '',
            'payment_coinpayments_webhooks' => 0,
            'payment_coinpayments_cancelled_status' => $default_cancelled,
            'payment_coinpayments_completed_status' => $default_completed,
            'payment_coinpayments_pending_status' => $default_pending,
            'payment_coinpayments_geo_zone_id' => '0',
            'payment_coinpayments_sort_order' => null,
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

    /**
     * Validate the primary settings for the CoinPayments extension
     * @return boolean True if the settings provided are valid
     */
    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/coinpayments')) {
            $this->error['warning'] = $this->language->get('warning_permission');
        }

        if (empty($this->request->post['payment_coinpayments_client_id'])) {
            $this->error['client_id'] = $this->language->get('error_client_id');
        }

        if (!empty($this->request->post['payment_coinpayments_webhooks']) && empty($this->request->post['payment_coinpayments_client_secret'])) {
            $this->error['client_secret'] = $this->language->get('error_client_secret');
        }

        if (empty($this->error)) {

            $this->load->model('extension/payment/coinpayments');

            if (empty($this->request->post['payment_coinpayments_webhooks']) &&
                !$this->model_extension_payment_coinpayments->validateInvoice($this->request->post['payment_coinpayments_client_id'])
            ) {

                $this->error['invalid_credentials'] = $this->language->get('error_invalid_credentials');

            } elseif (!empty($this->request->post['payment_coinpayments_webhooks']) &&
                !$this->model_extension_payment_coinpayments->validateWebhook(
                    $this->request->post['payment_coinpayments_client_id'],
                    $this->request->post['payment_coinpayments_client_secret']
                )
            ) {

                $this->error['invalid_credentials'] = $this->language->get('error_invalid_credentials');

            }
        }

        return empty($this->error);
    }
}
