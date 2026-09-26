<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Action;

use Store\Model\Transaction;

class PaymentReturnAction extends AbstractAction
{
    public function perform()
    {
        ee()->load->library('logger');
        $hash =ee()->input->get_post('H');

        ee()->logger->developer('PaymentReturnAction for hash: ' . $hash);

        $transaction = Transaction::where('site_id', config_item('site_id'))
            ->where('hash', (string) ee()->input->get_post('H'))
            ->first();

        if (empty($transaction)) {
            ee()->logger->developer('Transaction not found');
            show_error(lang('store.error_processing_order'));
        }

        ee()->logger->developer('Transaction exists with hash ' . $hash);

        ee()->store->payments->complete_payment($transaction);
    }
}
