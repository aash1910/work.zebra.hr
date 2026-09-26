<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}


if (! class_exists('Fieldpack_Fieldtype')) {
    require PATH_THIRD . 'fieldpack/fieldpack_fieldtype.php';
}


/**
 * Field Pack - Radio Buttons Class
 *
 * @package   P&T Field Pack
 * @author      Tom Jaeger <Tom@EEHarbor.com>
 * @copyright       Copyright (c) 2016, Tom Jaeger/EEHarbor
 */
class Fieldpack_radio_buttons_ft extends Fieldpack_Multi_Fieldtype
{
    public $cache;
    public $settings;
    public $element_name;
    public $class = 'fieldpack_radio_buttons';
    public $info = array(
        'name' => 'Field Pack - Radio Buttons',
        'version' => FIELDPACK_VERSION
    );

    // --------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        $this->info['name'] = 'Field Pack - Radio Buttons';

        /** ----------------------------------------
        /**  Prepare Cache
        /** ----------------------------------------*/

        if (! isset(ee()->session->cache['fieldpack_radio_buttons'])) {
            ee()->session->cache['fieldpack_radio_buttons'] = array('includes' => array());
        }
        $this->cache =& ee()->session->cache['fieldpack_radio_buttons'];
    }

    /**
     * Install
     */
    public function install()
    {
        if (! class_exists('FF2EE2')) {
            require PATH_THIRD . 'fieldpack/ff2ee2/ff2ee2.php';
        }

        new FF2EE2(array('ff_radio_group', 'fieldpack_radio_buttons'));

        $this->helper->convert_types('pt_radio_buttons', 'fieldpack_radio_buttons');
        $this->helper->uninstall_fieldtype('pt_radio_buttons');
        $this->helper->disable_extension();

        return array();
    }

    // --------------------------------------------------------------------

    /**
     * Prep Field Data
     */
    public function prep_field_data(&$data)
    {
        if (is_array($data)) {
            $data = array_shift($data);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Display Field
     */
    public function _display_field($data, $field_name)
    {
        $this->_include_theme_css('styles/fp-multi.css');

        if (empty($this->settings['options'])) {
            return $this->no_options_set();
        }

        $this->prep_field_data($data);

        $r = '';

        foreach ($this->settings['options'] as $option_name => $option) {
            $selected = ((string) $option_name === (string) $data);
            $r .= '<label>'
                .   form_radio($field_name, $option_name, $selected)
                .   NBS . $option
                . '</label>';
        }

        return $r;
    }

    /**
     * Display the element.
     *
     * @param $data
     * @return mixed
     */
    public function display_element($data)
    {
        $this->_include_ce_icon('radio_buttons');
        return $this->display_field($data);
    }

    // --------------------------------------------------------------------

    /**
     * Replace Tag
     */
    public function replace_tag($data, $params = array(), $tagdata = false)
    {
        $this->prep_field_data($data);

        return $data;
    }

    // --------------------------------------------------------------------

    /**
     * Option Label
     */
    public function replace_label($data)
    {
        $this->prep_field_data($data);

        return $this->settings['options'][$data];
    }

    // Support for Content Elements Fieldtype

    /**
     * Render the element.
     *
     * @param $data
     * @param array $params
     * @param $tagdata
     * @return bool
     */
    public function replace_element_tag($data, $params = array(), $tagdata = '')
    {
        $label = $data;

        // Defensively load label value
        if (isset($this->settings['options'][$data])) {
            $label = $this->settings['options'][$data];
        }

        $value = $data;

        $replace = array(
            'value' => $value,
            'label' => $label,
            'element_name' => $this->element_name
        );

        return ee()->functions->var_swap($tagdata, $replace);
    }
}
