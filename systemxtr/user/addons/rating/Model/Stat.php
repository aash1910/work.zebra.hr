<?php

namespace EEHarbor\Rating\Model;

class Stat extends BaseModel
{
    protected static $_primary_key  = 'stat_id';
    protected static $_table_name   = 'exp_rating_stats';

    // Properties
    protected $stat_id;
    protected $entry_id;
    protected $channel_id;
    protected $member_id;
    protected $collection;
    protected $last_rating_date;
    protected $count;
    protected $sum;
    protected $avg;
    protected $count_1;
    protected $sum_1;
    protected $avg_1;
    protected $count_2;
    protected $sum_2;
    protected $avg_2;

    protected $_field_list_cache;

    protected static $_relationships = array(
        'ChannelEntry' => array(
            'model'     => 'ee:ChannelEntry',
            'type'      => 'BelongsTo',
            'from_key'  => 'entry_id',
            'to_key'    => 'entry_id',
            'weak'      => true,
            'inverse'   => array(
                'name'  => 'Stat',
                'type'  => 'hasMany',
                'from_key'  => 'entry_id',
                'to_key'    => 'entry_id',
            )
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

            $this->_field_list_cache = array_merge($known, $all);
        }

        return $this->_field_list_cache;
    }

    //END getFieldList

    // --------------------------------------------------------------------

    /**
     * recount_stats()
     *
     * @access  public
     * @return  array   array of vars to be returned to a view file
     */

    public function recount_stats($row, $limit, $run)
    {
        // --------------------------------------------
        //  Remove Stats for Non-Rated Entries
        // --------------------------------------------

        if ($row == 0 and $run) {
            ee()->db->query("DELETE FROM exp_ratings WHERE entry_id != 0 AND entry_id NOT IN (SELECT entry_id FROM exp_channel_titles)");

            ee()->db->query("DELETE FROM exp_rating_stats WHERE entry_id != 0 AND entry_id NOT IN (SELECT entry_id FROM exp_ratings)");

            ee()->db->query("DELETE FROM exp_rating_stats WHERE channel_id != 0 AND channel_id NOT IN (SELECT channel_id FROM exp_ratings)");
        }

        // --------------------------------------------
        //  Entry Statistics
        // --------------------------------------------

        $query  = ee()->db->query("SELECT COUNT(DISTINCT entry_id) AS total_count FROM exp_ratings");
        $total  = $query->row('total_count');

        if ($run) {
            $query  = ee()->db->query("SELECT DISTINCT entry_id FROM exp_ratings ORDER BY entry_id LIMIT {$row}, {$limit}");

            foreach ($query->result_array() as $data_row) {
                // How damn! Look at that abstraction, baby!
                $this->update_entry_stats($data_row['entry_id']);
            }
        }

        // --------------------------------------------
        //  Member Statistics
        // --------------------------------------------

        $query  = ee()->db->query("SELECT COUNT(DISTINCT rating_author_id) AS total_count FROM exp_ratings");
        $total  = ($total < $query->row('total_count')) ? $query->row('total_count') : $total;

        if ($run) {
            $query  = ee()->db->query("SELECT DISTINCT rating_author_id FROM exp_ratings ORDER BY rating_author_id LIMIT {$row}, {$limit}");

            foreach ($query->result_array() as $data_row) {
                $this->update_member_stats($data_row['rating_author_id']);
            }
        }

        // --------------------------------------------
        //  Channel/Weblog Statistics
        // --------------------------------------------

        $query  = ee()->db->query("SELECT COUNT(DISTINCT channel_id) AS total_count FROM exp_ratings");
        $total  = ($total < $query->row('total_count')) ? $query->row('total_count') : $total;

        if ($run) {
            $query  = ee()->db->query("SELECT DISTINCT channel_id FROM exp_ratings ORDER BY channel_id LIMIT {$row}, {$limit}");

            foreach ($query->result_array() as $data_row) {
                $this->update_channel_stats($data_row['channel_id']);
            }
        }

        // --------------------------------------------
        //  Return
        // --------------------------------------------

        return compact('total', 'row');
    }

    //END recount_stats()
    // --------------------------------------------------------------------

    /**
     *  Update Member Stats
     *
     * @deprecated Please use the Utils::update_member_stats() instead.
     *
     *  @access     public
     *  @param      integer
     *  @return     boolean
     */

