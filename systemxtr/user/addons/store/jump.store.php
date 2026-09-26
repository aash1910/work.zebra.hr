<?php
use ExpressionEngine\Service\JumpMenu\AbstractJumpMenu;

class Store_jump extends AbstractJumpMenu
{
    protected static $items = array(
        'store_dashboard' => array(
            'icon' => 'fa-tachometer-alt',
            'command' => 'store dashboard',
            'command_title' => 'Dashboard',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => '',
        ),
        'store_orders' => array(
            'icon' => 'fa-list',
            'command' => 'store orders',
            'command_title' => 'Orders',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => '&sc=orders',
        ),
        'store_customers' => array(
            'icon' => 'fa-users',
            'command' => 'store customers',
            'command_title' => 'Customers',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => '&sc=customers',
        ),
        'store_inventory' => array(
            'icon' => 'fa-boxes',
            'command' => 'store inventory',
            'command_title' => 'Inventory',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => '&sc=inventory',
        ),
        'store_sales' => array(
            'icon' => 'fa-dollar-sign',
            'command' => 'store sales',
            'command_title' => 'Sales',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => '&sc=sales',
        ),
        'store_discounts' => array(
            'icon' => 'fa-percent',
            'command' => 'store discounts',
            'command_title' => 'Discounts',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => '&sc=discounts',
        ),
        'store_reports' => array(
            'icon' => 'fa-chart-bar',
            'command' => 'store reports',
            'command_title' => 'Reports',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => '&sc=reports',
        ),
        'store_settings' => array(
            'icon' => 'fa-cogs',
            'command' => 'store settings',
            'command_title' => 'Settings',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => '&sc=settings',
        ),
        'store_emails' => array(
            'icon' => 'fa-envelope',
            'command' => 'store emails',
            'command_title' => 'Emails',
            'dynamic' => false,
            'requires_keyword' => false,
            'target' => '&sc=emails',
        ),
    );
}
