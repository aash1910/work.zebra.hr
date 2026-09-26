<?php

namespace Omnipay\CorvusPay\Message;

use Store\Dependency\Omnipay\Common\Message\AbstractRequest;

/**
 * CorvusPay Purchase Request (off-site redirect via form POST)
 *
 * Based on the proven working version, with safe clean-ups:
 * - order_number = pure transaction ID
 * - no DB lookup / no cookies
 * - phone removed
 * - fields truncated to manual limits
 * - only non-empty optional cardholder fields are sent
 */
class PurchaseRequest extends AbstractRequest
{
    // --- Passthrough get/set from Gateway ---

    public function getStoreId()
    {
        return $this->getParameter('storeId');
    }

    public function setStoreId($value)
    {
        return $this->setParameter('storeId', $value);
    }

    public function getSecretKey()
    {
        return $this->getParameter('secretKey');
    }

    public function setSecretKey($value)
    {
        return $this->setParameter('secretKey', $value);
    }

    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }

    public function getRequireComplete()
    {
        return (bool) $this->getParameter('require_complete');
    }

    public function setRequireComplete($value)
    {
        return $this->setParameter('require_complete', (bool) $value);
    }

    public function getTestMode()
    {
        return (bool) $this->getParameter('testMode');
    }

    public function setTestMode($value)
    {
        return $this->setParameter('testMode', (bool) $value);
    }

    public function getData()
    {
        $this->validate('amount', 'transactionId');

        $store_id = $this->getStoreId();
        $key      = $this->getSecretKey();

        if (empty($store_id) || empty($key)) {
            throw new \Store\Dependency\Omnipay\Common\Exception\InvalidRequestException(
                'Missing storeId or secretKey for CorvusPay'
            );
        }

        $version = '1.6';

        // Human-friendly order id: "{cart order_id}-{transaction id}"
        // Falls back to the pure transaction ID if the cart order_id can't be found.
        $order_id = $this->buildOrderId();

        $require_complete = $this->getRequireComplete() ? 'true' : 'false';

        // Amount with decimal point and exactly 2 decimals
        $amount = number_format((float) $this->getAmount(), 2, '.', '');

        // Language: sr → rs, lowercase
        $language = $this->getLanguage() ?: 'hr';
        if ($language === 'sr') {
            $language = 'rs';
        }
        $language = strtolower($language);

        // ISO 3166-1 alpha-2
        $card = $this->getCard();
        $country_code = 'HR';
        if ($card && $card->getCountry()) {
            $cc = strtoupper((string) $card->getCountry());
            if (strlen($cc) === 2) {
                $country_code = $cc;
            }
        }

        // Cart description ≤ 255 (same style as working version)
        $cart = 'Narudzba: ' . $order_id . ' Proizvod:';
        $items = $this->getItems();
        if ($items) {
            $chunks = [];
            foreach ($items as $item) {
                $chunks[] = $item->getName() . 'x' . $item->getQuantity();
            }
            $cart .= implode('', $chunks);
        }
        $cart = $this->truncate($cart, 255);

        // ---- Build data exactly like the working version ----
        $data = [
            'version'                 => $version,
            'store_id'                => $store_id,
            'order_number'            => $order_id,
            'language'                => $language,
            'currency'                => 'EUR',
            'amount'                  => $amount,
            'cart'                    => $cart,
            'require_complete'        => $require_complete,
            'cardholder_country_code' => $country_code,
            'payment_all'             => 'Y0299',
            'payment_all_dynamic'     => 'true',
        ];

        // Optional cardholder fields – only when non-empty, truncated
        if ($card) {
            $name = $this->truncate((string) $card->getFirstName(), 40);
            if ($name !== '') {
                $data['cardholder_name'] = $name;
            }

            $surname = $this->truncate((string) $card->getLastName(), 40);
            if ($surname !== '') {
                $data['cardholder_surname'] = $surname;
            }

            $address = trim(
                ((string) $card->getAddress1()) . ' ' . ((string) $card->getAddress2())
            );
            $address = $this->truncate($address, 100);
            if ($address !== '') {
                $data['cardholder_address'] = $address;
            }

            $city = $this->truncate((string) $card->getCity(), 20);
            if ($city !== '') {
                $data['cardholder_city'] = $city;
            }

            $zip = $this->truncate((string) $card->getPostcode(), 9);
            if ($zip !== '') {
                $data['cardholder_zip_code'] = $zip;
            }

            // Keep the same behaviour as the working version:
            // cardholder_country receives the ISO-2 code
            $data['cardholder_country'] = $country_code;

            $email = $this->truncate((string) $card->getEmail(), 100);
            if ($email !== '') {
                $data['cardholder_email'] = $email;
            }
        }

        // Both success and cancel must go to Store's return action (getReturnUrl).
        // That endpoint is CSRF-safe. CompletePurchaseResponse then decides
        // success vs cancel based on presence of approval_code.
        $returnUrl = $this->getReturnUrl();
        if ($returnUrl !== null && $returnUrl !== '') {
            $data['success_url'] = $this->truncate((string) $returnUrl, 200);
            $data['cancel_url']  = $this->truncate((string) $returnUrl, 200);
        }

        // ---- Signature (identical algorithm to the working version) ----
        $data['signature'] = $this->calculateSignature($data, $key);

        return $data;
    }

    public function sendData($data)
    {
        return $this->response = new PurchaseResponse($this, $data);
    }

    /**
     * Builds a human-friendly order id of the form "{cart order_id}-{transaction id}"
     * so the store owner can recognize the order on CorvusPay without having to
     * cross-reference the raw transaction id.
     *
     * Falls back to the pure transaction id if the cart order_id lookup fails
     * for any reason (missing row, DB not available, etc.), so payments never
     * break because of this cosmetic change.
     */
    protected function buildOrderId()
    {
        $transaction_id = (string) $this->getTransactionId();

        if (function_exists('ee') && $transaction_id !== '') {
            try {
                $row = ee()->db
                    ->select('order_id')
                    ->get_where('exp_store_transactions', array('id' => $transaction_id))
                    ->row();

                if ($row && isset($row->order_id) && (string) $row->order_id !== '') {
                    return $row->order_id . '-' . $transaction_id;
                }
            } catch (\Exception $e) {
                // Swallow and fall back below - never let this break checkout.
            }
        }

        return $transaction_id;
    }

    /**
     * Identical to the working version + the official manual example.
     */
    public function calculateSignature(array $data, $secretKey)
    {
        $filtered = [];
        foreach ($data as $k => $v) {
            if ($v !== null && $v !== '') {
                $filtered[$k] = $v;
            }
        }

        ksort($filtered, SORT_STRING);

        $signatureString = '';
        foreach ($filtered as $k => $v) {
            $val = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            if (function_exists('mb_check_encoding') && !mb_check_encoding($val, 'UTF-8')) {
                $val = mb_convert_encoding($val, 'UTF-8', 'auto');
            }
            $signatureString .= $k . $val;
        }

        $signature = hash_hmac('sha256', $signatureString, (string) $secretKey);

        // Debug log only in test mode
        if ($this->getTestMode()) {
            $logFile = defined('PATH_CACHE') ? PATH_CACHE . 'corvuspay_signature_debug.log' : sys_get_temp_dir() . '/corvuspay_signature_debug.log';
            $log = date('c') . "\n"
                 . "Signature string:\n" . $signatureString . "\n"
                 . "Calculated signature: " . $signature . "\n"
                 . "Parameters:\n" . print_r($filtered, true) . "\n"
                 . str_repeat('-', 60) . "\n";
            @file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
        }

        return $signature;
    }

    protected function truncate($value, $max)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max, 'UTF-8');
        }
        return substr($value, 0, $max);
    }
}
