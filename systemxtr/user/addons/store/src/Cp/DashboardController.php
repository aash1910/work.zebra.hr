<?php

namespace Store\Cp;

class DashboardController extends AbstractController
{
    public function index()
    {
        $this->setTitle(lang('nav_dashboard'));

        $period = (int)ee()->input->get('period') ?: 30;

        $data = [];
        $data['stats'] = ee()->store->reports->get_dashboard_stats($period);

        // Top right dateranger picker
        $data['start_date'] = strtotime('-30 day', ee()->localize->now);
        $data['end_date'] = ee()->localize->now;

        if (ee()->input->get('daterange')) {
            $range = explode(',', ee()->input->get('daterange'));
            $data['start_date'] = strtotime(reset($range));
            $data['end_date'] = strtotime(end($range));
        }

        // Metric Boxes
        $metrics = [];
        $metrics['revenue'] = [
            'label' => lang('store.customer_revenue'),
            'url' => store_cp_url('reports', 'show', ['report' => 'revenue_summary'])
        ];
        $metrics['orders'] = [
            'label' => lang('store.customer_orders'),
            'url' => store_cp_url('orders')
        ];
        $metrics['products_sold'] = [
            'label' => lang('store.dashboard.products_sold'),
            'url' => store_cp_url('reports', 'show', ['report' => 'product_sales'])
        ];
        $metrics['average_order'] = [
            'label' => lang('store.dashboard.average_order'),
            'url' => store_cp_url('reports', 'show', ['report' => 'order_details'])
        ];
        $metrics['new_customers'] = [
            'label' => lang('store.dashboard.new_customers'),
            'url' => store_cp_url('reports', 'show', ['report' => 'customer_summary'])
        ];
        $metrics['top_selling_product'] = [
            'label' => lang('store.dashboard.top_selling_product'),
            'url' => store_cp_url('reports', 'show', ['report' => 'product_sales'])
        ];
        $data['metrics'] = $metrics;

        // load google javascript library and dashboard graph data
        ee()->cp->add_to_foot('<script type="text/javascript" src="https://www.google.com/jsapi"></script>');
        $graph = ee()->store->reports->get_dashboard_graph_data($period);
        ee()->javascript->output('ExpressoStore.dashboardGraph = ' . json_encode($graph) . ';');

        return $this->render('dashboard', $data);
    }

    public function install()
    {
        $this->setTitle(lang('store.install_new_site'));

        $data = [
            'site_name'         => config_item('site_name'),
            'post_url'          => store_cp_url('dashboard', 'install'),
            'duplicate_options' => ['' => lang('store.none')],
            'is_super_admin'    => ee()->store->config->is_super_admin(),
        ];

        if (ee()->input->post('submit')) {
            if (!$data['is_super_admin']) {
                return show_error(lang('store.no_access'));
            }

            // install default settings
            $site_id = config_item('site_id');
            ee()->store->install->install_site($site_id);

            // install example templates?
            if (ee()->input->post('install_example_templates')) {
                ee()->store->install->install_templates($site_id);
            }

            // redirect
            ee()->session->set_flashdata(['message_success' => lang('store.site_installed_successfully')]);
            ee()->functions->redirect(store_cp_url());
        }

        return ee()->load->view('install', $data, true);
    }
}
