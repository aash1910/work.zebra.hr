<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Service;

use Exception;
use Store\Dependency\Illuminate\Support\Str;
use Store\Dependency\Omnipay\Common\AbstractGateway;
use Store\Dependency\Omnipay\Common\CreditCard;
use Store\Dependency\Omnipay\Common\Exception\OmnipayException;
use Store\Dependency\Omnipay\Common\Helper;
use Store\Dependency\Omnipay\Common\Helper as OmnipayHelper;
use Store\Dependency\Omnipay\Common\Issuer;
use Store\Dependency\Omnipay\Common\ItemBag;
use Store\Dependency\Omnipay\Common\Message\AbstractRequest;
use Store\Dependency\Omnipay\Common\Message\RequestInterface;
use Store\Dependency\Omnipay\Common\Message\ResponseInterface;
use Store\Dependency\Omnipay\Omnipay;
use Store\Exception\CartException;
use Store\Model\Order;
use Store\Model\PaymentMethod;
use Store\Model\Transaction;

/**
 * Payments Service
 */
class PaymentsService extends AbstractService
{
    /**
    /**
     * Create a new transaction
     *
     * @return Transaction
     */
    public function new_transaction(Order $order)
    {
        $transaction = new Transaction();
        $transaction->site_id = $order->site_id;
        $transaction->order_id = $order->id;
        $transaction->date = time();
        $transaction->status = Transaction::PENDING;

        return $transaction;
    }

    /**
     * Find all installed payment gateways
     *
     * @return array An array of Omnipay gateway names
     */
    public function get_payment_gateways()
    {
        $gateways = $this->getGateways();

        if ($this->ee->extensions->active_hook('store_payment_gateways')) {
            $gateways = $this->ee->extensions->call('store_payment_gateways', $gateways);
        }

        usort($gateways, 'strcasecmp');

        return $gateways;
    }

    /**
     * Automatically find and register all officially supported gateways
     *
     * @return array An array of gateway names
     */
    public function getGateways()
    {
        foreach (glob(dirname(__FILE__) . '/../Gateways/*.php') as $file) {
            $gateway = pathinfo($file, PATHINFO_FILENAME);
            if (class_exists($this->getGatewayClassName($gateway))) {
                Omnipay::register($gateway);
            }
        }

        return Omnipay::all();
    }

    /**
     * Return the fully qualified Gateway Class Name, including namespace
     *
     * @param string $gateway Name of Gateway
     * @return string Fully qualified Class namespace
     */
    public function getGatewayClassName($gateway)
    {
        $storeGateway = '\\Store\\Gateways\\' . $gateway;

        if (class_exists($storeGateway)) {
            $class = $storeGateway;
        } else {
            $class = Helper::getGatewayClassName($gateway);
        }

        return $class;
    }

    /**
     * Find a payment method record
     *
     * @param $name
     * @return PaymentMethod|null NULL if not found or missing gateway class
     */
    public function find_payment_method($name)
    {
        $payment_method = PaymentMethod::where('site_id', $this->ee->config->item('site_id'))
            ->where('class', $name)
            ->where('enabled', 1)
            ->first();

        if ($payment_method) {
            $gateways = $this->get_payment_gateways();

            // if an Omnipay gateway exists, use it.
            $real_class = $this->getGatewayClassName($payment_method->class);

            // Check if the found method's class is in the registered list AND the class exists
            if (in_array($payment_method->class, $gateways) && class_exists($real_class)) {
                return $payment_method;
            }
        }
    }

    /**
     * Create and initialize a payment gateway
     *
     * @param $name
     * @return AbstractGateway
     */
    public function load_payment_method($name)
    {
        $payment_method = $this->find_payment_method($name);

        if (!$payment_method) {
            throw new CartException(lang('valid_payment_method'));
        }

        return $payment_method->createGateway();
    }

