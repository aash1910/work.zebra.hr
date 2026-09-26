<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Report;

use Store\Dependency\Illuminate\Database\Query\Builder;
use Store\DateTime;
use Store\Model\OrderItem;

class OrderItemsReport extends AbstractReport
{
    protected $orderby = 'order_date';
    protected $totals;

    public function default_options()
    {
        $now = DateTime::now(ee()->store->reports->timezone());
        $options = [
            'from'      => ['type' => 'date_select', 'default' => $now->copy()->subDays(30)->format('Y-m-d')],
            'to'        => ['type' => 'date_select', 'default' => $now->format('Y-m-d')],
            'member_id' => ['type' => 'select'],
        ];

        $options['member_id']['options'] = ['' => lang('store.any'), 'anonymous' => lang('store.reports.anonymous')] + ee()->store->reports->member_select_options();

        return $options;
    }

    public function run()
    {
        $this->totals = [
            'item_qty'      => 0,
            'item_subtotal' => 0,
            'item_discount' => 0,
            'item_tax'      => 0,
            'item_total'    => 0,
        ];

        $this->render->initialize($this->orderby, $this->sort);
        $this->render->table_open();
        $this->render->table_header([
            ['data' => lang('store.#'), 'orderby' => 'id'],
            ['data' => lang('store.order_id'), 'orderby' => 'order_id'],
            ['data' => lang('store.order_date'), 'orderby' => 'order_date'],
            ['data' => lang('store.reports.member_id'), 'orderby' => 'member_id'],
            ['data' => lang('store.reports.member_name'), 'orderby' => 'screen_name'],
            ['data' => lang('store.reports.channel_id'), 'orderby' => 'channel_id'],
            ['data' => lang('store.reports.channel_name'), 'orderby' => 'channel_title'],
            ['data' => lang('store.entry_id'), 'orderby' => 'entry_id'],
            ['data' => lang('store.stock_id'), 'orderby' => 'stock_id'],
            ['data' => lang('store.sku'), 'orderby' => 'sku'],
            ['data' => lang('title'), 'orderby' => 'title'],
            ['data' => 'Kategorije'],
            ['data' => lang('store.modifiers')],
            ['data' => lang('store.price'), 'orderby' => 'price', 'class' => 'store_numeric'],
            ['data' => lang('store.quantity'), 'orderby' => 'item_qty', 'class' => 'store_numeric'],
            ['data' => lang('store.reports.subtotal'), 'orderby' => 'item_subtotal', 'class' => 'store_numeric'],
            ['data' => lang('store.reports.discount'), 'orderby' => 'item_discount', 'class' => 'store_numeric'],
            ['data' => lang('store.reports.tax'), 'orderby' => 'item_tax', 'class' => 'store_numeric'],
            ['data' => lang('store.total'), 'orderby' => 'item_total', 'class' => 'store_numeric'],
        ]);

        /** @var Builder $query */
        $query = OrderItem::join('store_orders', 'store_orders.id', '=', 'store_order_items.order_id')
            ->leftJoin('members', 'members.member_id', '=', 'store_orders.member_id')
            ->leftJoin('channels', 'channels.channel_id', '=', 'store_order_items.channel_id')
            ->where('store_order_items.site_id', config_item('site_id'))
            ->where('store_orders.order_completed_date', '>', 0)
            ->select(['store_order_items.*', 'store_orders.order_date', 'store_orders.member_id', 'members.screen_name', 'channels.channel_title']);

        if ($this->options['from']) {
            $query->where('store_orders.order_date', '>=', $this->options['from']->timestamp);
        }

        if ($this->options['to']) {
            $query->where('store_orders.order_date', '<', $this->options['to']->addDay()->timestamp);
        }

        if ($this->options['member_id'] === 'anonymous') {
            $query->whereNull('store_orders.member_id');
        } elseif ($this->options['member_id'] > 0) {
            $query->where('store_orders.member_id', $this->options['member_id']);
        }

        if ($this->orderby === 'id') {
            $query->orderBy('store_order_items.id', $this->sort);
        } elseif (in_array($this->orderby, ['order_id', 'order_date', 'member_id',
            'screen_name', 'channel_id', 'channel_title', 'entry_id', 'stock_id', 'sku',
            'title', 'price', 'item_qty', 'item_subtotal', 'item_discount', 'item_tax',
            'item_total', ])) {
            $query->orderBy($this->orderby, $this->sort);
        }

        $self = $this;
        $query->chunk(1000, function ($chunk) use ($self) {
            foreach ($chunk as $item) {
                $self->run_item($item);
            }
        });

        $this->render->table_footer([
            ['data' => lang('store.totals'), 'colspan' => 13],
            ['class' => 'store_numeric', 'data' => $this->totals['item_qty']],
            ['class' => 'store_numeric', 'data' => store_currency($this->totals['item_subtotal'])],
            ['class' => 'store_numeric', 'data' => store_currency($this->totals['item_discount'])],
            ['class' => 'store_numeric', 'data' => store_currency($this->totals['item_tax'])],
            ['class' => 'store_numeric', 'data' => store_currency($this->totals['item_total'])],
        ]);
        $this->render->table_close();
    }

    public function run_item($item)
    {
        $member_name = $item->member_id ? $item->screen_name : lang('store.reports.anonymous');

        $kategorije_result = ee()->db->select('a.cat_id as cat_id, a.entry_id, b.cat_name as cat_name')
            ->from('category_posts a')
            ->join('categories b', 'b.cat_id = a.cat_id', 'left')
            ->where('a.entry_id', $item->entry_id)
            ->where('b.group_id', 3)
            ->order_by('a.cat_id', 'ASC')
            ->get()->result_array();

        $kategorije = '';
        foreach ($kategorije_result as $kategorije_row) {
            $kategorije .= $kategorije_row['cat_name'] . '</br>';
        }

        $row = [
            $item->id,
            $item->order_id,
            ee()->localize->human_time($item->order_date, ee()->store->reports->timezone()),
            $item->member_id ?: null, // display 0 as empty
            e($member_name),
            $item->channel_id,
            $item->channel_title,
            $item->entry_id,
            $item->stock_id,
            $item->sku,
            $item->title,
            $kategorije,
            $item->modifiers_html,
            ['class' => 'store_numeric', 'data' => store_currency($item->price)],
            ['class' => 'store_numeric', 'data' => $item->item_qty],
            ['class' => 'store_numeric', 'data' => store_currency($item->item_subtotal)],
            ['class' => 'store_numeric', 'data' => store_currency($item->item_discount)],
            ['class' => 'store_numeric', 'data' => store_currency($item->item_tax)],
            ['class' => 'store_numeric', 'data' => store_currency($item->item_total)],
        ];

        foreach ($this->totals as $key => $value) {
            $this->totals[$key] += $item->$key;
        }

        $this->render->table_row($row);
    }
}