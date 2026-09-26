<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Cp;

use Store\Report\CsvRenderer;
use Store\Report\HtmlRenderer;
use Store\Dependency\Illuminate\Support\Str;

class ReportsController extends AbstractController
{
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
        $report_name = Str::snake(ee('Request')->get('report', true));

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
        if (ee('Request')->get('csv')) {
            $renderer = new CsvRenderer();
            $report = new $class(ee(), $renderer, $_GET);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $report_name . '.csv"');
            $report->run();
            exit;
        }

        $renderer = new HtmlRenderer();
        $data['report'] = new $class(ee(), $renderer, $_GET);

        if (ee('Request')->get('print')) {
            $layout_data = [
                'title' => lang("store.reports.$report_name"),
                'body'  => $data['report'],
                'class' => 'report',
            ];

            echo ee('View')->make('store:print_layout')->render($layout_data);
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
