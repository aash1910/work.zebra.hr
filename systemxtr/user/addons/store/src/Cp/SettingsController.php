<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Cp;

use Store\Dependency\Illuminate\Database\Query\Builder;
use Store\Dependency\Illuminate\Support\Str;
use Store\Dependency\Omnipay\Omnipay;
use Store\FormBuilder;
use Store\Model\Country;
use Store\Model\Email;
use Store\Model\Order;
use Store\Model\PaymentMethod;
use Store\Model\ShippingMethod;
use Store\Model\ShippingRule;
use Store\Model\State;
use Store\Model\Status;
use Store\Model\Tax;

class SettingsController extends AbstractController
{
    public $data = [];

    public function __construct($ee)
    {
        parent::__construct($ee);

        $this->requirePrivilege('can_access_settings');
        $this->addBreadcrumb(store_cp_url('settings'), lang('nav_settings'));

        // some settings pages require countries/regions JSON data
        ee()->cp->add_js_script(['ui' => 'sortable']);
        ee()->javascript->output('ExpressoStore.countries = ' . ee()->store->shipping->get_countries_json() . ';');
    }

    protected function render($view, array $data, $selected_tab = '', $title = 'Store Settings')
    {
        $content = ee()->load->view($view, $data, true);

        return [
            'body'       => $this->renderLayout($content, $selected_tab),
            'breadcrumb' => $this->getBreadcrumbs(),
            'heading'    => $title,
        ];
    }

    protected function renderLayout($content, $selected_tab)
    {
        $settings_url = store_cp_url('settings');

        $layout_data = [];
        $layout_data['content'] = $content;
        $layout_data['current_page'] = $selected_tab;
        $layout_data['pages'] = [
            'general'      => $settings_url,
            'email'        => $settings_url . '&sm=email',
            'order_fields' => $settings_url . '&sm=order_fields',
            'status'       => $settings_url . '&sm=status',
            'payment'      => $settings_url . '&sm=payment',
            'shipping'     => $settings_url . '&sm=shipping',
            'country'      => $settings_url . '&sm=country',
            'tax'          => $settings_url . '&sm=tax',
            'conversions'  => $settings_url . '&sm=conversions',
            'security'     => $settings_url . '&sm=security',
        ];

        // render layout
        return parent::render('settings/base', $layout_data);
    }

    public function textname_check($str)
    {
        if ($str == 'test') {
            $this->form_validation->set_message('textname_check', 'The %s field can not be the word "test"');

            return false;
        } else {
            return true;
        }
    }

    public function index()
    {
        $this->setTitle(lang('store.settings.general'));

        $items = [
            'store_currency_symbol', 'store_currency_suffix', 'store_currency_decimals',
            'store_currency_dec_point', 'store_currency_thousands_sep', 'store_currency_code',
            'store_weight_units', 'store_dimension_units', 'store_from_email', 'store_from_name',
            'store_default_order_address', 'store_cc_payment_method', 'store_force_member_login',
            'store_cart_expiry', 'store_secure_template_tags', 'store_order_id_start', 'store_order_invoice_url',
            'store_reporting_timezone',
        ];

        return $this->renderSettings('general', $items);
    }

    protected function renderSettings($page, $items)
    {
        $data = [];
        $data['post_url'] = store_cp_url('settiings', $page);
        $data['settings'] = [];
        $data['setting_defaults'] = [];

        // check for submitted general form
        if (!empty($_POST)) {
            // load submitted settings
            $settings = ee()->input->post('settings');
            ee()->store->config->update($settings);

            $redirect_url = store_cp_url('settings');
            if ($page != 'general') {
                $redirect_url .= '&sm=' . $page;
            }

            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect($redirect_url);
        }

        // generate setting inputs
        foreach ($items as $key) {
            $data['settings'][$key] = config_item($key);
            $data['setting_defaults'][$key] = ee()->store->config->settings[$key];
        }

        return $this->render('settings/general', $data, $page);
    }

    public function conversions()
    {
        $this->setTitle(lang('store.settings.conversions'));

        $items = ['store_google_analytics_ecommerce', 'store_conversion_tracking_extra'];

        return $this->renderSettings('conversions', $items);
    }

