<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Action;

use Store\Model\Transaction;

class NotificationHandlerAction extends AbstractAction
{
    public function perform()
    {
        $transaction = Transaction::where('site_id', config_item('site_id'))
            ->where('hash', (string)ee()->input->get_post('H'))
            ->first();

        if (empty($transaction)) {
            show_error(lang('store.error_processing_order'));
        }

        ee()->store->payments->notification_handler($transaction);
    }
}
