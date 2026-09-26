<?php

use EEHarbor\Rating\Library\Utils;
use EEHarbor\Rating\FluxCapacitor\Base\Ext;
use EllisLab\ExpressionEngine\Model\Channel\ChannelEntry;

class Rating_ext extends Ext
{
    use \EEHarbor\Rating\Library\AddonBuilderTrait;

    // public $settings        = array();
    public $name            = 'Rating';
    public $version         = '';
    public $description     = '';
    public $settings_exist  = 'n';
    public $docs_url        = "http://solspace.com/docs/";
    public $required_by     = array('module');

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access  public
     * @return  null
     */

    public function __construct($settings = array())
    {
        parent::__construct();

        // instantiate the addonBuilder "construct"
        $this->addonBuilderConstruct('extension');

        // --------------------------------------------
        //  Settings
        // --------------------------------------------

        $this->settings = $settings;
    }

    // END constructor

    // --------------------------------------------------------------------

    /**
     * On channel entry deletion - its ratings get deleted
     *
     * @param ChannelEntry $entry
     *
     * @return bool
     */
    public function before_channel_entry_delete(ChannelEntry $entry)
    {
        $ratings = $this
            ->fetch('Rating')
            ->filter('entry_id', 'IN', $entry->entry_id)
            ->all();

        if (!$ratings) {
            return false;
        }

        $channelIds = $entryIds = $memberIds = $ratingIds = array();
        foreach ($ratings as $rating) {
            $channelIds[] = $rating->channel_id;
            $entryIds[]   = $rating->entry_id;
            $memberIds[]  = $rating->rating_author_id;
            $ratingIds[]  = $rating->rating_id;
        }

        // --------------------------------------------
        //  Delete the Ratings..GONE...buh-bye!
        // --------------------------------------------

        if (!empty($entryIds)) {
            $this->fetch('Rating')
                 ->filter('entry_id', 'IN', $entryIds)
                 ->delete();

            $this->fetch('Stat')
                 ->filter('entry_id', 'IN', $entryIds)
                 ->delete();
        }

        Utils::update_member_stats($memberIds);
        Utils::update_channel_stats($channelIds);
        Utils::update_entry_stats($entryIds);

        return true;
    }

    /**
     * Modify SQL
     *
     * This alters the $end variable for the SQL query that grabs channel entries.
     *
     * @param   str $end
     * @return  str
     */

    public function modify_order_by_sql($end, $sql = '')
    {
        // --------------------------------------------
        //  Set return end
        // --------------------------------------------

        $end = $this->get_last_call($end);

        // --------------------------------------------
        //  Should we even execute?
        // --------------------------------------------

        if (! ee()->TMPL->fetch_param('orderby_ratings') or ee()->TMPL->fetch_param('orderby_ratings') == '') {
            return $end;
        }

        // --------------------------------------------
        //  Sort rated entries before or after non-rated entries
        // --------------------------------------------

        if (ee()->TMPL->fetch_param('sort_ratings') and in_array(ee()->TMPL->fetch_param('sort_ratings'), array('asc', 'desc'))) {
            $sort_ratings = strtoupper(ee()->TMPL->fetch_param('sort_ratings'));
        } else {
            $sort_ratings = 'DESC';
        }

        // --------------------------------------------
        //  Is the ratings module running?
        // --------------------------------------------

        if ($this->database_version() === false) {
            return $end;
        }

        /*
        // Example $sql variable

        FROM exp_channel_titles AS t
        LEFT JOIN exp_channels ON t.channel_id = exp_channels.channel_id
        LEFT JOIN exp_members AS m ON m.member_id = t.author_id
        WHERE t.entry_id !=''
        AND t.site_id IN ('1')
        AND t.entry_date < 1302975459
        AND (t.expiration_date = 0 OR t.expiration_date > 1302975459)
        AND t.channel_id = '1' AND t.status = 'open'
        */

        // --------------------------------------------
        //  Modify order by
        // --------------------------------------------

        if ($sql != '') {
            $sql = "SELECT exp_rating_stats.entry_id FROM exp_rating_stats, " . substr(trim($sql), 5) .
                    " AND t.entry_id = exp_rating_stats.entry_id AND exp_rating_stats.entry_id != 0
                    ORDER BY exp_rating_stats.avg DESC";
        } else {
            $sql = "SELECT entry_id FROM exp_rating_stats WHERE entry_id != 0 ORDER BY avg DESC";
        }

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            return $end;
        }

        foreach ($query->result_array() as $row) {
            $ids[] = $row['entry_id'];
        }

        // Since not all entry_ids are in the ORDER BY FIELD() command,
        // do this trick: invert the $ids array to order entries
        // without a rating *after* rated entries (in the case of $sort_ratings = DESC)
        $ids = array_reverse($ids);

        return str_replace('ORDER BY', 'ORDER BY FIELD(t.entry_id, ' . implode(',', $ids) . ') ' . $sort_ratings . ', ', $end);
    }

    // END modify_sql()
}
// END Class Rating_ext