    public function email()
    {
        $title = lang('store.settings.email');

        // handle form submit
        if (!empty($_POST)) {
            $selected = Email::where('site_id', config_item('site_id'))
                ->whereIn('id', (array)ee()->input->post('selected', true));

            switch (ee()->input->post('with_selected')) {
                case 'enable':
                    $selected->update(['enabled' => 1]);
                    break;
                case 'disable':
                    $selected->update(['enabled' => 0]);
                    break;
                case 'delete':
                    $selected->delete();
                    break;
            }

            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('settings', 'email'));
        }

        $data = [];
        $data['post_url'] = store_cp_url() . '&sc=settings&sm=email';
        $data['edit_url'] = store_cp_url('settings', 'email_edit') . '&id=';
        $data['emails'] = Email::where('site_id', config_item('site_id'))->orderBy('name')->get();

        return $this->render('settings/email', $data, 'email', $title);
    }

    public function email_edit()
    {
        $this->addBreadcrumb(store_cp_url('settings', 'email'), lang('store.settings.email'));
        ee()->lang->loadfile('communicate');
        ee()->load->library('form_validation');
        $id = ee()->input->get('id');
        if ('new' == $id) {
            $title = lang('store.new_email_template');
            $email = new Email();
            $email->site_id = config_item('site_id');
        } else {
            $title = lang('store.settings.edit_email');

            $email = Email::where('site_id', config_item('site_id'))->find($id);
            if (empty($email)) {
                return show_404();
            }
        }

        // handle form submit
        $rules = [
            [
                'field' => 'name',
                'label' => 'Email Name',
                'rules' => 'required',
            ], [
                'field' => 'subject',
                'label' => 'Subject',
                'rules' => 'required',
            ], [
                'field' => 'contents',
                'label' => 'Message',
                'rules' => 'required',
            ],
        ];

        ee()->form_validation->set_rules($rules);

        if (ee()->form_validation->run() == true) {
            // DON'T run POST through XSS filter - it breaks inline CSS
            $email->fill($_POST);
            $email->save();
            // redirect
            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('settings', 'email'));
        }

        $data = [];
        $data['post_url'] = store_cp_url() . '&sc=settings&sm=email_edit&id=' . $id;
        $data['email'] = $email;

        return $this->render('settings/email_edit', $data, 'email', $title);
    }

    public function order_fields()
    {

        $title = lang('store.settings.order_fields');

        $f = [];

        $fields = ee('Model')->get('MemberField')->all();
        $sql = "SELECT *  FROM exp_member_fields";
        $fields = ee()->db->query($sql)->result();


        foreach($fields as $field) {
            $f[$field->m_field_name] = $field->m_field_label;
        }

        $f = array_merge(['' => ''], array_filter($f));

        $data = [
            'post_url'      => store_cp_url() . '&sc=settings&sm=order_fields',
            'order_fields'  => ee()->store->config->order_fields(),
            //'member_fields' => ee()->store->member->get_member_fields_select(), results in errors for ee7
            'member_fields' => $f
        ];


        // check for submitted form
        if (!empty($_POST)) {
            if (ee()->input->post('restore_defaults')) {
                $data['order_fields'] = ee()->store->config->order_field_defaults();
            } else {
                $post_order_fields = ee()->input->post('order_fields', true);
                foreach ($data['order_fields'] as $field_name => $field) {
                    if (isset($field['title'])) {
                        $data['order_fields'][$field_name]['title'] = isset($post_order_fields[$field_name]['title']) ? $post_order_fields[$field_name]['title'] : '';
                    }

                    $data['order_fields'][$field_name]['member_field'] = isset($post_order_fields[$field_name]['member_field']) ? $post_order_fields[$field_name]['member_field'] : '';
                }
            }

            // update database and redirect
            ee()->store->config->update(['store_order_fields' => $data['order_fields']]);

            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect($data['post_url']);
        }

        return $this->render('settings/order_fields', $data, 'order_fields', $title);
    }

    public function status()
    {
        $this->setTitle(lang('store.settings.status'));

        // sortable ajax post
        if (!empty($_POST['sortable_ajax'])) {
            return $this->sortableAjax('\Store\Model\Status');
        }

        $data = [];
        $data['post_url'] = store_cp_url('settings', 'status');
        $data['edit_url'] = store_cp_url('settings', 'status_edit') . '&id=';
        $data['statuses'] = Status::where('site_id', config_item('site_id'))->orderBy('sort')->get();

        return $this->render('settings/status', $data, 'status');
    }

    public function status_edit()
    {
        $this->addBreadcrumb(store_cp_url('sales', 'status'), lang('nav_sales'));
        ee()->load->library('form_validation');
        $status_id = ee()->input->get('id');
        $message_error = '';

        if ($status_id == 'new') {
            $status = new Status();
            $status->site_id = config_item('site_id');
            $status->sort = Status::max('sort') + 1;
            $title = lang('store.status_add');
        } else {
            $status = Status::where('site_id', config_item('site_id'))->find($status_id);
            if (empty($status)) {
                return show_404();
            }
            $title = lang('store.status_edit');
        }

        $locked = $status->exists && Order::where('site_id', config_item('site_id'))
                ->where('order_status_name', $status->name)
                ->count() > 0;
        $rules = [];

        // handle form submit
        if (!empty($_POST['submit'])) {
            if ($locked) {
                unset($_POST['status']['name']);
            } else {
                $rules = [
                    [
                        'field' => 'status[name]',
                        'label' => 'Name',
                        'rules' => 'required',
                    ],
                ];
            }

            $status->fill((array)ee()->input->post('status', true));

            if (!$locked) {
                ee()->form_validation->set_rules($rules);

                if (ee()->form_validation->run() == true) {
                    if ($this->statusExists(ee()->input->post('status', true)) && $status_id == 'new') {
                        //ee()->form_validation->set_message('unique_status_name','Status Name must be unique');
                        ee()->session->set_flashdata('message_error', 'Status Name must be unique');
                        $message_error = 'Status Name must be unique';
                    } else {
                        $status->save();
                        // ensure there is only one default
                        if ($status->is_default) {
                            Status::where('site_id', config_item('site_id'))
                                ->where('id', '!=', $status->id)
                                ->update(['is_default' => 0]);
                        }
                        ee()->session->set_flashdata('message_success', lang('store.settings.updated'));
                        ee()->functions->redirect(store_cp_url('settings', 'status'));
                    }
                }
            } else {
                $status->save();
                // ensure there is only one default
                if ($status->is_default) {
                    Status::where('site_id', config_item('site_id'))
                        ->where('id', '!=', $status->id)
                        ->update(['is_default' => 0]);
                }
                ee()->session->set_flashdata('message_success', lang('store.settings.updated'));
                ee()->functions->redirect(store_cp_url('settings', 'status'));
            }
        }

        // handle delete
        if (!$locked && !empty($_POST['delete'])) {
            Status::where('id', '=', $status->id)->delete();
            ee()->session->set_flashdata('message_success', lang('store.settings.updated'));
            ee()->functions->redirect(store_cp_url('settings', 'status'));
        }

        $data = [];
        $data['status'] = $status;
        $data['form'] = new FormBuilder($status);
        $data['locked'] = $locked;

        $data['emails'] = ['' => lang('store.none')];
        foreach (Email::where('site_id', config_item('site_id'))->get() as $email) {
            $data['emails'][$email->id] = store_email_template_name($email->name);
        }
        if ($message_error != '') {
            //ee()->session->set_flashdata('message_error', $message_error);
            $data['message_error'] = $message_error;
        }

        return $this->render('settings/status_edit', $data, 'status', $title);
    }

    public function statusExists($status)
    {
        $exist = false;
        foreach (Status::where('name', '=', $status['name'])->get() as $status) {
            $exist = true;
        }

        return $exist;
    }

    public function payment()
    {
        $this->setTitle(lang('store.settings.payment'));

        $data['gateways'] = [];
        foreach (ee()->store->payments->get_payment_gateways() as $name) {
            $class = ee()->store->payments->getGatewayClassName($name);
            $gateway = Omnipay::create($class);

            $data['gateways'][$name] = [
                'title'        => $gateway->getName(),
                'class'        => $name,
                'enabled'      => false,
                'settings_url' => store_cp_url('settings', 'payment_edit', ['class' => $name]),
            ];
        }

        $payment_methods = PaymentMethod::where('site_id', config_item('site_id'))->where('enabled', 1)->get();
        foreach ($payment_methods as $method) {
            if (isset($data['gateways'][$method->class])) {
                $data['gateways'][$method->class]['enabled'] = true;
            }
        }

        return $this->render('settings/payment', $data, 'payment');
    }

    public function payment_edit()
    {
        // allow extensions to load custom gateways
        $available_gateways = ee()->store->payments->get_payment_gateways();
        if (!in_array(ee()->input->get('class'), $available_gateways)) {
            return show_404();
        }

        $class = ee()->store->payments->getGatewayClassName(ee()->input->get('class'));
        $gateway = Omnipay::create($class);

        $this->addBreadcrumb(store_cp_url('settings', 'payment'), lang('store.settings.payment'));
        $title = $gateway->getName();

        $class = $gateway->getShortName();
        $method = PaymentMethod::where('site_id', config_item('site_id'))->where('class', $class)->first();
        if ($method) {
            $gateway->initialize($method->settings);
        }

        // check for submitted data
        if (!empty($_POST)) {
            $settings = (array)ee()->input->post('settings');
            // Special handling for Stripe: map secret/publishable keys
            if ($class === 'Stripe_PaymentIntents') {
                if (isset($settings['apiKey'])) {
                    $settings['secret_key'] = $settings['apiKey'];
                }
                if (isset($settings['publishableKey'])) {
                    $settings['publishable_key'] = $settings['publishableKey'];
                }
            }
            $gateway->initialize($settings);

            $method = $method ?: new PaymentMethod();
            $method->site_id = config_item('site_id');
            $method->class = $class;
            $method->title = $gateway->getName();
            $method->settings = $gateway->getParameters();
            $method->enabled = (int)ee()->input->post('enabled');
            $method->save();

            // Display error message to user via alert system
            ee('CP/Alert')
                ->makeBanner('payment-updated')
                ->asSuccess()
                ->withTitle('Payment updated')
                ->addToBody(lang('store.settings.updated') . ': ' . $gateway->getName())
                ->defer();

            ee()->functions->redirect(store_cp_url('settings', 'payment'));
        }

        $data = [];
        $data['post_url'] = store_cp_url() . '&sc=settings&sm=payment_edit&class=' . $class;
        $data['title'] = $gateway->getName();
        $data['short_name'] = $class;
        $data['default_settings'] = $gateway->getDefaultParameters();
        foreach ($data['default_settings'] as $key => $values) {
            if (is_array($values)) {
                $data['default_settings'][$key] = ['type' => 'select', 'options' => []];
                foreach ($values as $value) {
                    $data['default_settings'][$key]['options'][$value] = 'store.payment.' . Str::snake($key) . '.' . strtolower($value);
                }
            }
        }
        $data['settings'] = $gateway->getParameters();
        $data['enabled'] = $method && $method->enabled;

        return $this->render('settings/payment_edit', $data, 'payment', $title);
    }

    public function shipping()
    {
        $this->setTitle(lang('store.settings.shipping'));

        // handle form submit
        if (!empty($_POST['submit'])) {
            $selected_ids = (array)ee()->input->post('selected', true);
            /** @var ShippingMethod $selected */
            $selected = ShippingMethod::where('site_id', config_item('site_id'))->whereIn('id', $selected_ids);

            switch (ee()->input->post('with_selected')) {
                case 'enable':
                    $selected->update(['enabled' => 1]);
                    break;
                case 'disable':
                    $selected->update(['enabled' => 0]);
                    break;
                case 'delete':
                    // delete related shipping rules
                    ShippingRule::whereIn('shipping_method_id', $selected_ids)->delete();
                    $selected->delete();
            }

            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('settings', 'shipping'));
        }

        if (!empty($_POST['submit_default'])) {
            $settings = ee()->input->post('settings');
            ee()->store->config->update($settings);

            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('settings', 'shipping'));
        }

        // sortable ajax post
        if (!empty($_POST['sortable_ajax'])) {
            return $this->sortableAjax('\Store\Model\ShippingMethod');
        }

        $data = [];
        $data['shipping_methods'] = ShippingMethod::where('site_id', config_item('site_id'))->orderBy('sort')->get();
        $data['shipping_method_options'] = ['' => lang('store.none')];
        foreach ($data['shipping_methods'] as $method) {
            $data['shipping_method_options'][$method->id] = $method->name;
        }
        $data['default_shipping_method_id'] = config_item('store_default_shipping_method_id');

        $data['post_url'] = store_cp_url('settings', 'shipping');
        $data['edit_url'] = store_cp_url('settings', 'shipping_method') . '&id=';

        // Add shipping extensions
        $shipping_extensions = [];
        $shippingExtensionQuery = ee()->db->select('*')
            ->from('extensions')
            ->where('hook', 'store_order_shipping_methods')
            ->get();

        foreach ($shippingExtensionQuery->result() as $shippingExt) {
            $class = $shippingExt->class;
            $enabled = $shippingExt->enabled === 'y';
            $version = $shippingExt->version;

            // Guess addon name from class (e.g. Store_fedex_ext => store_fedex)
            $addon_name = strtolower(str_replace('_ext', '', $class));
            $settings_url = $enabled ? ee('CP/URL')->make('addons/settings/' . $addon_name)->compile() : null;

            // Check to see if this addon is installed
            $addon = ee('Addon')->get($addon_name);

            // If the addon is not installed, we'll add a message to the user
            if (empty($addon)) {
                $is_installed = false;
            } else {
                $is_installed = true;
            }

            $shipping_extensions[] = [
                'class' => $class,
                'enabled' => $enabled,
                'version' => $version,
                'settings_url' => $settings_url,
                'is_installed' => $is_installed,
            ];
        }
        $data['shipping_extensions'] = $shipping_extensions;

        return $this->render('settings/shipping', $data, 'shipping');
    }

    public function shipping_method()
    {
        ee()->load->library('form_validation');
        $this->addBreadcrumb(store_cp_url('settings', 'shipping'), lang('store.settings.shipping'));

        $method_id = ee()->input->get('id');

        /* @var ShippingMethod $method */
        if ($method_id == 'new') {
            $method = new ShippingMethod();
            $method->site_id = config_item('site_id');
            $method->enabled = 1;
            $method->sort = ShippingMethod::max('sort') + 1;
            $title = lang('store.shipping_method_add');
        } else {
            $method = ShippingMethod::where('site_id', config_item('site_id'))
                ->find(ee()->input->get('id'));
            if (!$method) {
                return show_404();
            }
            $title = lang('store.shipping_method_edit');
        }

        // handle form submit
        if (!empty($_POST['submit'])) {
            $method->fill(ee()->input->post('ship_method'));
            $rules = [
                [
                    'field' => 'ship_method[name]',
                    'label' => 'Name',
                    'rules' => 'required',
                ],
            ];
            ee()->form_validation->set_rules($rules);
            if (ee()->form_validation->run() == true) {
                $method->save();
                // redirect to current page
                ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
                ee()->functions->redirect(store_cp_url('settings', 'shipping_method', ['id' => $method->id]));
            }
        }
        if (!empty($_POST['submit_selected'])) {
            /** @var Builder $selected */
            $selected = ShippingRule::where('shipping_method_id', $method_id)
                ->whereIn('id', (array)ee()->input->post('selected', true));

            switch (ee()->input->post('with_selected')) {
                case 'enable':
                    $selected->update(['enabled' => 1]);
                    break;
                case 'disable':
                    $selected->update(['enabled' => 0]);
                    break;
                case 'delete':
                    $selected->delete();
                    break;
            }

            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('settings', 'shipping_method', ['id' => $method_id]));
        }

        // sortable ajax post
        if (!empty($_POST['sortable_ajax'])) {
            return $this->sortableAjax('\Store\Model\ShippingRule', false);
        }

        $data = [];
        $data['shipping_method'] = $method;
        $data['rules'] = $method->rules()->orderBy('sort')->get();
        $data['post_url'] = store_cp_url('settings', 'shipping_method', ['id' => $method_id]);
        $data['edit_url'] = store_cp_url('settings', 'shipping_rule', ['shipping_method_id' => $method_id]) . '&id=';

        ee()->lang->language['store.shipping_rule_per_weight_unit'] = sprintf(lang('store.shipping_rule_per_weight_unit'), config_item('store_weight_units'));

        return $this->render('settings/shipping_method', $data, 'shipping', $title);
    }

    public function shipping_rule()
    {
        /** @var ShippingMethod $method */
        $method = ShippingMethod::where('site_id', config_item('site_id'))
            ->find(ee()->input->get('shipping_method_id'));

        $this->addBreadcrumb(store_cp_url('settings', 'shipping'), lang('store.settings.shipping'));
        $this->addBreadcrumb(store_cp_url('settings', 'shipping_method', ['id' => $method->id]), $method->name);

        $rule_id = ee()->input->get('id');
        if ($rule_id == 'new') {
            $rule = new ShippingRule();
            $rule->shipping_method_id = $method->id;
            $rule->enabled = 1;
            $rule->sort = ShippingRule::max('sort') + 1;

            $this->setTitle(lang('store.shipping_rule_add'));
        } else {
            $rule = $method->rules()->find(ee()->input->get('id'));

            $this->setTitle(lang('store.shipping_rule_edit'));
        }

        // handle form submit
        $rule->fill((array)ee()->input->post('shipping_rule', true));
        if (!empty($_POST)) {
            $rule->save();

            ee()->session->set_flashdata(['message_success', lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('settings', 'shipping_method', ['id' => $method->id]));
        }

        $data = [];
        $data['shipping_rule'] = $rule;
        $data['country_options'] = ee()->store->shipping->get_enabled_country_options($rule->country_code, lang('store.any'));
        $data['state_options'] = ee()->store->shipping->get_enabled_state_options($rule->country_code, $rule->state_code, lang('store.any'));

        ee()->lang->language['store.shipping_rule_per_weight_rate'] = sprintf(lang('store.shipping_rule_per_weight_rate'), config_item('store_weight_units'));

        return $this->render('settings/shipping_rule', $data, 'shipping');
    }

    public function country()
    {
        $title = lang('store.settings.country');

        // handle form submit
        if (!empty($_POST['submit'])) {
            /** @var Country $selected */
            $selected = Country::where('site_id', config_item('site_id'))
                ->whereIn('id', (array)ee()->input->post('selected', true));

            switch (ee()->input->post('with_selected')) {
                case 'enable':
                    $selected->update(['enabled' => 1]);
                    break;
                case 'disable':
                    $selected->update(['enabled' => 0]);
                    break;
            }

            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('settings', 'country'));
        }

        if (!empty($_POST['submit_default'])) {
            $defaults = ee()->input->post('default', true);
            if (!is_array($defaults)) {
                $defaults = [];
            }
            ee()->store->config->update([
                'store_default_country' => isset($defaults['country_code']) ? $defaults['country_code'] : '',
                'store_default_state'   => isset($defaults['state_code']) ? $defaults['state_code'] : '',
            ]);

            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('settings', 'country'));
        }

        $data = [];
        $data['countries'] = Country::where('site_id', config_item('site_id'))->orderBy('name')->get();
        $data['post_url'] = store_cp_url() . '&sc=settings&sm=country';
        $data['edit_url'] = store_cp_url('settings', 'country_edit') . '&id=';

        $data['country_options'] = ee()->store->shipping->get_enabled_country_options(
            config_item('store_default_country'),
            lang('store.none')
        );
        $data['state_options'] = ee()->store->shipping->get_enabled_state_options(
            config_item('store_default_country'),
            config_item('store_default_state'),
            lang('store.none')
        );

        return $this->render('settings/country', $data, 'country', $title);
    }

    public function country_edit()
    {
        /** @var Country $country */
        $country = Country::where('site_id', config_item('site_id'))
            ->find(ee()->input->get('id'));

        if (empty($country)) {
            return show_404();
        }

        // handle form submit
        if (!empty($_POST)) {
            $states = [];
            $state_codes = [];
            foreach ((array)ee()->input->post('states') as $key => $row) {
                // find or initialize state
                $state_id = isset($row['id']) ? $row['id'] : null;
                $state = State::where('country_id', $country->id)->find($state_id) ?: new State();
                $state->site_id = $country->site_id;
                $state->country_id = $country->id;
                $state->fill($row);
                $states[$key] = $state;

                // don't validate rows scheduled for deletion
                if ($state->delete) {
                    continue;
                }
                // add required fields

                //below lines commented becoz this type of rule is not working in EE3. We added jQuery validation
                //ee()->form_validation->set_rules("states[$key][name]", 'lang:name', 'required');
                //ee()->form_validation->set_rules("states[$key][code]", 'lang:store.code', 'required|max_length[5]');

                // check for duplicate codes
                if (!empty($row['code'])) {
                    $code = strtoupper($row['code']);
                    if (isset($state_codes[$code])) {
                        //$duplicate_key = $state_codes[$code];
                        //ee()->form_validation->add_error("states[$duplicate_key][code]", lang('store.duplicate_code'));
                        //ee()->form_validation->add_error("states[$key][code]", lang('store.duplicate_code'));
                    } else {
                        $state_codes[$code] = $key;
                    }
                }
            }

            // must have at least one validation rule for run() to return true
            $rules = ['submit' => 'required'];

            $result = ee('Validation')->make($rules)->validate($_POST);
            //if (ee()->form_validation->run()) {
            if ($result->isValid()) {
                foreach ($states as $key => $state) {
                    if ($state->delete) {
                        $state->exists && $state->delete();
                    } else {
                        $state->save();
                    }
                }
                ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
                ee()->functions->redirect(store_cp_url('settings', 'country'));
            } else {
                print_r($result->getAllErrors());
                exit;
            }
        } else {
            $states = $country->states()->orderBy('name')->get();
        }

        $this->addBreadcrumb(store_cp_url('settings', 'country'), lang('store.settings.country'));
        $this->setTitle($country->name);

        $data = [];
        $data['country'] = $country;
        $data['states'] = $states;
        $data['post_url'] = store_cp_url() . '&sc=settings&sm=country_edit&id=' . $country->id;
        $data['form_name'] = 'country_edit';

        return $this->render('settings/country_edit', $data, 'country');
    }

    public function tax()
    {
        $this->setTitle(lang('store.settings.tax'));

        // handle form submit
        if (!empty($_POST['submit'])) {
            /** @var Tax $selected */
            $selected = Tax::where('site_id', config_item('site_id'))
                ->whereIn('id', (array)ee()->input->post('selected', true));

            switch (ee()->input->post('with_selected')) {
                case 'enable':
                    $selected->update(['enabled' => 1]);
                    break;
                case 'disable':
                    $selected->update(['enabled' => 0]);
                    break;
                case 'delete':
                    $selected->delete();
                    break;
            }

            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('settings', 'tax'));
        }

        // sortable ajax post
        if (!empty($_POST['sortable_ajax'])) {
            return $this->sortableAjax('\Store\Model\Tax');
        }

        $data = [];
        $data['post_url'] = store_cp_url() . '&sc=settings&sm=tax';
        $data['tax_rates'] = Tax::where('site_id', config_item('site_id'))->orderBy('sort')->get();
        $data['edit_link'] = store_cp_url('settings', 'tax_edit') . '&id=';

        return $this->render('settings/tax', $data, 'tax');
    }

    public function tax_edit()
    {
        ee()->load->library('form_validation');
        $tax_id = ee()->input->get('id');

        if ($tax_id == 'new') {
            $tax = new Tax();
            $tax->site_id = config_item('site_id');
            $tax->enabled = 1;
            $tax->sort = Tax::max('sort') + 1;
        } else {
            $tax = Tax::where('site_id', config_item('site_id'))->find($tax_id);

            if (empty($tax)) {
                return show_404();
            }
        }

        // handle form submit
        $tax->fill((array)ee()->input->post('tax', true));

        $rules = [
            [
                'field' => 'tax[name]',
                'label' => 'Tax Name',
                'rules' => 'callback_textname_check|required',
            ],
        ];

        ee()->form_validation->set_rules($rules);

        if (ee()->form_validation->run() == true) {
            $tax->save();
            $tax->categories()->sync(array_filter($_POST['tax']['category_ids']));
            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('settings', 'tax'));
        }

        $data = [];
        $data['tax'] = $tax;
        $data['country_options'] = ee()->store->shipping->get_enabled_country_options($tax->country_code, lang('store.any'));
        $data['state_options'] = ee()->store->shipping->get_enabled_state_options($tax->country_code, $tax->state_code, lang('store.any'));
        $data['category_options'] = ee()->store->products->get_categories();

        return $this->render('settings/tax_edit', $data, 'tax');
    }

    public function security()
    {
        $roles = ee('Model')->get('Role')
            ->filter('role_id', 'IN', (ee('Permission')->rolesThatCan('access_cp')))
            ->all()
            ->getDictionary('role_id', 'name');

        $title = lang('store.settings.security');
        $data = [
            'post_url'     => store_cp_url() . '&sc=settings&sm=security',
            'security'     => ee()->store->config->security(),
            'member_roles' => $roles,
        ];

        if (!empty($_POST)) {
            $security_settings = ee()->input->post('security', true);
            ee()->store->config->update(['store_security' => $security_settings]);
            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect($data['post_url']);
        }

        return $this->render('settings/security', $data, 'security', $title);
    }
}
