<?php

namespace Store\Gateways\Stripe\Message;

use Store\Dependency\Omnipay\Common\Message\AbstractResponse;
use Store\Dependency\Omnipay\Common\Message\RequestInterface;

/**
 * Stripe Response
 */
class Response extends AbstractResponse
{
    protected $statusCode;

    public function __construct(RequestInterface $request, $data, $statusCode = 200)
    {
        parent::__construct($request, $data);
        $this->statusCode = $statusCode;

        // Enhanced debug logging to help with troubleshooting
        $logData = is_array($data) ? json_encode($data) : $data;
        $hasError = isset($data['error']) ? ' [HAS ERROR]' : '';
        error_log('Stripe Response: status=' . $statusCode . $hasError . ', data=' . $logData);

        // Log specific error details if present
        if (isset($data['error'])) {
            error_log('Stripe Error Details: ' . json_encode($data['error']));
        }

        // Log payment intent status
        if (isset($data['status'])) {
            error_log('Stripe Payment Intent Status: ' . $data['status']);
        }
    }

    /**
     * Is the response successful?
     *
     * @return bool
     */
    public function isSuccessful()
    {
        // Some PaymentIntents require confirmation or action
        if (isset($this->data['status'])) {
            if ($this->data['status'] === 'requires_action' ||
                $this->data['status'] === 'requires_confirmation' ||
                $this->data['status'] === 'requires_source_action' ||
                $this->data['status'] === 'requires_payment_method' ||
                $this->data['status'] === 'requires_authentication') {
                error_log('Stripe PaymentIntent requires additional action: ' . $this->data['status']);
                return false;
            }

            if ($this->data['status'] === 'succeeded' ||
                $this->data['status'] === 'processing' ||
                $this->data['status'] === 'requires_capture') {
                error_log('Stripe PaymentIntent succeeded or processing: ' . $this->data['status']);
                return true;
            }
        }

        if (isset($this->data['error'])) {
            error_log('Stripe error: ' . json_encode($this->data['error']));
            return false;
        }

        return $this->statusCode < 400;
    }

    /**
     * Get the transaction reference
     *
     * @return string|null
     */
    public function getTransactionReference()
    {
        // For payment intents, use the payment intent ID
        if (isset($this->data['id']) && strpos($this->data['id'], 'pi_') === 0) {
            error_log('Using payment intent ID as transaction reference: ' . $this->data['id']);
            return $this->data['id'];
        }

        // For charges, use the charge ID
        if (isset($this->data['latest_charge']) && strpos($this->data['latest_charge'], 'ch_') === 0) {
            error_log('Using latest charge ID as transaction reference: ' . $this->data['latest_charge']);
            return $this->data['latest_charge'];
        }

        // For any object with an ID
        if (isset($this->data['id'])) {
            error_log('Using object ID as transaction reference: ' . $this->data['id']);
            return $this->data['id'];
        }

        return null;
    }

    /**
     * Get the error message from the response.
     *
     * @return string|null
     */
    public function getMessage()
    {
        if (isset($this->data['error']) && isset($this->data['error']['message'])) {
            return $this->data['error']['message'];
        }

        if (isset($this->data['status'])) {
            if ($this->data['status'] === 'requires_action' || $this->data['status'] === 'requires_source_action') {
                return 'Payment requires customer action to authenticate';
            } elseif ($this->data['status'] === 'requires_confirmation') {
                return 'Payment requires confirmation';
            } elseif ($this->data['status'] === 'requires_authentication') {
                return 'Payment requires authentication';
            } elseif ($this->data['status'] === 'requires_payment_method') {
                return 'Payment requires a payment method';
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isRedirect()
    {
        // Check if this response needs a redirect for 3D Secure
        if (isset($this->data['status']) &&
            ($this->data['status'] === 'requires_action' ||
             $this->data['status'] === 'requires_source_action' ||
             $this->data['status'] === 'requires_authentication')) {

            if (isset($this->data['next_action']) &&
                isset($this->data['next_action']['type']) &&
                $this->data['next_action']['type'] === 'redirect_to_url' &&
                isset($this->data['next_action']['redirect_to_url']['url'])) {
                error_log('3D Secure redirect required: ' . $this->data['next_action']['redirect_to_url']['url']);
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the redirect target url.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        if ($this->isRedirect() &&
            isset($this->data['next_action']['redirect_to_url']['url'])) {
            return $this->data['next_action']['redirect_to_url']['url'];
        }

        return null;
    }

    /**
     * Get the response data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Redirect the user to the 3DS URL
     */
    public function redirect()
    {
        $url = $this->getRedirectUrl();

        if ($url) {
            error_log('Redirecting to 3D Secure URL: ' . $url);
            header('Location: ' . $url);
            exit;
        } else {
            error_log('No redirect URL found in payment intent response');
        }
    }
}
