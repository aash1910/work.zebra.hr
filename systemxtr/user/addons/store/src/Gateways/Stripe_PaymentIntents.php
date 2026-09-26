<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Gateways;

use Store\Dependency\Omnipay\Common\Message\RequestInterface;
use Store\Dependency\Omnipay\Stripe\PaymentIntentsGateway;
use Store\Model\Transaction;
use Store\Transformer\AbstractTransformer;
use Store\Transformer\DefaultTransformer;

/**
 * This is a Stripe Gateway override class that will allow it to work more seamlessly with Exp:resso Store
 */
class Stripe_PaymentIntents extends PaymentIntentsGateway
{
    /** @var string */
    public const VERSION = '2025-04-30.basil';

    public function getName()
    {
        return 'Stripe';
    }

    public function getShortName()
    {
        return 'Stripe_PaymentIntents';
    }

    public function getDefaultParameters()
    {
        return [
            'apiKey' => '',
            'publishableKey' => '',
            'apiVersion' => self::VERSION,
        ];
    }

    public function getApiKey()
    {
        return $this->getParameter('apiKey');
    }

    public function setApiKey($value)
    {
        return $this->setParameter('apiKey', $value);
    }

    public function getPublishableKey()
    {
        return $this->getParameter('publishableKey');
    }

    public function setPublishableKey($value)
    {
        return $this->setParameter('publishableKey', $value);
    }

    public function getApiVersion()
    {
        return $this->getParameter('apiVersion');
    }

    public function setApiVersion($value)
    {
        return $this->setParameter('apiVersion', $value);
    }

    /**
     * @param array $parameters
     * @return array $parameters
     */
    public function initialize(array $parameters = array())
    {
        // Make sure API version is set
        if (!isset($parameters['apiVersion'])) {
            $parameters['apiVersion'] = self::VERSION;
        }

        if (isset($parameters['secret_key'])) {
            $parameters['apiKey'] = $parameters['secret_key'];
        }
        if (isset($parameters['publishable_key'])) {
            $parameters['publishableKey'] = $parameters['publishable_key'];
        }

        return parent::initialize($parameters);
    }

    /**
     * @return AbstractTransformer
     */
    public function getTransformer()
    {
        return new DefaultTransformer();
    }

    /**
     * Create a purchase request
     *
     * @param array $parameters
     * @return \Omnipay\Stripe\Message\PurchaseRequest
     */
    public function purchase(array $parameters = array())
    {
        $parameters = $this->massageStripeParameters($parameters);
        $request = $this->createRequest('\\Store\\Gateways\\Stripe\\Message\\PurchaseRequest', $parameters);
        $this->applyStripeRequestSettings($request, $parameters);
        return $request;
    }

    public function authorize(array $parameters = array())
    {
        $parameters = $this->massageStripeParameters($parameters);
        $request = $this->createRequest('\\Store\\Gateways\\Stripe\\Message\\AuthorizeRequest', $parameters);
        $this->applyStripeRequestSettings($request, $parameters);
        return $request;
    }

    protected function massageStripeParameters(array $parameters)
    {
        // If 'token' is present, set it as 'payment_method'
        if (isset($parameters['token'])) {
            $parameters['payment_method'] = $parameters['token'];
            error_log('Using Stripe Payment Intent with payment method: ' . $parameters['token']);
        }
        if (isset($parameters['payment_method'])) {
            error_log('Setting payment method from payment_method field: ' . $parameters['payment_method']);
        }
        return $parameters;
    }

    protected function applyStripeRequestSettings($request, $parameters)
    {
        // Set payment method on the request object if possible
        if (isset($parameters['payment_method']) && method_exists($request, 'setPaymentMethod')) {
            $request->setPaymentMethod($parameters['payment_method']);
        }
        // Set token on the request object if possible
        if (isset($parameters['token']) && method_exists($request, 'setToken')) {
            $request->setToken($parameters['token']);
        }
    }

