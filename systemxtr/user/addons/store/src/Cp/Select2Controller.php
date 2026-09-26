<?php

namespace Store\Cp;

use Store\Model\Product;

class Select2Controller extends AbstractController
{
    public function __construct($ee)
    {
        parent::__construct($ee);
    }

    public function products()
    {
        $out = ['results' => [], 'more' => true];
        $term = trim(ee()->input->get_post('q'));
        $limit = ee()->input->get_post('page_limit') ? ee()->input->get_post('page_limit') : 10;
        $page = ee()->input->get_post('page') ? ee()->input->get_post('page') : 1;
        $offset = ($page - 1) * $limit;

        // Real Query
        $query = Product::join('channel_titles', 'channel_titles.entry_id', '=', 'store_products.entry_id')
            ->select(['store_products.*', 'channel_titles.title'])
            ->where('channel_titles.site_id', '=', config_item('site_id'))
            ->groupBy('store_products.entry_id');

        if ($term) {
            $query->where('channel_titles.title', 'LIKE', '%' . $term . '%');
        }

        $query = $query
            ->orderby('channel_titles.title')
            ->take($limit)
            ->skip($offset)
            ->get();

        foreach ($query as $row) {
            $out['results'][] = ['id' => $row->entry_id, 'text' => $row->title];
        }

        if (count($out['results']) < $limit) {
            $out['more'] = false;
        }

        exit(json_encode($out));
    }
}
