<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store;

use EE_Fieldtype;
use Store\Model\Product;

class Field extends EE_Fieldtype
{
    public $info = [
        'name'    => 'Store Product Details',
        'version' => STORE_VERSION,
    ];

    public $has_array_data = true;
    public $params;
    public $tagdata;
    public $grid_data;
    public $rules;
    public $upload_prefs;

    /**
     * Display field on the publish tab
     *
     * @param $field_data
     * @return
     */
    public function display_field($field_data)
    {
        foreach (['length_with_units', 'width_with_units', 'height_with_units'] as $key) {
            ee()->lang->language['store.' . $key] = sprintf(lang('store.' . $key), config_item('store_dimension_units'));
        }

        ee()->lang->language['store.weight_with_units'] = sprintf(lang('store.weight_with_units'), config_item('store_weight_units'));

        ee()->load->library('table');

        $data = [];
        $data['field_name'] = $this->field_name;
        $data['field_required'] = $this->is_required();

        $entry_id = ee()->uri->segment(5) ? (int)ee()->uri->segment(5) : 0;
        $product = $this->find_or_create_product($entry_id);

        $data['product'] = $product;

        $post_data = ee()->input->post('store_product_field', true);
        if ($post_data) {
            $product->fill((array)$post_data);
        }

        $data['modifiers'] = isset($post_data['modifiers']) ? $post_data['modifiers'] : $product->getModifiersArray();

        // load store css + js
        ee()->store->config->load_cp_assets();
        ee()->cp->add_to_foot('
            <script type="text/javascript">
            ExpressoStore.productStock = ' . $product->stock->toJSON() . ';
            $("#store_product_field").parent(".setting-field").removeClass("w-8");
            </script>');
        ee()->cp->add_js_script([
            'ui'   => ['datepicker', 'sortable'],
            'file' => ['underscore'],
        ]);

        return ee()->load->view('field', $data, true);
    }

    protected function find_or_create_product($entry_id)
    {
        $entry_id = (int)$entry_id;
        $product = Product::with([
            'modifiers' => function ($query) {
                $query->orderBy('mod_order');
            },
            'modifiers.options' => function ($query) {
                $query->orderBy('opt_order');
            },
            'stock',
            'stock.stockOptions',
        ])->find($entry_id);

        if (!$product) {
            $product = new Product();
            $product->entry_id = $entry_id;
        }

        return $product;
    }

    /**
     * Prep the data for saving
     *
     * Cache product SKUs inside our custom field, so that it can be found by EE search tags.
     * We never actually use the data stored in the custom field, it is purely here for search.
     *
     * @param $data
     * @return string
     */
    public function save($data)
    {
        $field_data = ee()->input->post('store_product_field', true);
        $skus = ['[store]'];

        if (!empty($field_data['stock'])) {
            foreach ($field_data['stock'] as $stock) {
                $skus[] = $stock['sku'];
            }
        }

        return implode(' ', $skus);
    }

    /**
     * Runs after an entry has been saved
     *
     * @param $data
     */
    public function post_save($data)
    {
        $this->post_save_settings($data);
        $product = $this->find_or_create_product($this->settings['entry_id']);

        $product->fill((array)ee()->input->post('store_product_field', true));

        ee()->store->products->save_product($product);
    }

    public function post_save_settings($data)
    {
        $settings = ee()->input->post('store', true);
        if (!is_array($settings)) {
            $settings = [];
        }
        $content_id = $this->content_id();
        if ($content_id !== null) {
            $settings['entry_id'] = $content_id;
        }
        $settings['field_fmt'] = 'none';
        $settings['field_show_fmt'] = 'n';
        $settings['field_type'] = 'store';
        $this->settings = $settings;
        //return $settings;
    }

    public function delete($entry_ids)
    {
        ee()->store->products->delete_all($entry_ids);
    }

    public function validate($data)
    {
        $error = false;

        if ($this->is_required() && !$this->run_validation('store_product_field[price]', 'lang:store.price', 'required')) {
            $error = true;
        }

        return $error;
    }

    protected function is_required()
    {
        return 'y' === $this->settings['field_required'];
    }

    /**
     * Immediately run validation rules
     *
     * @param $field
     * @param string $label
     * @param string $rules
     */
    protected function run_validation($field, $label = '', $rules = '')
    {
    }

    /**
     * Allow {product_details} to be used as a tag pair
     *
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @return
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        if ($tagdata) {
            return ee()->TMPL->parse_variables($tagdata, [$data]);
        }
    }

    /**
     * EE bug: replace_tag_catchall doesn't seem to work with conditionals
     * e.g. {if product_details:on_sale}
     *
     * @param $data
     * @return
     */
    public function replace_on_sale($data)
    {
        if (isset($data['on_sale'])) {
            return $data['on_sale'];
        }
    }

    public function replace_tag_catchall($data, $params = [], $tagdata = false, $modifier = null)
    {
        if (isset($data[$modifier])) {
            return $data[$modifier];
        }
    }

    public function load_settings($data)
    {
        $settings = [
            'field_options_store' => [
                'label'    => 'field_options',
                'group'    => 'store',
                'settings' => [
                    [
                        'title'  => lang('store.enable_custom_prices', 'enable_custom_prices'),
                        'desc'   => lang('store.enable_custom_prices_subtext') . '<i>' . lang('store.enable_custom_prices_warning') . '</i>',
                        'fields' => [
                            'store[enable_custom_prices]' => [
                                'type'    => 'radio',
                                'choices' => ['1' => lang('yes'), '0' => lang('no')],
                                'value'   => isset($data['enable_custom_prices']) ? $data['enable_custom_prices'] : '',
                            ],
                        ],
                    ], [
                        'title'  => lang('store.enable_custom_weights', 'enable_custom_weights'),
                        'desc'   => lang('store.enable_custom_weights_subtext'),
                        'fields' => [
                            'store[enable_custom_weights]' => [
                                'type'    => 'radio',
                                'choices' => ['1' => lang('yes'), '0' => lang('no')],
                                'value'   => isset($data['enable_custom_weights']) ? $data['enable_custom_weights'] : '',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $settings;
    }

    /**
     * Display Settings Screen
     *
     * @param $data
     * @return array default global settings
     */
    public function display_settings($data)
    {
        ee()->lang->loadfile('fieldtypes');

        return $this->load_settings($data);
    }

    /**
     * Save Settings
     *
     * @param $data
     * @return array field settings
     */
    public function save_settings($data)
    {
        $settings = ee()->input->post('store', true);
        if (!is_array($settings)) {
            $settings = [];
        }
        $settings['field_fmt'] = 'none';
        $settings['field_show_fmt'] = 'n';
        $settings['field_type'] = 'store';

        return $settings;
    }

    /**
     * Support Entry API v3
     *
     * @param null $data
     * @param bool $free_access
     * @param int $entry_id
     * @return array|null
     */
    public function entry_api_pre_process($data = null, $free_access = false, $entry_id = 0)
    {
        /** @var Product $product */
        $product = Product::with([
            'modifiers' => function ($query) {
                $query->orderBy('mod_order');
            },
            'modifiers.options' => function ($query) {
                $query->orderBy('opt_order');
            },
            'stock',
        ])
            ->whereNotNull('price')
            ->find($entry_id);

        if (empty($product)) {
            return;
        }

        ee()->store->products->apply_sales($product);

        // ew, gross
        ee()->load->helper('form');

        return $product->toTagArray();
    }

    /**
     * ===================================
     * function zenbu_field_extra_settings
     * ===================================
     * Set up display for this fieldtype in "display settings"
     *
     * @param $table_col
     * @param $channel_id
     * @param $extra_options
     * @return mixed
     */
    public function zenbu_field_extra_settings($table_col, $channel_id, $extra_options)
    {
        ee()->load->helper('form');

        $store_price = (isset($extra_options['store_price'])) ? true : false;
        $store_handling = (isset($extra_options['store_handling'])) ? true : false;
        $store_sku = (isset($extra_options['store_sku'])) ? true : false;
        $store_width = (isset($extra_options['store_width'])) ? true : false;
        $store_length = (isset($extra_options['store_length'])) ? true : false;
        $store_height = (isset($extra_options['store_height'])) ? true : false;
        $store_weight = (isset($extra_options['store_weight'])) ? true : false;

        $output['store_price'] = form_label(form_checkbox('settings[' . $channel_id . '][' . $table_col . '][store_price]', 'y', $store_price) . '&nbsp;' . ee()->lang->line('zenbu_show_price') . '<br />');

        $output['store_handling'] = form_label(form_checkbox('settings[' . $channel_id . '][' . $table_col . '][store_handling]', 'y', $store_handling) . '&nbsp;' . ee()->lang->line('zenbu_show_handling') . '<br />');

        $output['store_sku'] = form_label(form_checkbox('settings[' . $channel_id . '][' . $table_col . '][store_sku]', 'y', $store_sku) . '&nbsp;' . ee()->lang->line('zenbu_show_sku') . '<br />');

        $output['store_width'] = form_label(form_checkbox('settings[' . $channel_id . '][' . $table_col . '][store_width]', 'y', $store_width) . '&nbsp;' . ee()->lang->line('zenbu_show_width') . '<br />');

        $output['store_height'] = form_label(form_checkbox('settings[' . $channel_id . '][' . $table_col . '][store_height]', 'y', $store_height) . '&nbsp;' . ee()->lang->line('zenbu_show_width') . '<br />');

        $output['store_length'] = form_label(form_checkbox('settings[' . $channel_id . '][' . $table_col . '][store_length]', 'y', $store_length) . '&nbsp;' . ee()->lang->line('zenbu_show_length') . '<br />');

        $output['store_weight'] = form_label(form_checkbox('settings[' . $channel_id . '][' . $table_col . '][store_weight]', 'y', $store_weight) . '&nbsp;' . ee()->lang->line('zenbu_show_weight'));

        return $output;
    }

    /**
     *    =============================
     *    function zenbu_get_table_data
     *    =============================
     *    Retrieve data stored in other database tables
     *    based on results from Zenbu's entry list
     *
     * @param $entry_ids array    An array of entry IDs from Zenbu's entry listing results
     * @param $field_ids array    An array of field IDs tied to/associated with result entries
     * @param $channel_id int        The ID of the channel in which Zenbu searched entries (0 = "All channels")
     * @return array|void $output                    array    An array of data (typically broken down by entry_id then field_id) that can be used and processed by the zenbu_display() method
     * @uses    Instead of many small queries, this function can be used to carry out
     *            a single query of data to be later processed by the zenbu_display() method
     */
    public function zenbu_get_table_data($entry_ids, $field_ids, $channel_id)
    {
        $output = [];
        if (empty($entry_ids) || empty($field_ids)) {
            return $output;
        }

        $product = Product::with(['stock'])->find($entry_ids)->toTagArray();

        if (empty($product)) {
            return;
        }

        $pArray = [];

        foreach ($product as $p) {
            $pArray[$p['entry_id']] = $p;
        }

        ee()->session->set_cache('expresso_store', 'product_ids', $pArray);
    }

    /**
     *    ======================
     *    function zenbu_display
     *    ======================
     *    Set up display in entry result cell
     *
     * @param $entry_id
     * @param $channel_id
     * @param $data
     * @param array $grid_data
     * @param $field_id
     * @param $settings
     * @param array $rules
     * @param array $upload_prefs
     * @param $installed_addons
     * @return string $output        The HTML used to display data
     */
    public function zenbu_display($entry_id, $channel_id, $data, $grid_data = [], $field_id = null, $settings = [], $rules = [], $upload_prefs = [], $installed_addons = [])
    {
        $output = NBS;

        $pArray = ee()->session->cache('expresso_store', 'product_ids');

        $s = (array)$settings;

        if (empty($s)) {
            return $output;
        }

        ee()->store->config->load_cp_assets();

        $store_price = (isset($s['store_price'])) ? $s['store_price'] : false;
        $store_handling = (isset($s['store_handling'])) ? $s['store_handling'] : false;
        $store_sku = (isset($s['store_sku'])) ? $s['store_sku'] : false;
        $store_length = (isset($s['store_length'])) ? $s['store_length'] : false;
        $store_height = (isset($s['store_height'])) ? $s['store_height'] : false;
        $store_width = (isset($s['store_width'])) ? $s['store_width'] : false;
        $store_weight = (isset($s['store_weight'])) ? $s['store_weight'] : false;

        if (isset($store_price) || isset($store_handling) || isset($store_sku)) {
            $div = "<table class='store_ft'><tr>";

            if ($store_price) {
                $div .= '<td><strong>' . ee()->lang->line('store.item_price') . "</strong></td><td class='store_ft_text'>" . (($pArray[$entry_id]['price'] !== '') ? $pArray[$entry_id]['price'] : '-') . '</td></tr>';
            }

            if ($store_handling) {
                $div .= '<td><strong>' . ee()->lang->line('store.handling') . "</strong></td><td class='store_ft_text'>" . (($pArray[$entry_id]['handling'] !== '') ? $pArray[$entry_id]['handling'] : '-') . '</td></tr>';
            }

            if ($store_sku) {
                $div .= '<td><strong>' . ee()->lang->line('store.sku') . "</strong></td><td class='store_ft_text'>" . (($pArray[$entry_id]['sku'] !== '') ? $pArray[$entry_id]['sku'] : '-') . '</td></tr>';
            }

            if ($store_width) {
                $div .= '<td><strong>' . sprintf(lang('store.width_with_units'), config_item('store_dimension_units')) . "</strong></td><td class='store_ft_text'>" . (($pArray[$entry_id]['width'] != '') ? $pArray[$entry_id]['width'] : '-') . '</td></tr>';
            }

            if ($store_height) {
                $div .= '<td><strong>' . sprintf(lang('store.height_with_units'), config_item('store_dimension_units')) . "</strong></td><td class='store_ft_text'>" . (($pArray[$entry_id]['height'] != '') ? $pArray[$entry_id]['height'] : '-') . '</td></tr>';
            }

            if ($store_length) {
                $div .= '<td><strong>' . sprintf(lang('store.length_with_units'), config_item('store_dimension_units')) . "</strong></td><td class='store_ft_text'>" . (($pArray[$entry_id]['length'] != '') ? $pArray[$entry_id]['length'] : '-') . '</td></tr>';
            }

            if ($store_weight) {
                $div .= '<td><strong>' . sprintf(lang('store.weight_with_units'), config_item('store_weight_units')) . "</strong></td><td class='store_ft_text'>" . (($pArray[$entry_id]['weight'] != '') ? $pArray[$entry_id]['weight'] : '-') . '</td></tr>';
            }

            $div .= '</table>';

            return $div;
        } else {
            // ..else return the table as-is, and see it displayed directly in the row
            return $output;
        }
    }
}
