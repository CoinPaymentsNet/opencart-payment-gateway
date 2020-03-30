<?php

/**
 * CoinPayments Payment Controller
 */
class ControllerPaymentCoinpayments extends Controller
{

    /** @var CoinPaymentsLibrary $coinpayments */
    private $coinpayments;

    /**
     * CoinPayments Payment Controller Constructor
     * @param Registry $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->language('payment/coinpayments');
        $this->load->library('coinpayments');
        $this->coinpayments = new Coinpayments($registry);
    }

    public function index()
    {

        $this->data['button_confirm'] = $this->language->get('button_confirm');

        $this->load->model('checkout/order');
        if (!isset($this->session->data['order_id'])) {
            $this->response->redirect($this->url->link('checkout/cart'));
            return;
        }

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        if (false === $order_info) {
            $this->response->redirect($this->url->link('checkout/cart'));
            return;
        }

        $this->load->model('payment/coinpayments');

        $invoice = $this->model_payment_coinpayments->createInvoice($order_info);

        if (isset($invoice['id'])) {
            $this->data['form_params'] = array(
                'invoice-id' => $invoice['id'],
                'success-url' => $this->url->link('checkout/success'),
                'cancel-url' => $this->url->link('checkout/checkout', '', 'SSL'),
            );
        } else {
            $this->data['error_coinpayments'] = $this->language->get('error_create_invoice');
        }

        $this->data['action'] = sprintf('%s/%s/', Coinpayments::API_URL, Coinpayments::API_CHECKOUT_ACTION);
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/coinpayments.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/coinpayments.tpl';
        } else {
            $this->template = 'default/template/payment/coinpayments.tpl';
        }
        $this->render();
    }

    public function success()
    {
        $this->load->model('checkout/order');
        $order_id = $this->session->data['order_id'];
        if (is_null($order_id)) {
            $this->response->redirect($this->url->link('checkout/success'));
            return;
        }

        // Progress the order status
        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('coinpayments_pending_status'));
        $this->response->redirect($this->url->link('checkout/success'));
    }

    public function callback()
    {
        $content = file_get_contents('php://input');

        if ($this->config->get('coinpayments_webhooks') && !empty($this->request->server['HTTP_X_COINPAYMENTS_SIGNATURE'])) {

            $signature = $this->request->server['HTTP_X_COINPAYMENTS_SIGNATURE'];
            $request_data = json_decode($content, true);
            $this->load->model('payment/coinpayments');

            if ($this->model_payment_coinpayments->checkDataSignature($signature, $content) && isset($request_data['invoice']['invoiceId'])) {

                $invoice_str = $request_data['invoice']['invoiceId'];
                $invoice_str = explode('|', $invoice_str);
                $host_hast = array_shift($invoice_str);
                $invoice_id = array_shift($invoice_str);

                if ($host_hast == md5($this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER) && $invoice_id) {
                    $this->load->model('checkout/order');
                    $order_info = $this->model_checkout_order->getOrder($invoice_id);
                    if ($order_info) {
                        $status = $request_data['invoice']['status'];
                        if ($status == 'Completed') {
                            if (!$order_info['order_status_id'] || $order_info['order_status_id'] != $this->config->get('coinpayments_completed_status')) {
                                $this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('coinpayments_completed_status'), 'Status: ' . $status);
                            }
                        } elseif ($status == 'Cancelled') {
                            if (!$order_info['order_status_id'] || $order_info['order_status_id'] != $this->config->get('coinpayments_cancelled_status')) {
                                $this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('coinpayments_cancelled_status'), 'Status: ' . $status);
                            }
                        }
                    }
                }
            }
        }

    }
}