    /**
     * Check the status of a Payment Intent
     *
     * @param string $paymentIntentReference
     * @return array
     */
    public function checkPaymentIntentStatus($paymentIntentReference)
    {
        try {
            $fetchRequest = $this->fetchPaymentIntent(['paymentIntentReference' => $paymentIntentReference]);
            $fetchResponse = $fetchRequest->send();
            return $fetchResponse->getData();
        } catch (\Exception $e) {
            error_log('Stripe status check failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Handle Payment Intent response and update transaction accordingly
     *
     * @param \Omnipay\Common\Message\ResponseInterface $response
     * @param \Store\Model\Transaction $transaction
     * @return \Store\Model\Transaction
     */
    public function handlePaymentIntentResponse($response, $transaction)
    {
        if (!method_exists($response, 'getData')) {
            return $transaction;
        }

        $data = $response->getData();

        // Log payment intent data
        if (isset($data['id'])) {
            error_log("Payment intent data: " . json_encode($data));
        }

        if (!isset($data['status'])) {
            return $transaction;
        }

        error_log("Payment intent status: " . $data['status']);

        switch ($data['status']) {
            case 'requires_action':
            case 'requires_source_action':
            case 'requires_authentication':
                $transaction->status = \Store\Model\Transaction::REDIRECT;
                if (isset($data['id'])) {
                    $transaction->reference = $data['id'];
                }
                $this->handle3DSRedirect($data, $transaction, $response);
                break;

            case 'succeeded':
            case 'processing':
            case 'requires_capture':
                $transaction->status = \Store\Model\Transaction::SUCCESS;
                $this->setTransactionReference($data, $transaction);
                break;

            case 'requires_payment_method':
                $transaction->status = \Store\Model\Transaction::FAILED;
                $transaction->message = 'Payment method was declined or not provided.';
                break;
        }

        return $transaction;
    }

    /**
     * Handle 3D Secure redirect
     *
     * @param array $data
     * @param \Store\Model\Transaction $transaction
     * @param \Omnipay\Common\Message\ResponseInterface $response
     */
    protected function handle3DSRedirect($data, $transaction, $response)
    {
        if (isset($data['next_action']) &&
            isset($data['next_action']['type']) &&
            $data['next_action']['type'] === 'redirect_to_url' &&
            isset($data['next_action']['redirect_to_url']['url'])) {

            $transaction->message = 'redirect:' . $data['next_action']['redirect_to_url']['url'];
            error_log("Storing 3DS redirect URL: " . $data['next_action']['redirect_to_url']['url']);
        } elseif (method_exists($response, 'getRedirectUrl') && $response->getRedirectUrl()) {
            $transaction->message = 'redirect:' . $response->getRedirectUrl();
            error_log("Storing fallback redirect URL: " . $response->getRedirectUrl());
        } else {
            $transaction->message = 'Payment requires authentication, but no redirect URL was provided.';
            error_log("No redirect URL found for 3DS authentication.");
        }
    }

    /**
     * Set the transaction reference based on Payment Intent data
     *
     * @param array $data
     * @param \Store\Model\Transaction $transaction
     */
    protected function setTransactionReference($data, $transaction)
    {
        if (isset($data['latest_charge'])) {
            $transaction->reference = $data['latest_charge'];
            error_log("Using latest charge as reference: " . $data['latest_charge']);
        } else if (isset($data['id'])) {
            $transaction->reference = $data['id'];
            error_log("Using payment intent ID as reference: " . $data['id']);
        }
    }

    /**
     * Get the charge ID for refund reference
     *
     * @param \Omnipay\Common\Message\ResponseInterface $response
     * @return string|null
     */
    public function getChargeIdForRefund($response)
    {
        if (method_exists($response, 'getRequest') &&
            method_exists($response->getRequest(), 'getResponse')) {

            $responseData = $response->getRequest()->getResponse()->getData();
            $latestCharge = $responseData['latest_charge'] ?? null;

            if ($latestCharge !== null && substr($latestCharge, 0, 3) === 'ch_') {
                return $latestCharge;
            }
        }
        return null;
    }
}
