<?php

namespace ExpressionEngine\Addons\ControlPanel\Routes;

use ExpressionEngine\Service\Addon\Controllers\Mcp\AbstractRoute;
use Illuminate\Support\Str;

class Reports extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'reports';

    /**
     * @var string
     */
    protected $cp_page_title = 'Reports';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false)
    {
        $this->addBreadcrumb('reports', 'Reports');
    }

    public function index()
    {
        $title = lang('nav_reports');

        $data = [];
        $data['reports'] = ee()->store->reports->get_reports();

        return [
            'body'       => $this->render('reports/index', $data),
            'breadcrumb' => $this->getBreadcrumbs(),
            'heading'    => $title,
        ];
    }

    public function show()
    {
        $reports = ee()->store->reports->get_reports();
        $report_name = Str::snake(ee()->input->get('report', true));

        if (!isset($reports[$report_name])) {
            return show_404();
        }

        // submitted form values are converted query string for easy bookmarking etc
        if (isset($_POST['options'])) {
            $options = array_merge(['report' => $report_name], (array)$_POST['options']);

            return ee()->functions->redirect(store_cp_url('reports', 'show', $options));
        }
        $this->addBreadcrumb(store_cp_url('reports'), lang('nav_reports'));
        $title = lang("store.reports.$report_name");
        $class = $reports[$report_name];
        if (ee()->input->get('csv')) {
            $renderer = new CsvRenderer();
            $report = new $class($this->ee, $renderer, $_GET);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $report_name . '.csv"');
            $report->run();
            exit;
        }

        $renderer = new HtmlRenderer();
        $data['report'] = new $class($this->ee, $renderer, $_GET);

        if (ee()->input->get('print')) {
            $layout_data = [
                'title' => lang("store.reports.$report_name"),
                'body'  => $data['report'],
                'class' => 'report',
            ];

            echo ee()->load->view('print_layout', $layout_data, true);
            exit;
        }

        $data['post_url'] = store_cp_url('reports', 'show', ['report' => $report_name]);
        $data['export_url'] = store_cp_url('reports', 'show', $_GET);

        ee()->cp->add_js_script('ui', 'datepicker');

        return [
            'body'       => $this->render('reports/show', $data),
            'breadcrumb' => $this->getBreadcrumbs(),
            'heading'    => $title,
        ];
    }
}
