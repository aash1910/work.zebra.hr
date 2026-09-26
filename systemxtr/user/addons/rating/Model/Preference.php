<?php

namespace EEHarbor\Rating\Model;

class Preference extends BaseModel
{
    protected static $_primary_key  = 'preference_id';
    protected static $_table_name   = 'exp_rating_preferences';

    protected $preference_id;
    protected $preference_name;
    protected $preference_value;
    protected $site_id;

    protected $_default_prefs = array(
        'enabled_channels'  => array(
            'type'      => 'multiselect|channel',
            'default'   => array()
        ),
        'can_post_ratings'  => array(
            'type'      => 'multiselect|member_group',
            'default'   => array()
        ),
        'quarantine_minimum'    => array(
            'type'      => 'short-text',
            'label'     => '',
            'default'   => '3',
            'validate'  => 'required|integer',
            'required'  => true
        ),
        'can_report_ratings'    => array(
            'type'      => 'multiselect|member_group',
            'default'   => array()
        ),
        'can_delete_ratings'    => array(
            'type'      => 'multiselect|member_group',
            'caution'   => true,
            'default'   => array()
        ),
        'require_email' => array(
            'type'      => 'yes_no',
            'default'   => 'n',
            'validate'  => 'enum[y,n]'
        ),
        'use_captcha'   => array(
            'type'      => 'yes_no',
            'default'   => 'n',
            'validate'  => 'enum[y,n]'
        ),
    );

    // --------------------------------------------------------------------

    /**
     * Validate Default Prefs
     *
     * Since we often use multiple prefs, lets validate default prefs
     * and leave alone the ones not meant to be in the prefs page
     *
     * @access  public
     * @param   array   $inputs     incoming inputs to validate
     * @param   array   $required   array of names of required items
     * @return  object              instance of validator result
     */

    public function validateDefaultPrefs($inputs = array(), $required = array())
    {
        //not a typo, see get__default_prefs
        $prefsData = $this->default_prefs;

        $rules = array();

        foreach ($prefsData as $name => $data) {
            if (isset($data['validate'])) {
                $r = (in_array($name, $required)) ? 'required|' : '';

                $rules[$name] = $r . $data['validate'];
            }
        }

        return ee('Validation')->make($rules)->validate($inputs);
    }

    //END validateDefaultPrefs

    // --------------------------------------------------------------------

    /**
     * Getter: default_prefs
     *
     * loads items with lang lines and choices before sending off.
     * (Requires ee('Model')->make() to access.)
     *
     * @access  public
     * @return  array       key->value array of pref names and defaults
     */

    public function get__default_prefs()
    {
        //just in case this gets removed in the future.
        if (isset(ee()->lang) && method_exists(ee()->lang, 'loadfile')) {
            ee()->lang->loadfile('rating');
        }

        $prefs = $this->_default_prefs;

        return $prefs;
    }

    //END get__default_prefs

    // --------------------------------------------------------------------

    /**
     * Getter: preference_value
     *
     * @access  public
     * @return  mixed
     */

    public function get__preference_value()
    {
        if (! isset($this->_default_prefs[$this->preference_name])) {
            return false;
        }

        if (strpos($this->_default_prefs[$this->preference_name]['type'], 'multiselect') !== false) {
            return explode('|', $this->preference_value);
        }

        return $this->preference_value;
    }

    //END get__preference_value
}
//END Preference
