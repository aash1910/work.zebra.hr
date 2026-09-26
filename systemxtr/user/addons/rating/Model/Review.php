<?php

namespace EEHarbor\Rating\Model;

use EEHarbor\Rating\Library\AddonBuilderClass;

class Review extends BaseModel
{
    protected static $_primary_key  = 'review_id';
    protected static $_table_name   = 'rating_reviews';

    // Properties
    protected $review_id;
    protected $site_id;
    protected $rating_id;
    protected $entry_id;
    protected $channel_id;
    protected $author_id;
    protected $ip_address;
    protected $status;
    protected $name;
    protected $email;
    protected $url;
    protected $location;
    protected $rating_helpful;
    protected $rating_review;
    protected $review_date;

    protected static $_relationships = array(
        'Rating' => array(
            'model'     => 'Rating',
            'type'      => 'BelongsTo',
            'from_key'  => 'rating_id',
            'to_key'    => 'rating_id',
            'weak'      => false
        )
    );

    protected $_default_prefs = array(
        'review_id' => array(
            'type'      => 'hidden',
            'default'   => '',
        ),
        'status'    => array(
            'type'      => 'select',
            'choices'   => array(
                'open'      => 'Open',
                'closed'    => 'Closed'
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
        ),
        'rating_review' => array(
            'type'      => 'textarea',
            'default'   => '',
        ),
    );

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

    // --------------------------------------------------------------------

    /**
     * recount_rating_reviews()
     *
     * @access  public
     * @param   array   $rating_id
     */

    public function recount_rating_reviews($rating_id)
    {
        $y_count    = 0;
        $rating_id  = (is_array($rating_id)) ? $rating_id : array($rating_id);

        $aob        = new AddonBuilderClass();

        foreach ($rating_id as $id) {
            $y_count    = $aob->fetch('Review')
                ->filter('rating_id', $id)
                ->filter('rating_helpful', 'y')
                ->count();

            $n_count    = $aob->fetch('Review')
                ->filter('rating_id', $id)
                ->filter('rating_helpful', 'n')
                ->count();

            //  These are considered comments
            $comment_count  = $aob->fetch('Review')
                ->filter('rating_id', $id)
                ->filter('rating_helpful', '')
                ->filter('rating_review', '!=', '')
                ->count();

            $aob    = new AddonBuilderClass();

            $rating = $aob->fetch('Rating', $id)
                ->first();

            $rating->rating_helpful_y       = $y_count;
            $rating->rating_helpful_n       = $n_count;
            $rating->rating_review_count    = $comment_count;
            $rating->save();
        }

        return $y_count;
    }

    //END recount_rating_reviews()
}
//END Review
