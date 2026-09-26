<?php

namespace EEHarbor\Rating\Model;

class Field extends BaseModel
{
    protected static $_primary_key  = 'field_id';
    protected static $_table_name   = 'rating_fields';

    // Properties
    protected $field_id;
    protected $field_name;
    protected $field_label;
    protected $field_type;
    protected $field_list_items;
    protected $field_maxl;
    protected $field_search;
    protected $field_fmt;
    protected $field_order;

    protected $_default_prefs = array(
        'field_id'  => array(
            'type'      => 'hidden',
            'default'   => '',
        ),
        'field_type'    => array(
            'type'      => 'select',
            'default'   => 'number',
            'choices'   => array(
                'number'    => 'Number',
                'text'      => 'Text Input',
                'textarea'  => 'Textarea',
            ),
            'validate'  => 'required',
        ),
        'field_label'   => array(
            'type'      => 'text',
            'default'   => '',
            'validate'  => 'required',
            'required'  => true
        ),
        'field_name'    => array(
            'type'      => 'text',
            'default'   => '',
            'validate'  => 'required|alphaDash|unique_name|allowed_name',
            'required'  => true
        ),
        'field_fmt' => array(
            'type'      => 'select',
            'default'   => 'none',
            'choices'   => array(
                'none'  => 'None',
                'br'        => 'Auto &lt;br />',
                'xhtml'     => 'XHTML'
            ),
            'validate'  => 'required',
        ),
        'field_maxl'    => array(
            'type'      => 'short-text',
            'label'     => '',
            'default'   => '10',
            'validate'  => 'required|integer|correct_maxl',
        ),
        'field_order'   => array(
            'type'      => 'short-text',
            'label'     => '',
            'default'   => '1',
            'validate'  => 'required|integer'
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

    public function validateDefaultPrefs($instance, $required = array())
    {
        //not a typo, see get__default_prefs
        $prefsData = $this->default_prefs;

        //validator
        $validator  = ee('Validation')
            ->make();

        //unique name
        $validator->defineRule('unique_name', function ($key, $value, $parameters) use ($instance) {
            if ($key != 'field_name') {
                return true;
            }

            $count  = ee('Model')
                ->get('rating:Field')
                ->filter('field_name', $value);

            if (! empty($instance['field_id'])) {
                $count->filter('field_id', '!=', $instance['field_id']);
            }

            $count  = $count->count();

            if (! empty($count)) {
                return str_replace('%name%', $value, lang('field_name_exists'));
            }

            return true;
        });

        //correct maxl
        $validator->defineRule('correct_maxl', function ($key, $value, $parameters) use ($instance) {
            if ($key != 'field_maxl') {
                return true;
            }

            if ($instance['field_type'] == 'text' and $value > 255) {
                return str_replace(array('%label%', '%x%'), array($instance['field_label'], 255), lang('field_too_long'));
            }

            if ($instance['field_type'] == 'number' and $value > 10) {
                return str_replace(array('%label%', '%x%'), array($instance['field_label'], 10), lang('field_too_long'));
            }

            return true;
        });

        //prohibited names
        $validator->defineRule('allowed_name', function ($key, $value, $parameters) use ($instance) {
            if ($key != 'field_name') {
                return true;
            }

            $exclude    = array('rating_id', 'entry_id', 'entry_title', 'rating_author_id', 'ip_address',
                'collection', 'name', 'rating_date', 'edit_date', 'url', 'status', 'email',
                'location', 'rating_review', 'notify', 'rating', 'review');

            if (in_array(strtolower($instance['field_name']), $exclude)) {
                return str_replace('%name%', $instance['field_name'], lang('reserved_field_name'));
            }

            return true;
        });

        //rules
        $rules = array();

        foreach ($prefsData as $name => $data) {
            if (isset($data['validate'])) {
                $r = (in_array($name, $required)) ? 'required|' : '';

                $rules[$name] = $r . $data['validate'];
            }
        }

        $validator->setRules($rules);

        return $validator->validate($instance);
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
     * delete_fields()
     *
     * Delete the fields and columns as needed.
     *
     * @access  public
     * @return  bool
     */

    public function delete_fields($field_ids)
    {
        //---------------------------------------------
        //  Grab the model instances
        //---------------------------------------------

        $fields = ee('Model')
            ->get('rating:Field')
            ->filter('field_id', 'IN', $field_ids)
            ->all();

        //---------------------------------------------
        //  Remove the other related DB columns
        //---------------------------------------------

        $q = ee()->db->query("SHOW COLUMNS FROM exp_rating_stats");
        $exp_rating_stats_cols = array();

        foreach ($q->result_array() as $r) {
            $exp_rating_stats_cols[] = $r['Field'];
        }

        $q = ee()->db->query("SHOW COLUMNS FROM exp_ratings");
        $exp_ratings_cols = array();

        foreach ($q->result_array() as $r) {
            $exp_ratings_cols[] = $r['Field'];
        }

        foreach ($fields as $field) {
            if (in_array($field->field_name, $exp_ratings_cols)) {
                ee()->db->query("ALTER TABLE exp_ratings DROP `" . ee()->db->escape_str($field->field_name) . "`");
            }

            if (in_array('sum_' . $field->field_id, $exp_rating_stats_cols)) {
                ee()->db->query("ALTER TABLE exp_rating_stats DROP sum_" . ee()->db->escape_str($field->field_id));
            }

            if (in_array('total_' . $field->field_id, $exp_rating_stats_cols)) {
                ee()->db->query("ALTER TABLE exp_rating_stats DROP total_" . ee()->db->escape_str($field->field_id));
            }

            if (in_array('count_' . $field->field_id, $exp_rating_stats_cols)) {
                ee()->db->query("ALTER TABLE exp_rating_stats DROP count_" . ee()->db->escape_str($field->field_id));
            }

            if (in_array('avg_' . $field->field_id, $exp_rating_stats_cols)) {
                ee()->db->query("ALTER TABLE exp_rating_stats DROP avg_" . ee()->db->escape_str($field->field_id));
            }
        }

        //---------------------------------------------
        //  Now delete
        //---------------------------------------------

        $fields->delete();

        //---------------------------------------------
        //  Return
        //---------------------------------------------

        return true;
    }

    //END delete_fields()
}
//END Field
