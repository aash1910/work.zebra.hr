<?php

namespace EEHarbor\Rating\Model;

class Rating extends BaseModel
{
    protected static $_primary_key  = 'rating_id';
    protected static $_table_name   = 'ratings';

    // Properties
    protected $rating_id;
    protected $rated_rating_id;
    protected $entry_id;
    protected $channel_id;
    protected $rating_author_id;
    protected $quarantine;
    protected $collection;
    protected $status;
    protected $name;
    protected $email;
    protected $url;
    protected $location;
    protected $ip_address;
    protected $rating_date;
    protected $edit_date;
    protected $rating_review_count;
    protected $rating_review;
    protected $rating_helpful_y;
    protected $rating_helpful_n;
    protected $rating;
    protected $review;
    protected $notify;
    protected $sticky;

    protected static $_relationships = array(
        'Quarantine' => array(
            'model'     => 'Quarantine',
            'type'      => 'HasOne',
            'from_key'  => 'rating_id',
            'to_key'    => 'rating_id',
            'weak'      => false
        ),
        'Review' => array(
            'model'     => 'Review',
            'type'      => 'HasMany',
            'from_key'  => 'rating_id',
            'to_key'    => 'rating_id',
            'weak'      => false
        ),
        'ChannelEntry' => array(
            'model'     => 'ee:ChannelEntry',
            'type'      => 'BelongsTo',
            'from_key'  => 'entry_id',
            'to_key'    => 'entry_id',
            'weak'      => false,
            'inverse'   => array(
                'name'  => 'Rating',
                'type'  => 'hasMany',
                'from_key'  => 'entry_id',
                'to_key'    => 'entry_id',
            )
        )
    );

    protected $_field_list_cache;

    protected $_default_prefs = array(
        'rating_id' => array(
            'type'      => 'hidden',
            'default'   => '',
        ),
        'quarantine'    => array(
            'type'      => 'yes_no',
            'default'   => 'n',
            'validate'  => 'enum[y,n]'
        ),
        'collection'    => array(
            'type'      => 'text',
            'default'   => '',
        ),
        'status'    => array(
            'type'      => 'select',
            'choices'   => array(
                'open'      => 'Open',
                'closed'    => 'Closed',
                'reported'  => 'Reported'
            ),
            'default'   => '',
        ),
        'name'  => array(
            'type'      => 'text',
            'default'   => '',
        ),
        'email' => array(
            'type'      => 'text',
            'default'   => '',
        )
    );

    // --------------------------------------------------------------------

    /**
     * We have a variable number of columns so we have to
     * get a list of fields every first time this is called.
     *
     * Shamelessly stolen from:
     * /EllisLab/ExpressionEngine/Model/Content/VariableColumnGateway.php
     *
     * @access  public
     * @return  array   array of columns for this table
     */

    public function getFieldList()
    {
        if (! isset($this->_field_list_cache)) {
            $all = ee('Database')
                ->newQuery()
                ->list_fields($this->getTableName());

            $known = parent::getFieldList();
            //$known    = array();

            $this->_field_list_cache = array_merge($known, $all);
        }

        return $this->_field_list_cache;
    }
    //END getFieldList

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
        if (isset(ee()->lang) and method_exists(ee()->lang, 'loadfile')) {
            ee()->lang->loadfile('rating');
        }

        $prefs = $this->_default_prefs;

        return $prefs;
    }

    //END get__default_prefs

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
}
//END Rating
