<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Transformer;

use Store\Model\Order;
use Store\Model\Transaction;

/**
 * This is a Sage Pay data transformer that will add in necessary request data that Sage Pay expects
 */
class SagePayTransformer extends AbstractTransformer
{
    /**
     * @return array $request
     */
    public function transform(Transaction $transaction)
    {
        return [
            'VendorTxCode'       => $transaction->order->id,
            'SuccessURL'         => ee()->store->store->get_action_url('act_payment_return') . '&H=' . $transaction->hash,
            'FailureURL'         => ee()->store->store->get_action_url('act_payment_return') . '&H=' . $transaction->hash,
            'NotificationURL'    => ee()->store->store->get_action_url('act_notification_handler') . '&H=' . $transaction->hash,
            'BillingSurname'     => $transaction->order->billing_last_name,
            'BillingFirstnames'  => $transaction->order->billing_first_name,
            'BillingAddress1'    => $transaction->order->billing_address1,
            'BillingAddress2'    => $transaction->order->billing_address2,
            'BillingCity'        => $transaction->order->billing_city,
            'BillingPostCode'    => $transaction->order->billing_postcode,
            'BillingCountry'     => $transaction->order->billing_country,
            'BillingState'       => $transaction->order->billing_state,
            'BillingPhone'       => $transaction->order->billing_phone,
            'DeliverySurname'    => $transaction->order->shipping_last_name,
            'DeliveryFirstnames' => $transaction->order->shipping_first_name,
            'DeliveryAddress1'   => $transaction->order->shipping_address1,
            'DeliveryAddress2'   => $transaction->order->shipping_address2,
            'DeliveryCity'       => $transaction->order->shipping_city,
            'DeliveryPostCode'   => $transaction->order->shipping_postcode,
            'DeliveryCountry'    => $transaction->order->shipping_country,
            'DeliveryState'      => $transaction->order->shipping_state,
            'DeliveryPhone'      => $transaction->order->shipping_phone,
        ];
    }

    /**
     * @return array $request
     */
    public function cardTransform(Order $order)
    {
        return [
          'BillingSurname' => $order->billing_last_name,
        ];
    }
}
