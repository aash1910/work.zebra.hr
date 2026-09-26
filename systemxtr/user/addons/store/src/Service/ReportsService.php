<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Service;

use Store\Dependency\Carbon\Carbon;
use DateTimeZone;
use Store\DateTime;
use Store\Model\Order;

class ReportsService extends AbstractService
{
    public function get_reports()
    {
        $reports = [
            'customer_summary' => '\Store\Report\CustomerSummaryReport',
            'inventory'        => '\Store\Report\InventoryReport',
            'order_details'    => '\Store\Report\OrderDetailsReport',
            'order_items'      => '\Store\Report\OrderItemsReport',
            'product_sales'    => '\Store\Report\ProductSalesReport',
            'revenue_summary'  => '\Store\Report\RevenueSummaryReport',
        ];

        if (ee()->extensions->active_hook('store_reports')) {
            $reports = ee()->extensions->call('store_reports', $reports);
        }

        ksort($reports);

        return $reports;
    }

    public function timezone()
    {
        $timezone = config_item('store_reporting_timezone');
        if (!in_array($timezone, DateTimeZone::listIdentifiers())) {
            // timezone stored in old EE format...
            $timezone = ee()->localize->get_php_timezone($timezone);
        }
        return $timezone;
    }

    public function month_select_options()
    {
        $now = DateTime::now($this->timezone());
        $min_order_date = Order::where('order_date', '>', 0)->min('order_date') ?: $now->timestamp;
        $month = DateTime::createFromTimeStamp($min_order_date, $this->timezone())->startOfMonth();

        $options = [];
        while ($month <= $now) {
            $options[$month->formatEE('%Y-%m')] = $month->formatEE('%F %Y');
            $month->addMonth();
        }

        return $options;
    }

    public function member_select_options()
    {
        $query = ee()->store->db
            ->table('members')
            ->select(['members.member_id', 'members.screen_name'])
            ->join('store_orders', 'store_orders.member_id', '=', 'members.member_id')
            ->where('order_completed_date', '>', 0)
            ->groupBy('members.member_id');

        $members = [];
        foreach ($query->get() as $row) {
            $members[$row->member_id] = $row->screen_name;
        }

        return $members;
    }

    public function get_dashboard_stats($period)
    {
        $period = (int)$period;

        // current period
        $current = ee()->store->db->table('store_orders')
            ->select(
                [
                    ee()->store->db->raw('COALESCE(SUM(order_total), 0) AS `revenue`'),
                    ee()->store->db->raw('COUNT(id) AS `orders`'),
                    ee()->store->db->raw('COALESCE(SUM(order_qty), 0) AS `items`'),
                    ee()->store->db->raw('CASE COUNT(id) WHEN 0 THEN 0 ELSE SUM(order_total)/COUNT(id) END AS `average_order`'),
                ]
            )->where('site_id', config_item('site_id'))
            ->where('order_completed_date', '>=', ee()->store->db->raw("UNIX_TIMESTAMP(DATE(NOW() - INTERVAL $period DAY))"))
            ->where('order_completed_date', '<', ee()->store->db->raw('UNIX_TIMESTAMP(DATE(NOW()))'))
            ->first();

        // previous period
        $previous_days = $period * 2;
        $previous = ee()->store->db->table('store_orders')
            ->select(
                [
                    ee()->store->db->raw('SUM(order_total) AS `prev_revenue`'),
                    ee()->store->db->raw('COUNT(id) AS `prev_orders`'),
                    ee()->store->db->raw('SUM(order_qty) AS `prev_items`'),
                    ee()->store->db->raw('SUM(order_total)/COUNT(id) AS `prev_average_order`'),
                ]
            )->where('site_id', config_item('site_id'))
            ->where('order_completed_date', '>=', ee()->store->db->raw("UNIX_TIMESTAMP(DATE(NOW() - INTERVAL $previous_days DAY))"))
            ->where('order_completed_date', '<', ee()->store->db->raw("UNIX_TIMESTAMP(DATE(NOW() - INTERVAL $period DAY))"))
            ->first();
            // Convert stdClass objects to arrays
    $current = (array) $current;
    $previous = (array) $previous;

        return array_merge($current, $previous);
    }

    public function get_dashboard_graph_data($period)
    {
        $period = (int)$period;

        // for now dashboard data is grouped by timezone of mysql server
        $totals = ee()->store->db->table('store_orders')
            ->select([
                ee()->store->db->raw('DATE(FROM_UNIXTIME(`order_completed_date`)) AS `date`'),
                ee()->store->db->raw('SUM(order_total) AS `total`'),
            ])
            ->where('site_id', config_item('site_id'))
            ->where('order_completed_date', '>=', ee()->store->db->raw("UNIX_TIMESTAMP(DATE(NOW() - INTERVAL $period DAY))"))
            ->where('order_completed_date', '<', ee()->store->db->raw('UNIX_TIMESTAMP(DATE(NOW()))'))
            ->groupBy(ee()->store->db->raw('DATE(FROM_UNIXTIME(order_completed_date))'))
            ->orderBy(ee()->store->db->raw('DATE(FROM_UNIXTIME(order_completed_date))'))
            ->value('total', 'date');

        // ask MySQL for the start and end dates too, we can't assume PHP timezone matches MySQL
        $dates = ee()->store->db->select("SELECT DATE(NOW() - INTERVAL $period DAY) AS `start`, DATE(NOW()) AS `end`");
        $start_date = Carbon::createFromFormat('Y-m-d', $dates[0]->start, 'UTC')->setTime(0, 0, 0);
        $end_date = Carbon::createFromFormat('Y-m-d', $dates[0]->end, 'UTC')->setTime(0, 0, 0);

        /**
         * Format data for google charts
         */
        $data = [];
        $data['cols'] = [
            ['type' => 'string'],
            ['label' => lang('store.revenue'), 'type' => 'number'],
        ];

        $date = $start_date->copy();
        while ($date < $end_date) {
            $ymd = $date->toDateString();
            $total = isset($totals[$ymd]) ? $totals[$ymd] : 0;

            $data['rows'][] = [
                'c' => [
                    ['v' => $ymd, 'f' => ee()->store->store->format_date($date, '%M %j')],
                    ['v' => $total, 'f' => store_currency($total)],
                ],
            ];

            $date->addDay();
        }

        return $data;
    }
}
