<?php

namespace Omnipay\CorvusPay\Message;

use Store\Dependency\Omnipay\Common\Message\AbstractResponse;

/**
 * CompletePurchaseResponse
 *
 * Distinguishes three outcomes:
 *  - Successful payment  → valid Corvus signature + approval_code
 *  - Cancelled by buyer  → order_number present, no approval_code
 *  - Other failure       → everything else
 *
 * Both success_url and cancel_url point to Store’s return action
 * (getReturnUrl), which is CSRF-safe. Store then calls this class.
 */
class CompletePurchaseResponse extends AbstractResponse
{
    /** @var bool */
    protected $signatureValid = false;

    public function __construct($request, $data)
    {
        parent::__construct($request, $data);

        if (!is_array($this->data)) {
            $this->data = (array) $this->data;
        }

        if (!array_key_exists('message', $this->data) || $this->data['message'] === null) {
            $this->data['message'] = '';
        } else {
            $this->data['message'] = (string) $this->data['message'];
        }

        $this->verifySignature();
    }

    /**
     * Verify signature using only the parameters that Corvus actually signs.
     * Strip ExpressionEngine / Store query params (ACT, H, etc.).
     */
    protected function verifySignature()
    {
        $receivedSig = isset($this->data['signature'])
            ? (string) $this->data['signature']
            : '';

        if ($receivedSig === '') {
            $this->signatureValid = false;
            return;
        }

        /** @var CompletePurchaseRequest $request */
        $request   = $this->getRequest();
        $secretKey = $request->getSecretKey();

        if (empty($secretKey)) {
            $this->signatureValid = false;
            return;
        }

        // Only keep fields that Corvus is known to return on success
        $corvusFields = [
            'order_number',
            'language',
            'approval_code',
            'ips_transaction_id',
            // add any other documented return fields here if needed
        ];

        $params = [];
        foreach ($corvusFields as $field) {
            if (isset($this->data[$field]) && $this->data[$field] !== '' && $this->data[$field] !== null) {
                $params[$field] = $this->data[$field];
            }
        }

        // Fallback: if we have almost nothing, try all non-EE params
        if (count($params) < 2) {
            $params = $this->data;
            unset($params['signature'], $params['ACT'], $params['H'], $params['csrf_token']);
        }

        $calculated = $request->calculateSignature($params, $secretKey);
        $this->signatureValid = (strcasecmp($calculated, $receivedSig) === 0);
    }

    /**
     * True only for a real approved payment.
     */
    public function isSuccessful()
    {
        if (!$this->signatureValid) {
            return false;
        }

        $approval = isset($this->data['approval_code'])
            ? trim((string) $this->data['approval_code'])
            : '';

        return $approval !== '';
    }

    /**
     * Buyer clicked Cancel on the Corvus page (or payment was otherwise not completed).
     */
    public function isCancelled()
    {
        // Cancel redirect from Corvus contains order_number + language, no approval_code, no signature
        $hasOrder    = !empty($this->data['order_number']);
        $hasApproval = !empty($this->data['approval_code']);

        return $hasOrder && !$hasApproval;
    }

    public function isSignatureValid()
    {
        return $this->signatureValid;
    }

    public function getTransactionReference()
    {
        if (!empty($this->data['order_number'])) {
            return (string) $this->data['order_number'];
        }
        if (!empty($this->data['ShoppingCartID'])) {
            return (string) $this->data['ShoppingCartID'];
        }
        return null;
    }

    /**
     * Store matches this value against exp_store_transactions.id, so it must
     * be the pure numeric transaction id - never the "{cart order_id}-{id}"
     * display string that PurchaseRequest sends to CorvusPay as order_number.
     * If a "-" is present, take everything after the last one (the id is
     * always appended last, so this is safe even if the cart order_id
     * itself contains hyphens).
     */
    public function getTransactionId()
    {
        $reference = $this->getTransactionReference();

        if ($reference !== null && strpos($reference, '-') !== false) {
            $parts = explode('-', $reference);
            return (string) end($parts);
        }

        return $reference;
    }

    public function getMessage()
    {
        if ($this->isSuccessful()) {
            return 'Approved';
        }

        if ($this->isCancelled()) {
            return 'Cancelled by buyer';
        }

        if (!empty($this->data['signature']) && !$this->signatureValid) {
            return 'Invalid signature';
        }

        return (string) ($this->data['message'] ?? 'Payment not completed');
    }

    public function getCode()
    {
        if (!empty($this->data['approval_code'])) {
            return (string) $this->data['approval_code'];
        }
        return null;
    }
}
