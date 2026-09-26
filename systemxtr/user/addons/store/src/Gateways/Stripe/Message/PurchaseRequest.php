<?php

namespace Store\Gateways\Stripe\Message;

/**
 * Stripe Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{
    /**
     * @return array|mixed
     * @throws \Store\Dependency\Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData()
    {
        // Log request parameters for debugging
        error_log('Stripe Payment Intent request params: ' . json_encode($this->getParameters()));

        $data = [
            'amount' => $this->getAmountInteger(),
            'currency' => strtolower($this->getCurrency()),
            'description' => $this->getDescription(),
            'metadata' => [
                'transactionId' => $this->getTransactionId(),
                'orderId' => $this->getParameter('orderId'),
            ],
            'confirmation_method' => 'automatic',
            'confirm' => true,
        ];

        // Get the payment method ID - try multiple sources
        $paymentMethod = null;

        // First try the payment_method parameter
        if ($this->getParameter('payment_method')) {
            $paymentMethod = $this->getParameter('payment_method');
            error_log('Found payment method in payment_method parameter: ' . $paymentMethod);
        }

        // Then try getPaymentMethod() method
        if (!$paymentMethod && method_exists($this, 'getPaymentMethod')) {
            $paymentMethod = $this->getPaymentMethod();
            if ($paymentMethod) {
                error_log('Found payment method from getPaymentMethod(): ' . $paymentMethod);
            }
        }

        // Finally try the token parameter
        if (!$paymentMethod && $this->getParameter('token')) {
            $paymentMethod = $this->getParameter('token');
            if ($paymentMethod) {
                error_log('Found payment method in token parameter: ' . $paymentMethod);
            }
        }

        // If we have a payment method ID, include it
        if ($paymentMethod) {
            error_log('Using payment method ID: ' . $paymentMethod);
            $data['payment_method'] = $paymentMethod;
        } else {
            error_log('ERROR: No payment method ID found for Stripe payment intent');
        }

        // Handle return URL for 3D Secure redirects
        if ($this->getReturnUrl()) {
            $data['return_url'] = $this->getReturnUrl();
        }

        error_log('Stripe Payment Intent data: ' . json_encode($data));

        return $data;
    }

    /**
     * @param mixed $data
     * @return \Store\Dependency\Omnipay\Common\Message\ResponseInterface|\Store\Gateways\Stripe\Message\Response
     */
    public function sendData($data)
    {
        $headers = $this->getHeaders();

        // Set Content-Type to application/x-www-form-urlencoded as required by Stripe
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        // Prepare parameters - convert boolean values to strings that Stripe accepts
        $params = [];
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $params[$key] = $value ? 'true' : 'false';
            } elseif (is_array($value)) {
                foreach ($value as $k => $v) {
                    $params[$key.'['.$k.']'] = $v;
                }
            } else {
                $params[$key] = $value;
            }
        }

        $body = http_build_query($params, '', '&');

        error_log('Stripe Request URL-encoded: ' . $body);
        error_log('Stripe Request Headers: ' . json_encode($headers));

        $response = $this->httpClient->request(
            'POST',
            $this->getEndpoint() . '/payment_intents',
            $headers,
            $body
        );

        $responseBody = (string) $response->getBody()->getContents();
        $responseData = json_decode($responseBody, true);

        error_log('Stripe Response Status: ' . $response->getStatusCode());
        error_log('Stripe Response: ' . $responseBody);

        return $this->createResponse($responseData, $response->getStatusCode());
    }

    /**
     * Get the payment method
     *
     * @return string|null
     */
    public function getPaymentMethod()
    {
        return $this->getParameter('payment_method');
    }

    /**
     * Set the payment method
     *
     * @param string $value
     * @return $this
     */
    public function setPaymentMethod($value)
    {
        error_log('Setting payment method: ' . $value);
        return $this->setParameter('payment_method', $value);
    }
}
