<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Tag;

use Store\Model\Collection;

class OrdersTag extends AbstractTag
{
    public function parse()
    {
        /** @var Collection $orders */
        $orders = $this->get_orders_query()->get();
        $tag_vars = $orders->toTagArray();

        if (empty($tag_vars[0])) {
            return $this->no_results('no_orders');
        }

        $out = $this->parse_variables($tag_vars);

        return $out . $this->track_conversion($tag_vars);
    }
}