    /**
     * Add a new payment for an order
     *
     * @param Transaction $transaction
     * @param array $card_data
     * @param bool $send
     * @return
     */
    public function process_payment(Order $order, $transaction, $card_data, $send = true)
    {
        // load driver
        $gateway = $this->load_payment_method($transaction->payment_method);

        // decide which action to use
        if (!$gateway->supportsPurchase() && $gateway->supportsAuthorize()) {
            // Manual gateway always uses `authorize` method
            $action = 'authorize';
        } elseif ('authorize' === $this->ee->config->item('store_cc_payment_method') &&
            $gateway->supportsAuthorize()) {
            $action = 'authorize';
        } else {
            $action = 'purchase';
        }

        // save transaction, so it has an id for build_payment_request()
        $transaction->type = $action;
        $transaction->save();
        try {
            $card = $this->build_payment_credit_card($order, $card_data, $gateway);
            $requestParams = $this->build_payment_request($transaction);
            if (is_array($card_data)) {
                $requestParams = array_merge($requestParams, $card_data);
            }
            $request = $gateway->$action($requestParams);
            $transaction->brand = $card->getBrand();
            $transaction->last_four = $card->getNumberLastFour();
            $request->setCard($card);
            if (isset($card_data['check_number'])) {
                $request->setCheck($card_data);
            }
            $request->setItems($this->build_payment_items($order));
            if (isset($card_data['token']) && method_exists($request, 'setToken')) {
                $request->setToken($card_data['token']);
            }
            if (isset($card_data['issuer']) && method_exists($request, 'setIssuer')) {
                $request->setIssuer($card_data['issuer']);
            }
            if (method_exists($request, 'setButtonSource')) {
                $request->setButtonSource('DevDemon_SP');
            }
            $transaction->save();
            if ($send) {
                $this->send_payment_request($request, $transaction);
            } else {
                return $request;
            }
        } catch (Exception $e) {
            $transaction->status = Transaction::FAILED;
            if (strcasecmp($transaction->payment_method, 'Dummy') === 0) {
                $transaction->message = $e->getMessage();
            } else {
                $transaction->message = lang('store.payment.communication_error');
            }
            $transaction->save();
            $errorMsg = 'Credit Card authentication has failed.';
            if ($transaction->message && $transaction->message !== 'Credit Card authentication has failed.') {
                $errorMsg .= ' Error: ' . $transaction->message;
            }
            $this->ee->session->set_flashdata(['store_payment_error' => $errorMsg]);
            $this->ee->functions->redirect($transaction->order->cancel_url);
            return;
        }
    }

