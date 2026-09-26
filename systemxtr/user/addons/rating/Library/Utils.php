<?php

namespace EEHarbor\Rating\Library;

class Utils
{
    /**
     * Characters Decoding
     * Converted entities back into characters
     *
     * @param string $str
     *
     * @return string
     */
    public function chars_decode($str = '')
    {
        if ($str == '') {
            return '';
        }

        if (function_exists('htmlspecialchars_decode')) {
            $str = htmlspecialchars_decode($str);
        }

        if (function_exists('html_entity_decode')) {
            $str = html_entity_decode($str);
        }

        $str = str_replace(array('&amp;', '&#47;', '&#39;', '\''), array('&', '/', '', ''), $str);

        $str = stripslashes($str);

        return $str;
    }

    /**
     * Update Member Stats
     *
     * @param int|string $memberId
     *
     * @return bool
     */
    public static function update_member_stats($memberId = '')
    {
        if (empty($memberId)) {
            return false;
        }

        if (is_array($memberId)) {
            foreach (array_unique($memberId) as $id) {
                self::update_member_stats($id);
            }

            return true;
        }

        ee()->db->delete('rating_stats', array('member_id' => $memberId));

        $sql = "SELECT COUNT(*) as `count`, MAX(`rating_date`) as rating_date
                FROM exp_ratings
                WHERE quarantine != 'y' AND status != 'closed'
                AND rating_author_id = '" . ee()->db->escape_str($memberId) . "'";

        $query = ee()->db->query($sql);

        $arr = array(
            'entry_id'         => 0,
            'channel_id'       => 0,
            'collection'       => 'all',
            'member_id'        => $memberId,
            'last_rating_date' => ($query->row('rating_date') == null) ? 0 : $query->row('rating_date'),
            'count'            => ($query->num_rows() == 0) ? 0 : $query->row('count'),
        );

        $sql = ee()->db->insert_string('exp_rating_stats', $arr);

        $sql .= " ON DUPLICATE KEY UPDATE last_rating_date = VALUES(last_rating_date), `count` = VALUES(`count`)";

        ee()->db->query($sql);
    }

    /**
     * Update Channel Stats
     *
     * @param int|string $channelId
     *
     * @return bool
     */
    public static function update_channel_stats($channelId = '')
    {
        if (empty($channelId)) {
            return false;
        }

        if (is_array($channelId)) {
            foreach (array_unique($channelId) as $id) {
                self::update_channel_stats($id);
            }

            return true;
        }

        $sql = "SELECT COUNT(*) as `count`, MAX(`rating_date`) as rating_date
                FROM exp_ratings
                WHERE quarantine != 'y' AND status != 'closed'
                AND channel_id = '" . ee()->db->escape_str($channelId) . "'";

        ee()->db->delete('rating_stats', array('entry_id' => 0, 'channel_id' => $channelId));

        $query = ee()->db->query($sql);

        $arr = array(
            'entry_id'         => 0,
            'channel_id'       => $channelId,
            'collection'       => 'all',
            'member_id'        => 0,
            'last_rating_date' => ($query->row('rating_date') == null) ? 0 : $query->row('rating_date'),
            'count'            => ($query->num_rows() == 0) ? 0 : $query->row('count'),
        );

        $sql = ee()->db->insert_string('exp_rating_stats', $arr);

        $sql .= " ON DUPLICATE KEY UPDATE last_rating_date = VALUES(last_rating_date), `count` = VALUES(`count`)";

        ee()->db->query($sql);
    }

    /**
     * Update Entry Stats
     *
     * @param int|string $entry_id
     *
     * @return bool
     */
    public static function update_entry_stats($entry_id = '')
    {
        if ($entry_id == '') {
            return false;
        }

        if (is_array($entry_id)) {
            foreach (array_unique($entry_id) as $id) {
                self::update_entry_stats($id);
            }

            return true;
        }

        // ----------------------------------------
        //  Does the entry still exist?
        // ----------------------------------------
        ee()->db->delete('rating_stats', array('entry_id' => $entry_id));

        $count = ee('Model')
            ->get('ChannelEntry', $entry_id)
            ->first();

        if (!$count) {
            return false;
        }

        // --------------------------------------------
        //  Fetch Numeric Fields and Collect Data
        // --------------------------------------------

        foreach (self::get_rating_fields_data() as $field_row) {
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
                    GROUP   BY entry_id, collection ";

            $result = ee()->db->query($sql);

            foreach ($result->result_array() as $row) {
                $data[$row['collection']][$field_id] = array(
                    'channel_id'         => $row['channel_id'],
                    'rating_date'        => $row['rating_date'],
                    'count_' . $field_id => $row['count'],
                    'avg_' . $field_id   => $row['avg'],
                    'sum_' . $field_id   => $row['sum'],
                );
            }
        }

        if (empty($data)) {
            return false;
        }

        // --------------------------------------------
        //  Tally Data
        // --------------------------------------------

        //@todo - Perhaps review this and see if we can make it more efficient.
        //I am sure there is more going on in here than is absolutely necessary.

        $all         = array('collection' => 'all', 'entry_id' => $entry_id);
        $all_counter = 0;
        $all_count   = 0;
        $all_avg     = 0;
        $all_sum     = 0;
        $all_nfields = array();

        foreach ($data as $form => $fields) {
            $form_counter = 0;
            $form_count   = 0;
            $form_avg     = 0;
            $form_sum     = 0;
            $insert       = array();

            foreach ($fields as $field => $stats) {
                if (!isset($all[$field])) {
                    $all['channel_id']       = $stats['channel_id'];
                    $all['last_rating_date'] = $stats['rating_date'];
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
                        $all[$k]                    = (isset($all[$k])) ? $all[$k] + $v : $v;
                        $all_nfields[$field]['sum'] = (isset($all_nfields[$field]['sum'])) ? $all_nfields[$field]['sum'] + $v : $v;
                    } elseif (substr($k, 0, 5) == 'count') {
                        $all_counter += $v;
                        $form_counter += $v;

                        if ($form_count < $v) {
                            $form_count = $v;
                        }

                        $all[$k]                      = (isset($all[$k])) ? $all[$k] + $v : $v;
                        $all_nfields[$field]['count'] = (isset($all_nfields[$field]['count'])) ? $all_nfields[$field]['count'] + $v : $v;
                    } elseif (substr($k, 0, 3) == 'avg') {
                        $all_nfields[$field]['avg'] = $k;
                    }
                }
            }

            $insert['sum']   = $form_sum;
            $insert['avg']   = $form_sum / $form_counter;
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

        $all['sum']   = $all_sum;
        $all['avg']   = $all_sum / $all_counter;
        $all['count'] = $all_count;
        ee()->db->query(ee()->db->insert_string('exp_rating_stats', $all));

        // --------------------------------------------
        //  Recount for rating entries
        // --------------------------------------------
        $query = ee()->db->where('entry_id', $entry_id)->get('ratings');
        foreach ($query->result_array() as $row) {
            ee('Model')->make('rating:Review')
                       ->recount_rating_reviews($row['rating_id']);
        }

        return true;
    }

    /**
     * Full Data for all of Rating's Fields
     *
     * @return array
     */
    private static function get_rating_fields_data()
    {
        $fields = ee('Model')
            ->get('rating:Field')
            ->all();

        $out = array();

        foreach ($fields as $field) {
            $out[$field->field_name] = $field->toArray();
        }

        return $out;
    }
}
