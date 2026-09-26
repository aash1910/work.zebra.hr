<?php

namespace EEHarbor\Rating\Model;

class Template extends BaseModel
{
    protected static $_primary_key  = 'template_id';
    protected static $_table_name   = 'exp_rating_templates';

    // Properties
    protected $template_id;
    protected $enable_template;
    protected $wordwrap;
    protected $template_name;
    protected $template_label;
    protected $subject;
    protected $message;

    protected $_default_prefs = array(
        'template_id'   => array(
            'type'      => 'hidden',
            'default'   => ''
        ),
        'template_label'    => array(
            'type'      => 'text',
            'default'   => '',
            'validate'  => 'required',
            'required'  => true
        ),
        'template_name' => array(
            'type'      => 'text',
            'default'   => '',
            'validate'  => 'required|alphaDash',
            'required'  => true
        ),
        'wordwrap'  => array(
            'type'      => 'yes_no',
            'default'   => 'y',
            'validate'  => 'enum[y,n]',
            'validate'  => 'required'
        ),
        'subject'   => array(
            'type'      => 'text',
            'default'   => '',
            'validate'  => 'required',
            'required'  => true
        ),
        'message'   => array(
            'type'      => 'textarea',
            'default'   => '',
            'validate'  => 'required',
            'required'  => true
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
}
//END Template
