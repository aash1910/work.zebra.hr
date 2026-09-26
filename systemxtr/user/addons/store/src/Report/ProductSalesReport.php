<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Report;

use Store\Dependency\Illuminate\Database\Query\Builder;
use Store\DateTime;
use Store\Model\OrderItem;

class ProductSalesReport extends AbstractReport
{
    protected $orderby = 'stock_id';

    public function default_options()
    {
        $now = DateTime::now(ee()->store->reports->timezone());

        return [
            'from' => ['type' => 'date_select', 'default' => $now->copy()->subDays(30)->format('Y-m-d')],
            'to'   => ['type' => 'date_select', 'default' => $now->format('Y-m-d')],
        ];
    }

    public function run()
    {
        $totals = ['revenue' => 0, 'count' => 0];

        $this->render->initialize($this->orderby, $this->sort);
        $this->render->table_open();
        $this->render->table_header([
            ['data' => lang('store.#'), 'orderby' => 'stock_id'],
            ['data' => lang('store.entry_id'), 'orderby' => 'entry_id'],
            ['data' => lang('store.reports.channel_id'), 'orderby' => 'channel_id'],
            ['data' => lang('store.reports.channel_name'), 'orderby' => 'channel_title'],
            ['data' => lang('store.sku'), 'orderby' => 'sku'],
            ['data' => lang('title'), 'orderby' => 'title'],
            ['data' => lang('store.reports.sales'), 'orderby' => 'count', 'class' => 'store_numeric'],
            ['data' => lang('store.revenue'), 'orderby' => 'revenue', 'class' => 'store_numeric'],
        ]);

        /** @var Builder $db */
        $db = ee()->store->db;
        /** @var Builder $query */
        $query = OrderItem::join('store_orders', 'store_orders.id', '=', 'store_order_items.order_id')
            ->leftJoin('channels', 'channels.channel_id', '=', 'store_order_items.channel_id')
            ->where('store_order_items.site_id', config_item('site_id'))
            ->where('store_orders.order_completed_date', '>', 0)
            ->select([
                'store_order_items.stock_id',
                'store_order_items.entry_id',
                'store_order_items.channel_id',
                'store_order_items.sku',
                'store_order_items.title',
                'channels.channel_title',
                $db->raw('sum(item_qty) as count'),
                $db->raw('sum(item_subtotal) as revenue'), ])
            ->groupBy('stock_id')
            ->groupBy('sku')
            ->groupBy('store_order_items.entry_id')
            ->groupBy('store_order_items.channel_id')
            ->groupBy('store_order_items.title')
            ->groupBy('channels.channel_title');

        if ($this->options['from']) {
            $query->where('order_date', '>=', $this->options['from']->timestamp);
        }

        if ($this->options['to']) {
            $query->where('order_date', '<', $this->options['to']->addDay()->timestamp);
        }

        if (in_array($this->orderby, ['stock_id', 'entry_id', 'channel_id', 'channel_title', 'sku', 'title', 'count', 'revenue'])) {
            $query->orderBy($this->orderby, $this->sort);
        }

        foreach ($query->get() as $row) {
            /* $member_name = $row->member_id ? $row->username : lang('store.reports.anonymous'); */
            /* $link = store_cp_url('reports', 'show', array_merge(array('report' => 'order_details', 'member_id' => $row->member_id ?: 'anonymous'), $this->options->all())); */

            $this->render->table_row([
                $row->stock_id,
                '<a href="/systemxtr/index.php?/cp/publish/edit/entry/' . $row->entry_id . '" target="_blank">' . $row->entry_id . '</a>',
                $row->channel_id,
                $row->channel_title,
                $row->sku,
                $row->title,
                ['class' => 'store_numeric', 'data' => $row->count],
                ['class' => 'store_numeric', 'data' => store_currency($row->revenue)],
            ]);

            $totals['revenue'] += $row->revenue;
            $totals['count'] += $row->count;
        }

        $this->render->table_footer([
            ['data' => lang('store.totals'), 'colspan' => 6],
            ['class' => 'store_numeric', 'data' => $totals['count']],
            ['class' => 'store_numeric', 'data' => store_currency($totals['revenue'])],
        ]);
        $this->render->table_close();
    }
}