    /**
     * Handle off-site payment return
     */
    public function complete_payment(Transaction $transaction)
    {
        $order = $transaction->order;

        // load payment driver
        $gateway = $this->load_payment_method($transaction->payment_method);

        // ignore already processed transactions
        if ($transaction->status != Transaction::REDIRECT) {
            if ($transaction->status == Transaction::SUCCESS) {
                ee()->functions->redirect($order->parsed_return_url);
                return;
            } else {
                // New JSON Response
                if (ee()->input->is_ajax_request() && ee()->config->item('store_new_json_response') == 'yes') {
                    $this->returnJsonResponse($order, $transaction->message);
                }

                ee()->session->set_flashdata(['store_payment_error' => $transaction->message]);
                ee()->functions->redirect($order->cancel_url);
                return;
            }
        }

        // For Stripe PaymentIntents, check real status from Omnipay before confirming
        if ($gateway instanceof \Store\Gateways\Stripe_PaymentIntents && !empty($transaction->reference)) {
            try {
                $fetchRequest = $gateway->fetchPaymentIntent(['paymentIntentReference' => $transaction->reference]);
                $fetchResponse = $fetchRequest->send();
                $data = $fetchResponse->getData();

                // Handle different PaymentIntent statuses
                if (isset($data['status'])) {
                    if ($data['status'] === 'succeeded') {
                        // Payment already succeeded, update transaction and redirect
                        $transaction->status = Transaction::SUCCESS;
                        $transaction->message = 'Payment already completed successfully';
                        $transaction->save();

                        // Update order paid total and mark as complete
                        $this->update_order_paid_total($order);

                        ee()->functions->redirect($order->parsed_return_url);
                        return;
                    } elseif ($data['status'] === 'requires_payment_method' || $data['status'] === 'requires_action') {
                        // Payment needs additional action
                        $transaction->status = Transaction::FAILED;
                        $transaction->message = 'Payment requires additional action';
                        $transaction->save();
                        ee()->session->set_flashdata(['store_payment_error' => $transaction->message]);
                        ee()->functions->redirect($order->cancel_url);
                        return;
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue with confirmation attempt
                error_log('Stripe status check failed: ' . $e->getMessage());
            }
        }

        $action = 'complete' . ucfirst($transaction->type);
        $supportsAction = 'supports' . ucfirst($action);

        if ($gateway->$supportsAction()) {
            // don't send notifyUrl for completePurchase
            $params = $this->build_payment_request($transaction);

            if (method_exists($gateway, 'setParamReference')) {
                $params = $gateway->setParamReference($params, $transaction);
            }

            unset($params['notifyUrl']);

            // Ensure paymentIntentReference is set for Stripe PaymentIntents confirmation
            if (
                $gateway instanceof \Store\Gateways\Stripe_PaymentIntents &&
                in_array($action, ['completePurchase', 'completeAuthorize', 'confirm'])
            ) {
                if (!empty($transaction->reference)) {
                    $params['paymentIntentReference'] = $transaction->reference;
                }
            }

            /** @var AbstractRequest $request */
            $request = $gateway->$action($params);
            if (method_exists($gateway, 'setApiVersion') && method_exists($gateway, 'getApiVersion')) {
                $gateway->setApiVersion($gateway->getApiVersion());
            }
            if (method_exists($request, 'setItems')) {
                $request->setItems($this->build_payment_items($order));
            }

            $this->send_payment_request($request, $transaction);
        } else {
            exit('Payment return not supported');
        }
    }

    /**
     * Handle off-site notifications callbacks to receive the results of a payment or authorization, and
     * subsequently, validate the transaction
     */
    public function notification_handler(Transaction $transaction)
    {
        // load payment driver
        $gateway = $this->load_payment_method($transaction->payment_method);
        $notifyRequest = $gateway->acceptNotification();

        if ($transaction->id) {
            $notifyRequest->setTransactionReference($transaction->id);

            if (!$notifyRequest->isValid()) {
                // Respond to gateway and indicate we are not accepting this message.
                $notifyRequest->invalid($this->build_return_url($transaction), 'Signature validation failed.');
            } else {
                // accept the notification, update the local transaction and let the payment gateway know
                $notifyRequest->getData();

                // update your transaction with the final transactionReference
                $transaction->reference = $notifyRequest->getTransactionReference();

                if ($notifyRequest->getTransactionStatus() == $notifyRequest::STATUS_COMPLETED) {
                    $transaction->status = Transaction::SUCCESS;
                } elseif ($notifyRequest->getTransactionStatus() == $notifyRequest::STATUS_PENDING) {
                    $transaction->status = Transaction::REDIRECT;
                } else {
                    ee()->session->set_flashdata(['store_payment_error' => $notifyRequest->getMessage()]);
                }

                $transaction->save();

                // Let the gateway know you have accepted and saved the result:
                $notifyRequest->confirm($this->build_return_url($transaction));
            }
        } else {
            $notifyRequest->error($this->build_return_url($transaction), 'This transaction does not exist on the system');
        }
    }

    public function capture_transaction(Transaction $transaction, $member_id = null)
    {
        return $this->process_capture_or_refund($transaction, Transaction::CAPTURE, $member_id);
    }

    public function refund_transaction(Transaction $transaction, $member_id = null)
    {
        return $this->process_capture_or_refund($transaction, Transaction::REFUND, $member_id);
    }

    protected function process_capture_or_refund(Transaction $parent, $action, $member_id = null)
    {
        $order = $parent->order;
        $child = $this->new_transaction($order);
        $child->payment_method = $order->payment_method;
        $child->parent_id = $parent->id;
        $child->member_id = (int)$member_id;
        $child->payment_method = $parent->payment_method;
        $child->type = $action;
        $child->amount = $parent->amount;
        $child->save();

        $gateway = $this->load_payment_method($child->payment_method);
        /** @var AbstractRequest $request */
        $request = $gateway->$action($this->build_payment_request($child));
        if (method_exists($gateway, 'setApiVersion') && method_exists($gateway, 'getApiVersion')) {
            $gateway->setApiVersion($gateway->getApiVersion());
        }
        $request->setTransactionReference($parent->reference);

        if (method_exists($request, 'setVPSTxId')) {
            if (array_key_exists('VPSTxId', json_decode($child->reference, true))) {
                $request->setVPSTxId(json_decode($child->reference, true)['VPSTxId']);
            }
        }

        try {
            $response = $request->send();
            $this->update_transaction($child, $response);
        } catch (Exception $e) {
            $child->status = Transaction::FAILED;
            $child->message = $e->getMessage();
            $child->save();
        }

        return $child;
    }

    /**
     * Build Omnipay payment request array
     *
     * @return array
     * @throws Exception
     */
    public function build_payment_request(Transaction $transaction)
    {
        $request = [
            'amount'               => $transaction->amount,
            'currency'             => config_item('store_currency_code'),
            'transactionId'        => $transaction->id,
            'description'          => lang('store.order') . ' #' . $transaction->order->id,
            'transactionReference' => $transaction->hash,
            'returnUrl'            => $this->ee->store->store->get_action_url('act_payment_return') . '&H=' . $transaction->hash,
            'notifyUrl'            => $this->ee->store->store->get_action_url('act_payment_return') . '&H=' . $transaction->hash,
            'cancelUrl'            => $transaction->order->cancel_url,
            'clientIp'             => $this->ee->input->ip_address(),

            // custom gateways may wish to access the order directly
            'order'   => $transaction->order,
            'orderId' => $transaction->order->id,
        ];

        $gateway = $this->load_payment_method($transaction->payment_method);
        if (method_exists($gateway, 'getTransformer')) {
            $request = $gateway->getTransformer()->transform($transaction) + $request;
        }

        return $request;
    }

    /**
     * Build transaction return URL
     *
     * @return string
     */
    public function build_return_url(Transaction $transaction)
    {
        return $this->ee->store->store->get_action_url('act_payment_return') . '&H=' . $transaction->hash;
    }

    /**
     * Build Omnipay credit card array
     *
     * @param array $post_data
     * @param $gateway
     * @return CreditCard
     */
    public function build_payment_credit_card(Order $order, $post_data, $gateway)
    {
        $card = new CreditCard();
        $fields = ['first_name', 'last_name', 'address1', 'address2', 'city', 'postcode', 'state', 'country', 'phone', 'company'];

        // default to order details
        foreach ($fields as $key) {
            $card->{'setBilling' . Str::studly($key)}($order->{'billing_' . $key});
            $card->{'setShipping' . Str::studly($key)}($order->{'shipping_' . $key});
        }
        $card->setEmail($order->order_email);

        // map legacy parameters to new CreditCard object
        $map = [
            'card_no'     => 'number',
            'card_name'   => 'name',
            'exp_month'   => 'expiryMonth',
            'exp_year'    => 'expiryYear',
            'start_month' => 'startMonth',
            'start_year'  => 'startYear',
            'csc'         => 'cvv',
        ];

        foreach ($map as $old => $new) {
            if (isset($post_data[$old])) {
                $post_data[$new] = $post_data[$old];
                unset($post_data[$old]);
            }
        }

        if (method_exists($gateway, 'getTransformer')) {
            // initialize card attributes with extra card transformer data
            OmnipayHelper::initialize($card, $gateway->getTransformer()->cardTransform($order));
        }

        if ($post_data && strlen(trim(implode('', $post_data))) !== 0) {
            // initialize card attributes with post data
            OmnipayHelper::initialize($card, $post_data);
        } else if (isset($post_data['number'])) {
            // If the user posts NO card data at all, we hit a deprecation error where the number is not set.
            // On some installs, this stops the validation from completing, so we set the number here so that
            // the validation can complete.
            $card->setNumber($post_data['number']);
        }

        return $card;
    }

    /**
     * Build Omnipay items array
     *
     * @return ItemBag
     */
    public function build_payment_items(Order $order)
    {
        $items = new ItemBag();

        foreach ($order->items as $item) {
            $items->add([
                'name'     => $item->title,
                'quantity' => $item->item_qty,
                'price'    => $item->price,
            ]);
        }

        foreach ($order->adjustments as $adjustment) {
            if (!$adjustment->included) {
                $items->add([
                    'name'     => $adjustment->name,
                    'quantity' => 1,
                    'price'    => $adjustment->amount,
                ]);
            }
        }

        return $items;
    }

    /**
     * Send a payment request to the gateway, and redirect appropriately
     *
     * @param $transaction
     */
    public function send_payment_request(RequestInterface $request, $transaction)
    {
        if ($this->ee->extensions->active_hook('store_payment_request_start')) {
            $this->ee->extensions->call('store_payment_request_start', $request, $transaction);
            if ($this->ee->extensions->end_script) {
                return;
            }
        }

        try {
            $response = $request->send();
            $transaction = $this->update_transaction($transaction, $response);

            // Add descriptive gateway error message if needed
            $gateway = ee()->store->payments->load_payment_method($transaction->payment_method);
            if ($transaction->status === Transaction::FAILED && $gateway && method_exists($gateway, 'getFailureMessage')) {
                $transaction->message = $gateway->getFailureMessage($request, $response);
                $transaction->save();
            }

            if ($transaction->status == Transaction::REDIRECT) {
                // Check if we have a specific redirect URL stored
                if (strpos($transaction->message, 'redirect:') === 0) {
                    $redirectUrl = substr($transaction->message, 9);
                    error_log("Redirect URL: " . $redirectUrl);
                    $this->ee->functions->redirect($redirectUrl);
                    return;
                }

                // Standard redirect
                if (is_object($response) && method_exists($response, 'getRedirectUrl')) {
                    $redirectUrl = $response->getRedirectUrl();
                    if ($redirectUrl) {
                        error_log("Redirecting to: " . $redirectUrl);
                        $this->ee->functions->redirect($redirectUrl);
                        return;
                    }
                }
            }

            // Exception for SagePay
            if (method_exists($response, 'confirm')) {
                $response->confirm($this->build_return_url($transaction));
            }
        } catch (Exception $e) {
            $transaction->status = Transaction::FAILED;
            if (strcasecmp($transaction->payment_method, 'Dummy') === 0) {
                $transaction->message = $e->getMessage();
            } else {
                $transaction->message = lang('store.payment.communication_error');
            }
            $transaction->save();
        }

        if ($this->ee->extensions->active_hook('store_payment_request_end')) {
            $this->ee->extensions->call('store_payment_request_end', $request, $transaction);
            if ($this->ee->extensions->end_script) {
                return;
            }
        }

        if ($transaction->status == Transaction::SUCCESS) {
            if ($this->ee->input->is_ajax_request() && $this->ee->config->item('store_new_json_response') == 'yes') {
                // New JSON Response
                $this->returnJsonResponse($transaction->order, null, $transaction);
            }
            $this->ee->functions->redirect($transaction->order->parsed_return_url);
        } else {
            if ($this->ee->input->is_ajax_request() && $this->ee->config->item('store_new_json_response') == 'yes') {
                // New JSON Response
                $this->returnJsonResponse($transaction->order, $transaction->message);
            }

            if ($transaction->status == Transaction::FAILED) {
                // Include detailed error message if available
                $errorMsg = 'Credit Card authentication has failed.';
                if ($transaction->message && $transaction->message !== 'Credit Card authentication has failed.') {
                    $errorMsg .= ' Error: ' . $transaction->message;
                }
                $this->ee->session->set_flashdata(['store_payment_error' => $errorMsg]);
            } else {
                $this->ee->session->set_flashdata(['store_payment_error' => $transaction->message]);
            }
            $this->ee->functions->redirect($transaction->order->cancel_url);
        }
    }

    public function update_transaction(Transaction $transaction, ResponseInterface $response)
    {
        if ($response->isRedirect()) {
            $transaction->status = Transaction::REDIRECT;
            $transaction->message = $response->getMessage();
        } elseif ($response->isSuccessful()) {
            $transaction->status = Transaction::SUCCESS;
            $transaction->message = $response->getMessage();
        } else {
            $transaction->status = Transaction::FAILED;
            $transaction->message = $response->getMessage();
        }

        $transaction->reference = $response->getTransactionReference();

        // Handle gateway-specific response processing
        if ($transaction->payment_method) {
            $gateway = $this->load_payment_method($transaction->payment_method);

            // Handle Stripe Payment Intent responses
            if ($gateway instanceof \Store\Gateways\Stripe_PaymentIntents) {
                $transaction = $gateway->handlePaymentIntentResponse($response, $transaction);

                // Get charge ID for refund reference if needed
                if ($transaction->reference === null) {
                    $chargeId = $gateway->getChargeIdForRefund($response);
                    if ($chargeId) {
                        $transaction->reference = $chargeId;
                    }
                }
            }
        }

        $transaction->save();

        if ($response->isSuccessful()) {
            $transaction->order->payment_method = $transaction->payment_method;
            $this->update_order_paid_total($transaction->order);
        }

        if ($this->ee->extensions->active_hook('store_transaction_update_end')) {
            $this->ee->extensions->call('store_transaction_update_end', $transaction, $response);
        }

        return $transaction;
    }

    public function update_order_paid_total(Order $order)
    {
        $order->order_paid = $order->getTotalPaid();

        if ($order->is_order_paid && !$order->order_paid_date) {
            $order->order_paid_date = time();
        }

        $order->save();

        if (!$order->is_order_complete) {
            if ($order->is_order_paid) {
                $order->markAsComplete();
            } else {
                // maybe enough funds are authorized to complete the order
                if ($order->getTotalAuthorized() >= $order->order_total) {
                    $order->markAsComplete();
                }
            }
        }
    }

    public function redirect_form($url)
    {
        $site_name = htmlspecialchars(config_item('site_name'), ENT_QUOTES);
        $url = htmlspecialchars($url, ENT_QUOTES);

        $out = <<<EOF
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="1;URL=$url" />
    <title>Redirecting...</title>
</head>
<body onload="document.payment.submit();">
    <p>Please wait while we redirect you back to $site_name...</p>
    <form name="payment" action="$url" method="post">
        <p><input type="submit" value="Continue" /></p>
    </form>
</body>
</html>
EOF;
        echo $out;
        exit;
    }

    public function get_enabled_payment_method_options($selectedClass = null)
    {
        $methods = PaymentMethod::where('site_id', config_item('site_id'))->where('enabled', 1)->orderBy('title')->get();

        $html = '';
        foreach ($methods as $method) {
            $selected = $method->class == $selectedClass ? 'selected' : '';
            $html .= "<option value='{$method->class}' $selected>{$method->title}</option>\n";
        }

        return $html;
    }

    public function fetch_issuers($gateway_name)
    {
        // create cached http gateway
        $payment_method = $this->find_payment_method($gateway_name);
        if (!$payment_method) {
            return [lang('valid_payment_method')];
        }

        try {
            // create cached payment gateway to store list of issuers
            $gateway = $payment_method->createGateway($this->ee->store->cached_http);

            $response = $gateway->fetchIssuers()->send();
            if ($response->isSuccessful()) {
                return $response->getIssuers();
            } else {
                return [$response->getMessage()];
            }
        } catch (OmnipayException $e) {
            return [$e->getMessage()];
        } catch (Exception $e) {
            return [lang('store.payment.communication_error')];
        }
    }

    public function fetch_issuer_options($gateway_name)
    {
        $rawIssuers = $this->fetch_issuers($gateway_name);

        $issuers = [];

        foreach ($rawIssuers as $key => $val) {
            if (is_string($val)) {
                $issuers[$key] = $val;
            } elseif ($val instanceof Issuer) {
                $issuers[$val->getId()] = $val->getName();
            }
        }

        return store_select_options($issuers);
    }

    /**
     * @param Order $cart
     * @param bool $error
     * @param Transaction|bool $transaction
     */
    private function returnJsonResponse($cart, $error = false, $transaction = false)
    {
        $out = [];
        $out['cart'] = $cart->toTagArray();

        if ($error) {
            $out['form_errors'] = ['payment' => $error];
        }

        if ($transaction) {
            $out['transaction'] = $transaction->toArray();
        }

        $this->ee->output->send_ajax_response($out);
    }
}
