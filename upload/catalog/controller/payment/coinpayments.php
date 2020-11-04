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

        $this->data['action'] = $this->url->link('payment/coinpayments/process_order', '', 'SSL');

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/coinpayments.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/coinpayments.tpl';
        } else {
            $this->template = 'default/template/payment/coinpayments.tpl';
        }
        $this->render();
    }

    public function process_order()
    {
        $this->load->model('checkout/order');

        $order_id = $this->session->data['order_id'];
        $this->data['action'] = sprintf('%s/%s/', Coinpayments::CHECKOUT_URL, Coinpayments::API_CHECKOUT_ACTION);

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

            $this->model_checkout_order->confirm($order_id, $this->config->get('coinpayments_pending_status'));
            $action = sprintf('%s?%s', $this->data['action'], http_build_query($this->data['form_params']));


            if (isset($this->session->data['order_id'])) {
                $this->cart->clear();

                unset($this->session->data['shipping_method']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['payment_method']);
                unset($this->session->data['payment_methods']);
                unset($this->session->data['guest']);
                unset($this->session->data['comment']);
                unset($this->session->data['order_id']);
                unset($this->session->data['coupon']);
                unset($this->session->data['reward']);
                unset($this->session->data['voucher']);
                unset($this->session->data['vouchers']);
                unset($this->session->data['totals']);
            }

            $this->response->redirect($action);
        } else {
            $this->data['error_coinpayments'] = $this->language->get('error_create_invoice');
        }


        $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
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
                $host_hash = array_shift($invoice_str);
                $invoice_id = array_shift($invoice_str);

                if ($host_hash == md5($this->config->get('config_secure') ? HTTP_SERVER : HTTPS_SERVER) && $invoice_id) {
                    $this->load->model('checkout/order');
                    $order_info = $this->model_checkout_order->getOrder($invoice_id);
                    if ($order_info) {
                        $status = $request_data['invoice']['status'];
                        if ($status == 'Completed') {
                            if (!$order_info['order_status_id'] || $order_info['order_status_id'] != $this->config->get('coinpayments_completed_status')) {
                                $this->model_checkout_order->update($order_info['order_id'], $this->config->get('coinpayments_completed_status'), 'Status: ' . $status);
                            }
                        } elseif ($status == 'Cancelled') {
                            if (!$order_info['order_status_id'] || $order_info['order_status_id'] != $this->config->get('coinpayments_cancelled_status')) {
                                $this->model_checkout_order->update($order_info['order_id'], $this->config->get('coinpayments_cancelled_status'), 'Status: ' . $status);
                            }
                        }
                    }
                }
            }
        }

    }
}