    public function update_member_stats($member_id = '')
    {
        if (empty($member_id)) {
            return false;
        }

        if (is_array($member_id)) {
            foreach (array_unique($member_id) as $id) {
                $this->update_member_stats($id);
            }

            return;
        }

        ee()->db->delete('rating_stats', array('member_id' => $member_id));

        $sql = "SELECT COUNT(*) as `count`, MAX(`rating_date`) as rating_date
                FROM exp_ratings
                WHERE quarantine != 'y' AND status != 'closed'
                AND rating_author_id = '" . ee()->db->escape_str($member_id) . "'";

        $query = ee()->db->query($sql);

        $arr    = array('entry_id'          => 0,
                        'channel_id'        => 0,
                        'collection'        => 'all',
                        'member_id'         => $member_id,
                        'last_rating_date'  => ($query->row('rating_date') == null) ? 0 : $query->row('rating_date'),
                        'count'             => ($query->num_rows() == 0) ? 0 : $query->row('count'));

        $sql    = ee()->db->insert_string('exp_rating_stats', $arr);

        $sql    .= " ON DUPLICATE KEY UPDATE last_rating_date = VALUES(last_rating_date), `count` = VALUES(`count`)";

        ee()->db->query($sql);
    }

    // END update_member_stats()

    // --------------------------------------------------------------------

    /**
     *  Update Channel Stats
     *
     * @deprecated Please use the Utils::update_channel_stats() instead.
     *
     *  @access     public
     *  @param      integer
     *  @return     boolean
     */

    public function update_channel_stats($channel_id = '')
    {
        if (empty($channel_id)) {
            return false;
        }

        if (is_array($channel_id)) {
            foreach (array_unique($channel_id) as $id) {
                $this->update_channel_stats($id);
            }

            return;
        }

        $sql = "SELECT COUNT(*) as `count`, MAX(`rating_date`) as rating_date
                FROM exp_ratings
                WHERE quarantine != 'y' AND status != 'closed'
                AND channel_id = '" . ee()->db->escape_str($channel_id) . "'";

        ee()->db->delete('rating_stats', array('entry_id' => 0, 'channel_id' => $channel_id));

        $query = ee()->db->query($sql);

        $arr    = array('entry_id'          => 0,
                        'channel_id'        => $channel_id,
                        'collection'        => 'all',
                        'member_id'         => 0,
                        'last_rating_date'  => ($query->row('rating_date') == null) ? 0 : $query->row('rating_date'),
                        'count'             => ($query->num_rows() == 0) ? 0 : $query->row('count'));

        $sql    = ee()->db->insert_string('exp_rating_stats', $arr);

        $sql    .= " ON DUPLICATE KEY UPDATE last_rating_date = VALUES(last_rating_date), `count` = VALUES(`count`)";

        ee()->db->query($sql);
    }

    // END update_member_stats()

    // --------------------------------------------------------------------

