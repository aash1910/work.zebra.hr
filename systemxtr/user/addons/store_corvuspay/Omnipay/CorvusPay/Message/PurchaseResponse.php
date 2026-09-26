<?php

namespace Omnipay\CorvusPay\Message;

use Store\Dependency\Omnipay\Common\Message\AbstractResponse;
use Store\Dependency\Omnipay\Common\Message\RedirectResponseInterface;

/**
 * CorvusPay Purchase Response – always a redirect to the local relay
 * which then auto-POSTs the signed data to CorvusPay checkout.
 */
class PurchaseResponse extends AbstractResponse implements RedirectResponseInterface
{
    protected $prodEndpoint = 'https://wallet.corvuspay.com/checkout/';
    protected $testEndpoint = 'https://wallet.test.corvuspay.com/checkout/';

    public function __construct($request, $data)
    {
        parent::__construct($request, $data);

        if (!is_array($this->data)) {
            $this->data = (array) $this->data;
        }

        // Guarantee message is always a string (Store calls strpos on it)
        if (!array_key_exists('message', $this->data) || $this->data['message'] === null) {
            $this->data['message'] = '';
        } else {
            $this->data['message'] = (string) $this->data['message'];
        }
    }

    public function isSuccessful()
    {
        return false; // off-site: success is determined on return
    }

    public function isRedirect()
    {
        return true;
    }

    /**
     * Redirect the browser to the local relay action.
     * The relay renders a form that POSTs the signed payload to CorvusPay.
     */
    public function getRedirectUrl()
    {
        /** @var PurchaseRequest $request */
        $request  = $this->getRequest();
        $endpoint = $request->getTestMode()
            ? $this->testEndpoint
            : $this->prodEndpoint;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        // One-time token (valid ~5 minutes)
        $token = bin2hex(random_bytes(16));

        if (!isset($_SESSION['corvus_relay']) || !is_array($_SESSION['corvus_relay'])) {
            $_SESSION['corvus_relay'] = [];
        }

        // Store payload
        $_SESSION['corvus_relay'][$token] = [
            'data' => $this->getRedirectData(),
            'url'  => $endpoint,
            'ts'   => time(),
        ];

        // Aggressive cleanup of expired tokens (older than 5 min)
        $now = time();
        foreach ($_SESSION['corvus_relay'] as $tok => $rec) {
            if (!isset($rec['ts']) || $rec['ts'] < ($now - 300)) {
                unset($_SESSION['corvus_relay'][$tok]);
            }
        }

        // Build URL to the module action
        $actId = ee()->functions->fetch_action_id('Store_corvuspay', 'relay');
        $base  = rtrim(ee()->config->site_url(), '/');

        return $base . '/?ACT=' . $actId . '&t=' . $token;
    }

    public function getRedirectMethod()
    {
        // Store issues a 302 to the relay; the relay itself does the POST
        return 'GET';
    }

    public function getRedirectData()
    {
        return $this->data;
    }

    /**
     * Store expects a string from getMessage().
     */
    public function getMessage()
    {
        return (string) ($this->data['message'] ?? '');
    }
}
