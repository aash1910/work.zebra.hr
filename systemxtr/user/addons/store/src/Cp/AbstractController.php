<?php

namespace Store\Cp;

abstract class AbstractController
{
    protected $ee;
    protected $db;
    protected $breadcrumbs = [];
    protected $globalData = [];

    public function __construct($ee)
    {
        $this->ee = ee();
        $this->db = $ee->store->db;
        $this->addBreadcrumb(store_cp_url(), lang('store_module_name'));
    }

    protected function render($view, array $data, $selected_tab = '', $title = 'Store Settings')
    {
        $this->nav($data);

        // Merge our local view data with our global data
        $data = array_merge($this->globalData, $data);
        $vdata = [];
        $vdata['section'] = ee()->input->get('sc');
        $vdata['content'] = ee()->load->view($view, $data, true);

        return ee()->load->view('_layout', $vdata, true);
    }

    protected function requirePrivilege($privilege)
    {
        if (!ee()->store->config->has_privilege($privilege)) {
            show_error(lang('store.no_access'));
        }
    }

    /**
     * Handles the AJAX request for sorting items.
     *
     * This method processes the sorted IDs received from the AJAX request and updates
     * the sort order of the items in the database. It optionally filters by site ID.
     *
     * @param string $class The class name of the model to be sorted.
     * @param bool $with_site_id Whether to filter by site ID. Default is true.
     * @return ajax response
     */
    protected function sortableAjax($class, $with_site_id = true)
    {
        $sort = 0;
        foreach ((array)ee()->input->post('sorted_ids') as $id) {
            $query = new $class();
            if ($with_site_id) {
                $query = $query->where('site_id', config_item('site_id'));
            }
            $query->where('id', $id)->update(['sort' => $sort++]);
        }

        return ee()->output->send_ajax_response([
            'type'    => 'success',
            'message' => lang('store.settings.updated'),
        ]);
    }

    /**
     * Build the Control Panel's left sidebar navigation
     */
    protected function nav()
    {
        $sidebar = ee('CP/Sidebar')->make();

        // Get the sidebar items:
        $items = [];
        $settingsSection = $sidebar->addHeader('Store');
        $items['dashboard'] = $sidebar->addItem(lang('store.dashboard'), ee('CP/URL', 'addons/settings/store'))->withIcon('house');
        $items['reports'] = $sidebar->addItem(lang('store.reports'), ee('CP/URL', 'addons/settings/store&sc=reports'))->withIcon('file-chart-column');

        // Store section:
        $sidebar->addDivider();
        $settingsSection = $sidebar->addHeader('Manage');
        $items['orders'] = $sidebar->addItem(lang('store.orders'), ee('CP/URL', 'addons/settings/store&sc=orders'))->withIcon('list');
        $items['customers'] = $sidebar->addItem(lang('store.customers'), ee('CP/URL', 'addons/settings/store&sc=customers'))->withIcon('user');
        $items['inventory'] = $sidebar->addItem(lang('store.inventory'), ee('CP/URL', 'addons/settings/store&sc=inventory'))->withIcon('cubes');
        $items['sales'] = $sidebar->addItem(lang('nav_sales'), ee('CP/URL', 'addons/settings/store&sc=sales'))->withIcon('percent');
        $items['discounts'] = $sidebar->addItem(lang('nav_discounts'), ee('CP/URL', 'addons/settings/store&sc=discounts'))->withIcon('tag');

        if (ee()->store->config->has_privilege('can_access_settings')) {
            // Settings section:
            $sidebar->addDivider();
            $settingsSection = $sidebar->addHeader('Settings');

            $items['general'] = $sidebar->addItem(lang('store.general'), ee('CP/URL', 'addons/settings/store&sc=settings'))->withIcon('cog');
            $items['email'] = $sidebar->addItem(lang('store.settings.email'), ee('CP/URL', 'addons/settings/store&sc=settings&sm=email'))->withIcon('envelope');
            $items['order_fields'] = $sidebar->addItem(lang('store.settings.order_fields'), ee('CP/URL', 'addons/settings/store&sc=settings&sm=order_fields'))->withIcon('list');
            $items['status'] = $sidebar->addItem(lang('store.settings.status'), ee('CP/URL', 'addons/settings/store&sc=settings&sm=status'))->withIcon('check');
            $items['payment'] = $sidebar->addItem(lang('store.settings.payment'), ee('CP/URL', 'addons/settings/store&sc=settings&sm=payment'))->withIcon('credit-card');
            $items['shipping'] = $sidebar->addItem(lang('store.settings.shipping'), ee('CP/URL', 'addons/settings/store&sc=settings&sm=shipping'))->withIcon('truck');
            $items['country'] = $sidebar->addItem(lang('store.settings.country'), ee('CP/URL', 'addons/settings/store&sc=settings&sm=country'))->withIcon('globe');
            $items['tax'] = $sidebar->addItem(lang('store.settings.tax'), ee('CP/URL', 'addons/settings/store&sc=settings&sm=tax'))->withIcon('percent');
            $items['conversions'] = $sidebar->addItem(lang('store.settings.conversions'), ee('CP/URL', 'addons/settings/store&sc=settings&sm=conversions'))->withIcon('chart-line');
            $items['security'] = $sidebar->addItem(lang('store.settings.security'), ee('CP/URL', 'addons/settings/store&sc=settings&sm=security'))->withIcon('shield');
        }

        $activeAliases = [
            'status_edit' => 'status',
            'email_edit' => 'email',
            'payment_edit' => 'payment',
            'shipping_method' => 'shipping',
            'country_edit' => 'country',
            'tax_edit' => 'tax',
        ];

        // Get the current active item and set active state:
        $smPage = ee()->input->get('sm');
        $scPage = ee()->input->get('sc');
        if(isset($items[$scPage])) {
            $items[$scPage]->isActive();
        } elseif (isset($activeAliases[$scPage])) {
            $items[$activeAliases[$scPage]]->isActive();
        } elseif (isset($items[$smPage])) {
            $items[$smPage]->isActive();
        } elseif (isset($activeAliases[$smPage])) {
            $items[$activeAliases[$smPage]]->isActive();
        // else if dashboard
        } elseif (ee()->input->get('sc') == 'settings') {
            $items['general']->isActive();
        } else {
            $items['dashboard']->isActive();
        }
    }

    /**
     * Set the CP page title
     *
     * @param $title
     */
    protected function setTitle($title = 'Any title')
    {
        $this->setVariable('cp_page_title', $title);
    }

    /**
     * We use our own breadcrumb function to override the useless "Modules" crumb added by
     * the modules controller.
     *
     * @param $link
     * @param $title
     */
    protected function addBreadcrumb($link, $title)
    {
        $key = (string)$link;
        $this->breadcrumbs[$key] = $title;
        $this->setVariable('cp_breadcrumbs', $this->breadcrumbs);
    }

    public function getBreadcrumbs()
    {
        return $this->breadcrumbs;
    }

    /**
     * Backwards compatible view variable setter
     *
     * @param $key
     * @param $value
     */
    protected function setVariable($key, $value)
    {
        ee()->view->$key = $value;
    }
}
