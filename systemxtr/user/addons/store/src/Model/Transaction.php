<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Model;

use Store\Dependency\Omnipay\Common\AbstractGateway;
use Store\Exception\CartException;

class Transaction extends AbstractModel
{
    public const AUTHORIZE = 'authorize';
    public const CAPTURE = 'capture';
    public const PURCHASE = 'purchase';
    public const REFUND = 'refund';
    public const PENDING = 'pending';
    public const REDIRECT = 'redirect';
    public const SUCCESS = 'success';
    public const FAILED = 'failed';
    public const CONFIRMATION_REQUIRED = 'confirmation_required';

    protected $table = 'store_transactions';

    public function __construct(array $attributes = [])
    {
        // generate unique hash
        $this->hash = md5(uniqid(mt_rand(), true));

        parent::__construct($attributes);
    }

    public function order()
    {
        return $this->belongsTo('\Store\Model\Order');
    }

    public function member()
    {
        return $this->belongsTo('\Store\Model\Member');
    }

    public function parent()
    {
        return $this->belongsTo('\Store\Model\Transaction', 'parent_id');
    }

    public function children()
    {
        return $this->hasMany('\Store\Model\Transaction', 'parent_id');
    }

    public function canCapture()
    {
        // can only capture authorize payments
        if ($this->type != static::AUTHORIZE || $this->status != static::SUCCESS) {
            return false;
        }

        // check gateway supports capture
        try {
            /** @var AbstractGateway $gateway */
            $gateway = ee()->store->payments->load_payment_method($this->payment_method);
            if (!$gateway->supportsCapture()) {
                return false;
            }
        } catch (OmnipayException $e) {
            return false;
        } catch (CartException $e) {
            return false;
        }

        // check transaction hasn't already been captured
        return $this->children()
                ->where('type', static::CAPTURE)
                ->where('status', static::SUCCESS)
                ->count() == 0;
    }

    public function canRefund()
    {
        // can only refund purchase or capture transactions
        if (!in_array($this->type, [static::PURCHASE, static::CAPTURE]) ||
            $this->status != static::SUCCESS) {
            return false;
        }

        // check gateway supports refund
        try {
            /** @var AbstractGateway $gateway */
            $gateway = ee()->store->payments->load_payment_method($this->payment_method);
            if (!$gateway->supportsRefund()) {
                return false;
            }
        } catch (OmnipayException $e) {
            return false;
        } catch (CartException $e) {
            return false;
        }

        // check transaction hasn't already been refunded
        return $this->children()
                ->where('type', static::REFUND)
                ->where('status', static::SUCCESS)
                ->count() == 0;
    }
}
