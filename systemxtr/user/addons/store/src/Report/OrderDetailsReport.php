<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Report;

use Store\Dependency\Illuminate\Database\Query\Builder;
use Store\DateTime;
use Store\Model\Order;

class OrderDetailsReport extends AbstractReport
{
    protected $currency_totals;
    protected $total_order_qty;
    protected $order_fields;

    public function default_options()
    {
        $now = DateTime::now(ee()->store->reports->timezone());
        $options = [
            'from'      => ['type' => 'date_select', 'default' => $now->copy()->subDays(30)->format('Y-m-d')],
            'to'        => ['type' => 'date_select', 'default' => $now->format('Y-m-d')],
            'status'    => ['type' => 'select', 'options' => ['' => lang('store.any')]],
            'member_id' => ['type' => 'select'],
        ];

        $order_status_select_options = ee()->store->orders->order_statuses();
        foreach ($order_status_select_options as $option) {
            $options['status']['options'][$option->name] = store_order_status_name($option->name);
        }

        $options['member_id']['options'] =
            ['' => lang('store.any'), 'anonymous' => lang('store.reports.anonymous')] +
            ee()->store->reports->member_select_options();

        return $options;
    }

    public function run()
    {
        $this->order_fields = $this->get_order_fields();
        $this->currency_totals = [
            'order_subtotal' => 0,
            'order_discount' => 0,
            'order_shipping' => 0,
            'order_tax'      => 0,
            'order_total'    => 0,
            'order_paid'     => 0,
            'order_owing'    => 0,
        ];
        $this->total_order_qty = 0;

        $this->render->initialize($this->orderby, $this->sort);
        $this->render->table_open();
        $this->run_header();

        /** @var Builder $query */
        $query = Order::leftJoin('members', 'members.member_id', '=', 'store_orders.member_id')
            ->where('site_id', config_item('site_id'))
            ->where('order_completed_date', '>', 0)
            ->select(['store_orders.*', 'members.screen_name'])
            ->orderBy('order_date');

        if ($this->options['from']) {
            $query->where('order_date', '>=', $this->options['from']->timestamp);
        }

        if ($this->options['to']) {
            $query->where('order_date', '<', $this->options['to']->addDay()->timestamp);
        }

        if ($this->options['status']) {
            $query->where('order_status_name', $this->options['status']);
        }

        if ($this->options['member_id'] === 'anonymous') {
            $query->whereNull('store_orders.member_id');
        } elseif ($this->options['member_id'] > 0) {
            $query->where('store_orders.member_id', $this->options['member_id']);
        }

        $self = $this;
        $query->chunk(1000, function ($chunk) use ($self) {
            foreach ($chunk as $order) {
                $self->run_order($order);
            }
        });

        $this->run_footer();
        $this->render->table_close();
    }

    protected function run_header()
    {
        $row = [
            lang('store.#'),
            lang('store.order_date'),
            lang('store.status'),
            lang('store.reports.member_id'),
            lang('store.reports.member_name'),
        ];

        foreach ($this->order_fields as $title) {
            $row[] = $title;
        }

        $row[] = lang('store.promo_code');
        $row[] = lang('store.order_qty');

        foreach ($this->currency_totals as $key => $value) {
            $row[] = lang("store.$key");
        }

        $this->render->table_header($row);
    }

    public function run_order($order)
    {
        $member_name = $order->member_id ? $order->screen_name : lang('store.reports.anonymous');

        $row = [
            $order->id,
            ee()->localize->human_time($order->order_date, ee()->store->reports->timezone()),
            store_order_status_name($order->order_status_name),
            $order->member_id ?: null, // display 0 as empty
            e($member_name),
        ];

        foreach ($this->order_fields as $key => $title) {
            $row[] = $order->$key;
        }

        $row[] = $order->promo_code;
        $row[] = $order->order_qty;
        $this->total_order_qty += $order->order_qty;

        foreach ($this->currency_totals as $key => $value) {
            $row[] = store_currency($order->$key);
            $this->currency_totals[$key] += $order->$key;
        }

        $this->render->table_row($row);
    }

    protected function run_footer()
    {
        $row = [];
        $row[] = ['data' => lang('store.totals'), 'colspan' => 6 + count($this->order_fields)];
        $row[] = $this->total_order_qty;
        $row = array_merge($row, array_map('store_currency', $this->currency_totals));

        $this->render->table_footer($row);
    }

    protected function get_order_fields()
    {
        $order_fields = [];

        foreach (ee()->store->config->order_fields() as $name => $field) {
            $key = str_replace(['state', 'country'], ['state_name', 'country_name'], $name);

            if (isset($field['title'])) {
                if ($field['title']) {
                    $order_fields[$key] = $field['title'];
                }
            } else {
                $order_fields[$key] = lang('store.' . $name);
            }
        }

        return $order_fields;
    }
}