    /**
     * Update Entry Stats
     *
     * @deprecated Please use the Utils::update_entry_stats() instead.
     *
     * @param int|string $entry_id
     *
     * @return bool
     */
    public function update_entry_stats($entry_id = '')
    {
        // ----------------------------------------
        //  Should we execute?
        // ----------------------------------------

        if ($entry_id == '') {
            return;
        }

        if (is_array($entry_id)) {
            foreach (array_unique($entry_id) as $id) {
                $this->update_entry_stats($id);
            }

            return;
        }

        // ----------------------------------------
        //  Does the entry still exist?
        // ----------------------------------------

        ee()->db->delete('rating_stats', array('entry_id' => $entry_id));

        $count  = ee('Model')
            ->get('ChannelEntry', $entry_id)
            ->first();

        if (! $count) {
            return;
        }

        // --------------------------------------------
        //  Fetch Numeric Fields and Collect Data
        // --------------------------------------------

        $stats = array();

        foreach ($this->get_rating_fields_data() as $field_row) {
            if ($field_row['field_type'] != 'number') {
                continue;
            }

            extract($field_row); // $field_id and $field_name now set.

            // --------------------------------------------
            //  Query to Get Stats
            // --------------------------------------------

            $sql = "SELECT  entry_id, channel_id, collection,
                            COUNT(`{$field_name}`) as `count`,
                            AVG(`{$field_name}`) as `avg`,
                            SUM(`{$field_name}`) as `sum`,
                            MAX(`rating_date`) as rating_date
                    FROM    exp_ratings
                    WHERE   quarantine != 'y'
                    AND     status != 'closed'
                    AND     `{$field_name}` IS NOT NULL
                    AND     entry_id = " . ee()->db->escape_str($entry_id) . "
                    GROUP   BY entry_id, collection, channel_id ";

            $result = ee()->db->query($sql);

            foreach ($result->result_array() as $row) {
                $data[$row['collection']][$field_id] = array(
                    'channel_id'        => $row['channel_id'],
                    'rating_date'       => $row['rating_date'],
                    'count_' . $field_id  => $row['count'],
                    'avg_' . $field_id    => $row['avg'],
                    'sum_' . $field_id    => $row['sum']
                );
            }
        }

        if (empty($data)) {
            return;
        }

        // --------------------------------------------
        //  Tally Data
        // --------------------------------------------

        //@todo - Perhaps review this and see if we can make it more efficient.
        //I am sure there is more going on in here than is absolutely necessary.

        $all            = array('collection' => 'all', 'entry_id' => $entry_id);
        $all_counter    = 0;
        $all_count      = 0;
        $all_avg        = 0;
        $all_sum        = 0;
        $all_nfields    = array();

        foreach ($data as $form => $fields) {
            $form_counter   = 0;
            $form_count     = 0;
            $form_avg       = 0;
            $form_sum       = 0;
            $insert         = array();

            foreach ($fields as $field => $stats) {
                if (! isset($all[$field])) {
                    $all['channel_id']          = $stats['channel_id'];
                    $all['last_rating_date']    = $stats['rating_date'];
                } elseif ($all['rating_date'] < $stats['rating_date']) {
                    $all['last_rating_date'] = $stats['rating_date'];
                }

                if (empty($insert)) {
                    $insert['channel_id']       = $stats['channel_id'];
                    $insert['last_rating_date'] = $stats['rating_date'];
                    $insert['collection']       = $form;
                    $insert['entry_id']         = $entry_id;
                }

                foreach ($stats as $k => $v) {
                    if ($k == 'channel_id' or $k == 'rating_date') {
                        continue;
                    }

                    $insert[$k] = $v;

                    if (substr($k, 0, 3) == 'sum') {
                        $all_sum += $v;
                        $form_sum += $v;
                        $all[$k] = (isset($all[$k])) ? $all[$k] + $v : $v;
                        $all_nfields[$field]['sum'] = (isset($all_nfields[$field]['sum'])) ? $all_nfields[$field]['sum'] + $v : $v;
                    } elseif (substr($k, 0, 5) == 'count') {
                        $all_counter += $v;
                        $form_counter += $v;

                        if ($form_count < $v) {
                            $form_count = $v;
                        }

                        $all[$k] = (isset($all[$k])) ? $all[$k] + $v : $v;
                        $all_nfields[$field]['count'] = (isset($all_nfields[$field]['count'])) ? $all_nfields[$field]['count'] + $v : $v;
                    } elseif (substr($k, 0, 3) == 'avg') {
                        $all_nfields[$field]['avg'] = $k;
                    }
                }
            }

            $insert['sum'] = $form_sum;
            $insert['avg'] = $form_sum / $form_counter;
            $insert['count'] = $form_count;
            $all_count += $form_count;

            if ($form == '') {
                continue;
            } // EMPTY collection="" parameter when submitted

            ee()->db->query(ee()->db->insert_string('exp_rating_stats', $insert));
        }

        foreach ($all_nfields as $field => $data) {
            $all[$data['avg']] = $data['sum'] / $data['count'];
        }

        $all['sum'] = $all_sum;
        $all['avg'] = $all_sum / $all_counter;
        $all['count'] = $all_count;
        ee()->db->query(ee()->db->insert_string('exp_rating_stats', $all));

        // --------------------------------------------
        //  Recount for rating entries
        // --------------------------------------------

        $query  = ee()->db->where('entry_id', $entry_id)->get('ratings');
        foreach ($query->result_array() as $row) {
            ee('Model')->make('rating:Review')
                ->recount_rating_reviews($row['rating_id']);
        }

        // --------------------------------------------
        //  Return
        // --------------------------------------------

        return true;
    }

    // END update_entry_stats()


    // --------------------------------------------------------------------

    /**
     * Full Data for all of Rating's Fields
     *
     * @access  public
     * @param   params  MySQL clauses, if necessary
     * @return  array
     */

    public function get_rating_fields_data($params = array())
    {
        // --------------------------------------------
        //  Perform the Actual Work
        // --------------------------------------------

        $fields = ee('Model')
            ->get('rating:Field')
            ->all();

        $out    = array();

        foreach ($fields as $field) {
            $out[$field->field_name] = $field->toArray();
        }

        // --------------------------------------------
        //  Return Data
        // --------------------------------------------

        return $out;
    }

    // END get_rating_fields_data()
}
//END Stat
