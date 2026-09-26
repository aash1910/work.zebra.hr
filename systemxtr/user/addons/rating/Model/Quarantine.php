<?php

namespace EEHarbor\Rating\Model;

class Quarantine extends BaseModel
{
    protected static $_primary_key  = 'quarantine_id';
    protected static $_table_name   = 'exp_rating_quarantine';

    // Properties
    protected $quarantine_id;
    protected $rating_id;
    protected $entry_id;
    protected $channel_id;
    protected $member_id;
    protected $rating_author_id;
    protected $status;
    protected $entry_date;
    protected $edit_date;

    protected static $_relationships = array(
        'Rating' => array(
            'model'     => 'Rating',
            'type'      => 'HasOne',
            'from_key'  => 'rating_id',
            'to_key'    => 'rating_id',
            'weak'      => false
        )
    );
}
//END Quarantine
