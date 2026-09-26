<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Customresources module for ExpressionEngine
 * Copyright (c) 2012 krunovukovic@gmail.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once PATH_THIRD . 'store/autoload.php';

use Store\Model\Order;
use Store\Model\Email;

$plugin_info = array(
    'pi_name'        => 'Custom Resources',
    'pi_version'     => '2.0.0',
    'pi_author'      => 'Exp:krunovukovic',
    'pi_author_url'  => 'http://web-form.hr/',
    'pi_description' => 'Necessary Custom Resources!',
    'has_cp_backend' => 'n'
);

class Customresources
{
    public $return_data;
    public $root_dir;

    public function __construct()
    {
        $this->root_dir = FCPATH;
        
        ee()->load->helper('string');
        ee()->load->helper('url');
        ee()->lang->loadfile('customresources');
    }

    /**
     * Get distinct field options
     * Used for filtering/faceting
     */
    public function get_distinct_options()
    {

        $field_id = ee()->TMPL->fetch_param('field_id');

        // --------------------------------------
        // Start composing query
        // --------------------------------------
        $sql_now   = ee()->localize->now;
        $table = 'channel_data_field_'.$field_id;
        $sql_field = 'field_id_'.$field_id;
        ee()->db->select("DISTINCT(d.{$sql_field}) AS val")
             ->from($table.' d')
             ->join('channel_titles t', 'd.entry_id = t.entry_id')
             ->where($sql_field.' !=', '')
             ->where('t.status !=', 'closed')
             ->order_by($sql_field, "asc");
             ;
        // --------------------------------------
        // Filter by channel
        // --------------------------------------
        if ($channels = ee()->TMPL->fetch_param('channel'))
        {
            // Determine which channels to filter by
            list($channels, $in) = $this->_explode_param($channels);
            // Join channels table
            ee()->db->join('channels c', 't.channel_id = c.channel_id');
            ee()->db->{($in ? 'where_in' : 'where_not_in')}('c.channel_name', $channels);
        }

        // --------------------------------------
        // Filter by category
        // --------------------------------------
        if ($categories_param = ee()->TMPL->fetch_param('category'))
        {
            // Determine which categories to filter by
            list($categories, $in) = $this->_explode_param($categories_param);
            if (strpos($categories_param, '&'))
            {
                // Execute query the old-fashioned way, so we don't interfere with active record
                // Get the entry ids that have all given categories assigned
                $query = ee()->db->query(
                    "SELECT entry_id, COUNT(*) AS num
                    FROM exp_category_posts
                    WHERE cat_id IN (".implode(',', $categories).")
                    GROUP BY entry_id HAVING num = ". count($categories));
                // If no entries are found, make sure we limit the query accordingly
                if ( ! ($entry_ids = $this->_flatten($query->result_array(), 'entry_id')))
                {
                    $entry_ids = array(0);
                }
                ee()->db->where_in('entry_id', $entry_ids);
            }
            else
            {
                // Join category table
                ee()->db->join('category_posts cp', 'cp.entry_id = t.entry_id');
                ee()->db->{($in ? 'where_in' : 'where_not_in')}('cp.cat_id', $categories);
            }
        }

        // --------------------------------------
        // Filter by entry_id
        // --------------------------------------
        if ($entry_id = ee()->TMPL->fetch_param('entry_id'))
        {
            $split = strpos($entry_id, ",") ? "," : '|';
            $entry_id   = explode($split, $entry_id);
            ee()->db->where_in('t.entry_id', $entry_id);
        }

        // --------------------------------------
        // Get results
        // --------------------------------------
        $query = ee()->db->get();

        $vals = array();
        foreach ($query->result() AS $row)
        {
            $split = strpos($row->val, "\n") ? "\n" : '|';
            $val   = explode($split, $row->val);
            $vals  = array_merge($vals, $val);
        }
        $vals = array_unique($vals);

        $data = array();
        foreach ($vals AS $val)
        {
            $data[]['option'] = $val;
        }
        //echo "<pre>";print_r($data);exit;
        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $data);
    }
    // --------------------------------------------------------------------
    /**
     * Converts EE parameter to workable php vars
     *
     * @access      private
     * @param       string    String like 'not 1|2|3' or '40|15|34|234'
     * @return      array     [0] = array of ids, [1] = boolean whether to include or exclude: TRUE means include, FALSE means exclude
     */
    private function _explode_param($str)
    {
        // --------------------------------------
        // Initiate $in var to TRUE
        // --------------------------------------
        $in = TRUE;
        // --------------------------------------
        // Check if parameter is "not bla|bla"
        // --------------------------------------
        if (strtolower(substr($str, 0, 4)) == 'not ')
        {
            // Change $in var accordingly
            $in = FALSE;
            // Strip 'not ' from string
            $str = substr($str, 4);
        }
        // --------------------------------------
        // Return two values in an array
        // --------------------------------------
        return array(preg_split('/(&|\|)/', $str), $in);
    }
    // --------------------------------------------------------------------
    /**
     * Flatten a result set
     *
     * Given a DB result set, this will return an (associative) array
     * based on the keys given
     *
     * @param      array
     * @param      string    key of array to use as value
     * @param      string    key of array to use as key (optional)
     * @return     array
     */
    private function _flatten($resultset, $val, $key = FALSE)
    {
        $array = array();
        foreach ($resultset AS $row)
        {
            if ($key !== FALSE)
            {
                $array[$row[$key]] = $row[$val];
            }
            else
            {
                $array[] = $row[$val];
            }
        }
        return $array;
    }

    /**
     * Send email to Store users based on order status/timing
     */
    public function send_email_to_user(){

        $from = ee()->TMPL->fetch_param('past_hour_from');
        $to = ee()->TMPL->fetch_param('past_hour_to');
        $status = ee()->TMPL->fetch_param('status');
        $email_id = ee()->TMPL->fetch_param('email_id');
        if($email_id == "") return 0;
        if($status == "") return 0;
        
        $where = "";
        if( $status == "incomplete" ){
            $where = "order_completed_date IS NULL";
        }
        else{
            $where = "order_status_name = '$status'";
        }

        if( isset($from) ){
            $from = strtotime("-{$from} hours");
            $where .= " AND order_date <= '$from'";
        }
        if( isset($to) ){
            $to = strtotime("-{$to} hours");
            $where .= " AND order_date >= '$to'";
        }

        $where .= " AND order_subtotal > '0'";

        $query = "SELECT * FROM exp_store_orders WHERE {$where} AND order_email != '' ORDER BY order_date ASC";
        $orders = ee()->db->query($query)->result();
        //echo "<pre>";print_r($orders);exit;

        foreach ($orders as $v) {
            $email = Email::whereIn('id', array($email_id))->where('enabled', 1)->get();
            $order = Order::whereIn('id', array($v->id))->get();
            ee()->store->email->send($email[0], $order[0]);
        }

        return 1;
    }

    /**
     * Delete orders based on status and time parameters
     */
    public function delete_orders(){

        $from = ee()->TMPL->fetch_param('past_hour_from');
        $to = ee()->TMPL->fetch_param('past_hour_to');
        $status = ee()->TMPL->fetch_param('status');
        if($status == "") return 0;
        
        $where = "";
        if( $status == "incomplete" ){
            $where = "order_completed_date IS NULL";
        }
        else{
            $where = "order_status_name = '$status'";
        }

        if( isset($from) ){
            $from = strtotime("-{$from} hours");
            $where .= " AND order_date <= '$from'";
        }
        if( isset($to) ){
            $to = strtotime("-{$to} hours");
            $where .= " AND order_date >= '$to'";
        }

        $query = "SELECT * FROM exp_store_orders WHERE {$where} ORDER BY order_date ASC";
        $orders = ee()->db->query($query)->result();
        //echo "<pre>";print_r($orders);exit;

        $order_list = array();
        foreach ($orders as $v) {
            $order_list[] = $v->id;
        }
        //echo "<pre>";print_r($order_list);exit;

        ee()->store->orders->delete_orders($order_list);

        return 1;
    }

    /**
     * Set channel entries to closed status and reset stock
     */
    public function status_closed_stock()
    {
        $channelid = ee()->TMPL->fetch_param('channelid');
        ee()->db->query("UPDATE exp_channel_titles a JOIN exp_store_stock b ON a.entry_id = b.entry_id SET a.status = 'closed', b.stock_level = '', b.track_stock = '1' WHERE a.channel_id = '$channelid'");
        $affected_rows = ee()->db->affected_rows();
        
        echo "Total Affected Rows : ".$affected_rows;exit;
        return "";
    }

    /**
     * Set all entries in channel to closed status
     */
    public function status_closed()
    {
        $channelid = ee()->TMPL->fetch_param('channelid');
        ee()->db->query("UPDATE exp_channel_titles SET status = 'closed' WHERE channel_id = '$channelid'");
        $affected_rows = ee()->db->affected_rows();
        
        echo "Total Affected Rows : ".$affected_rows;exit;
        return "";
    }

    /**
     * Set all products in channel to in-stock
     */
    public function status_instock()
    {
        $channelid = ee()->TMPL->fetch_param('channelid');
        ee()->db->query("UPDATE exp_channel_titles a JOIN exp_store_stock b ON a.entry_id = b.entry_id SET b.stock_level = '100', b.track_stock = '1' WHERE a.channel_id = '$channelid'");
        $affected_rows = ee()->db->affected_rows();
        
        echo "Total Affected Rows : ".$affected_rows;exit;
        return "";
    }

    /**
     * Get most popular product IDs
     */
    public function najprodavanije_ids()
    {

        $result = ee()->db->select('o.order_id as order_id,o.order_completed_date as order_completed_date,o.order_email as order_email,i.item_qty as item_qty, i.entry_id as entry_id, SUM(item_qty) AS sum')
            ->from('store_order_items i')
            ->join('channel_titles t', 't.entry_id = i.entry_id', 'left')
            ->join('store_orders o', 'o.id = i.order_id', 'left')
            ->where('order_completed_date !=', '')
            ->group_by('i.entry_id', 'ASC')
            ->order_by('sum', 'DESC')
            ->limit('30')
            ->get()->result_array();

        //echo "<pre>";print_r($result);exit;

        $count = 0;
        $entry_ids = "";
        foreach ($result as $row) {
            $count = $count + 1;
            $row = array_map('trim', $row);
            if ($count == 1){
            $entry_ids = $row['entry_id'];
            } else {
            $entry_ids = $entry_ids."|".$row['entry_id'];
            }
        }
        return $entry_ids;
    }

    /**
     * Get category order counts
     */
    public function categoryorders(){

        $from = ee()->TMPL->fetch_param('from');
        $to = ee()->TMPL->fetch_param('to');
        $category = ee()->TMPL->fetch_param('category');


        $where = "order_completed_date IS NOT NULL";

        if( isset($from) ){
            $where .= " AND order_completed_date >= '$from'";
        }
        if( isset($to) ){
            $where .= " AND order_completed_date <= '$to'";
        }



        $query = "SELECT item_total, item_qty, category_ids
            FROM exp_store_order_items i
            LEFT JOIN exp_store_orders o on i.order_id = o.id
            WHERE {$where}
            ORDER BY i.id DESC";


        $orders = ee()->db->query($query)->result();

        $item_qtys = "0";
        $item_totals = "0";
        foreach (ee()->db->query($query)->result() AS $row)
        {
            $category_ids = $row->category_ids;
            $category_ids = explode("|",$category_ids);
            if (in_array($category, $category_ids)) {
                $item_qty = $row->item_qty;
                $item_total = $row->item_total;
                $item_qtys += $item_qty;
                $item_totals += $item_total;
            }
        }

        //echo($from."<br>");
        //echo($to."<br>");
        //echo($where."<br>");
        //echo("qty:".$item_qtys."<br>");
        //echo("total:".$item_totals."<br>");
        //print_r($category_ids);
        //echo "<pre>";print_r($orders);
        //$rezultat = "Komada:<b>".$item_qtys."</b>, Iznos:<b>".$item_totals."</b>";
        $rezultat2 = "<td>".$item_qtys."</td><td>".$item_totals."</td>";
        return $rezultat2;
    }

    /**
     * Delete entries from channel
     */
    public function delete_entries()
    {
        //ostaviti 10, jer ne postoji, ako se slučano posjeti url da ne bude nepredviđena situacija
        $ids = array('371742','371741','371740','371739','371738','371737');
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_entries');
        ee()->api_channel_entries->delete_entry($ids);
    }

    /**
     * List entries older than specified age
     */
    public function listEntriesOlderThanAge() {
        // Calculate the timestamp for 1 year ago
        //$ageAgo = strtotime('-1 year');
        $ageAgo = strtotime('-6 months');

    
        // Get channels and convert to an array
        $channels = ee()->TMPL->fetch_param('channels', '');
        $channelIds = explode('|', $channels);
    
        // Query to fetch entry IDs in a single call
        $query = ee()->db->select('ct.entry_id')
                         ->from('channel_titles ct')
                         ->join('channel_data_field_135 cd', 'cd.entry_id = ct.entry_id')
                         ->where_in('ct.channel_id', $channelIds)
                         ->where('cd.field_id_135 <', $ageAgo)
                         ->get();
    
        // Format results
        $entryIds = array_map(function($row) {
            return '"' . $row['entry_id'] . '"';
        }, $query->result_array());

        // Count results
        $count = count($entryIds);
    
        // Construct output
        $output = "Channels: " . implode('|', $channelIds) . "\n";
        $output .= "Entries older than 6 months: $count\n";
        $output .= "Entries: " . implode(', ', $entryIds);
    
        return $output;
    }

    /**
     * Archive old entries to exp_deleted_entries
     */
    public function archiveOldEntries() {

        // Timestamp for 6 months ago
        $ageAgo = strtotime('-6 months');
    
        // Get channels
        $channels = ee()->TMPL->fetch_param('channels', '');
        $channelIds = explode('|', $channels);
    
        // Fetch entries older than 6 months with necessary info
        $query = ee()->db->select('ct.entry_id, ct.channel_id, cd.field_id_135 as last_expired, ct.url_title, ct.entry_date')
                         ->from('channel_titles ct')
                         ->join('channel_titles ct', 'cd.entry_id = ct.entry_id')
                         ->join('channel_data_field_135 cd', 'cd.entry_id = ct.entry_id')
                         ->where_in('cd.channel_id', $channelIds)
                         ->where('cd.field_id_135 <', $ageAgo)
                         ->get();
    
        $entries = $query->result_array();
        $now = date('Y-m-d H:i:s'); // current readable timestamp
        $count = 0;
    
        // Insert each entry using INSERT IGNORE to skip duplicates
        foreach ($entries as $entry) {
            ee()->db->query(
                ee()->db->insert_string('exp_deleted_entries', [
                    'entry_id'    => $entry['entry_id'],
                    'url_title'   => $entry['url_title'],
                    'channel_id'  => $entry['channel_id'],
                    'entry_date'  => $entry['entry_date'],
                    'last_expired'=> $entry['last_expired'],
                    'current_time'=> $now
                ]) . " ON DUPLICATE KEY UPDATE entry_id=entry_id"
            );
            $count++;
        }
    
        return "$count entries processed into exp_deleted_entries (duplicates skipped).";
    }

    /**
     * Clean exp_deleted_entries of entries because sometimes new entries exist with exact same status (its either new or old entry again)
     */
    public function cleanArchivedEntries() {
        // treba paziti kada se ovo pokreće, mora se prvo izbrisati entries, inače će ovo samo vratiti
        // Get channels
        $channels = ee()->TMPL->fetch_param('channels', '');
        $channelIds = explode('|', $channels);
    
        // Get all url_titles currently live (open status, specific channels only)
        $live = ee()->db->select('url_title')
                        ->where_in('channel_id', $channelIds)
                        ->where('status', 'open')
                        ->get('channel_titles')
                        ->result_array();
        $live_titles = array_column($live, 'url_title');
    
        // Get all deleted entries
        $deleted = ee()->db->select('entry_id, url_title')->get('deleted_entries')->result_array();
        $deleted_titles = array_column($deleted, 'url_title', 'entry_id'); // [entry_id => url_title]
    
        // Find entry_ids where url_title exists in live
        $live_set = array_flip($live_titles); // flip for O(1) isset() lookup
        $to_delete = [];
    
        foreach ($deleted_titles as $entry_id => $url_title) {
            if (isset($live_set[$url_title])) {
                $to_delete[] = $entry_id;
            }
        }
    
        if (empty($to_delete)) {
            return "0 entries removed from exp_deleted_entries.";
        }
    
        // Delete in one query
        ee()->db->where_in('entry_id', $to_delete)->delete('deleted_entries');
    
        $count = count($to_delete);
        return "$count entries removed from exp_deleted_entries (restored as live entries).";
    }

    /**
     * Automated entry deletion based on age (this is perma disabled, its too scary)
     */
    public function delete_entries_automated()
    {
        //// Enable DB debug and profiler for EE
        //// ee()->db->db_debug = TRUE;
        //// ee()->output->enable_profiler(TRUE);

        //ee()->load->database();
        //ee()->load->library('api');
        //ee()->legacy_api->instantiate('channel_entries');

        //// Get URL param
        //$total = ee()->input->get('total') ? (int) ee()->input->get('total') : 10;

        //// Start timer
        //$start_time = microtime(true);

        //// Fetch a batch of undeleted entries
        //$query = ee()->db->select('entry_id')
        //    ->from('exp_deleted_entries')
        //    ->where('deleted', 0)
        //    ->limit($total)
        //    ->get();

        //if ($query->num_rows() == 0) {
        //    return "<p>All entries processed.</p>";
        //}

        //$ids = array_column($query->result_array(), 'entry_id');

        //// Delete entries
        //$deleted = ee()->api_channel_entries->delete_entry($ids);

        //// Mark as deleted if successful
        //if ($deleted) {
        //    ee()->db->where_in('entry_id', $ids);
        //    ee()->db->update('exp_deleted_entries', array('deleted' => 1));
        //}

        //// Stop timer
        //$end_time = microtime(true);
        //$duration = round($end_time - $start_time, 2);

        //// Output
        //$output  = "<p>Deleted <strong>" . count($ids) . "</strong> entries in {$duration} seconds.</p>";
        //$output .= "<p><a href='?total={$total}'>Next batch manually</a></p>";

        //return $output;
    }

    /**
     * Add one year to a date
     */
    public function dateplusyear()
    {
        $futureDate=date('Y-m-d', strtotime('+1 year'));
        return $futureDate;
    }

    /**
     * Get total sold quantity for product
     */
    public function broj_prodanih()
    {

        ee()->db->query("UPDATE exp_channel_data_field_125 SET field_id_125 = '0'");
        $result = ee()->db->select('o.order_id as order_id,o.order_completed_date as order_completed_date,o.order_email as order_email,i.item_qty as item_qty, i.entry_id as entry_id, SUM(item_qty) AS sum')
            ->from('store_order_items i')
            ->join('channel_titles t', 't.entry_id = i.entry_id', 'left')
            ->join('store_orders o', 'o.id = i.order_id', 'left')
            ->where('order_completed_date !=', '')
            ->having ('SUM(item_qty) > ', '0')
            ->group_by('i.entry_id', 'ASC')
            ->order_by('sum', 'DESC')
            ->get()->result_array();

        foreach ($result as $row) {
            $entry_id = $row['entry_id'];
            $sum = $row['sum'];
            //echo($count." - ".$entry_id." - ".$sum."<br>");
            ee()->db->query("UPDATE exp_channel_data_field_125 SET field_id_125 = '$sum' WHERE entry_id = '$entry_id'");
        }
    }

    /**
     * Import Channel Images from external database
     * - Uses Channel Images API directly (like DataGrab)
     */
    public function import_channel_images_from_external_db()
    {
        $total = ee()->input->get('total');
        $from = ee()->input->get('from');

        $field_id = 8; // Channel Images field ID
        $sku_field_id = 18; // SKU field ID
        $channel_id = ee()->TMPL->fetch_param('channel_id');

        // External database connection
        $db_host = 'zebra.hr';
        $db_user = 'zebra_import';
        $db_password = 'Gnw-QztsDR-2';
        $db_name = 'zebra_import';

        $link = mysqli_connect($db_host, $db_user, $db_password, $db_name);
        mysqli_query($link, 'SET NAMES utf8');
        mysqli_select_db($link, $db_name);
        
        $query = mysqli_query($link, "SELECT * FROM images_updates1 LIMIT $total OFFSET $from");

        if (mysqli_num_rows($query) == 0) {
            echo '<div id="import-status" data-status="done">Import Complete!</div>';
            return;
        }

        $import_rows = array();
        $import_rows_skus = array();
        
        while ($row = mysqli_fetch_array($query)) {
            $import_rows[] = $row;
            $import_rows_skus[] = $row["SKU"];
        }

        // Get EE entries matching SKUs
        $sku_data_table = 'channel_data_field_' . $sku_field_id;
        $sku_field_name = 'field_id_' . $sku_field_id;
        
        $result = ee()->db->select('entry_id,' . $sku_field_name)
            ->from($sku_data_table)
            ->where_in($sku_field_name, $import_rows_skus)
            ->get()->result_array();

        $skus_entry_id = array();
        foreach ($result as $row) {
            $skus_entry_id[$row[$sku_field_name]] = $row["entry_id"];
        }

        // Load Channel Images API
        if (!class_exists('Channel_Images_API')) {
            require_once PATH_THIRD . 'channel_images/api.channel_images.php';
        }
        $ciApi = new Channel_Images_API();

        // Process each product
        foreach ($import_rows as $row) {
            $entry_id = @$skus_entry_id[$row["SKU"]];
            
            if (empty($entry_id)) {
                continue;
            }

            // Collect all image URLs for this product
            $images = array();
            for ($i = 1; $i <= 29; $i++) {
                if (!empty($row["image{$i}"])) {
                    $images[] = $row["image{$i}"];
                }
            }

            if (empty($images)) {
                continue;
            }

            // Get entry data for channel_id and site_id
            $entryData = ee()->db->select('channel_id, site_id')
                ->from('exp_channel_titles')
                ->where('entry_id', $entry_id)
                ->get()->row_array();

            if (!$entryData) {
                continue;
            }

            $currentImages = array();

            // Before processing images for this entry, nuke the folder
            $upload_dir = FCPATH . 'images/uploads/' . $entry_id . '/';
            
            if (is_dir($upload_dir)) {
                // Delete all files in the directory
                array_map('unlink', glob($upload_dir . '*'));
                // Optionally also remove subdirs (thumbs, resizes, etc.)
                foreach (glob($upload_dir . '*', GLOB_ONLYDIR) as $subdir) {
                    array_map('unlink', glob($subdir . '/*'));
                    rmdir($subdir);
                }
            }

            // Process each image
            foreach ($images as $key => $imageUrl) {
                $imageUrl = trim($imageUrl);
                
                // Process filename based on source
//ovo je mijenjano, ime niže u fajlu (i u datagrab_channel_images.php), mijenjano 2025-06-24 i 2025-06-25
if (strpos($imageUrl, 'vidaxlpim.blob.core.windows.net/pimsalsify') !== false) {
    // NEW LOGIC for Azure Blob URLs — DO NOT ALTER URL!
    $parsed = parse_url($imageUrl);
    $decoded_path = urldecode($parsed['path']);
    $original_filename = basename($decoded_path);
    $original_filename = strtolower(ee()->security->sanitize_filename($original_filename));
    $filename = str_replace([' ', '+'], '_', $original_filename);
} else {
    // EXISTING LOGIC for all other image sources
    $original_filename = str_replace('/', '_', substr(parse_url($imageUrl, PHP_URL_PATH), 1));
    $original_filename = strtolower(ee()->security->sanitize_filename($original_filename));
    $filename = str_replace(array(' ', '+', '%'), array('_', '', ''), $original_filename);
}

                // Format through CI's formatter
                $filename = $ciApi->format_filename($filename);
                
                // Check if image already exists for this entry/field
                // Note: We can't add to $currentImages yet because we don't know the final filename
                // (it might get converted to .webp or stay as .jpg depending on actions)
                $row_image = ee()->db->get_where('exp_channel_images', array(
                    'filename' => $filename,
                    'entry_id' => $entry_id,
                    'field_id' => $field_id,
                    'is_draft' => 0
                ))->row_array();

                if (!empty($row_image)) {
                    // Image already exists, skip
                    continue;
                }

                // Download image
                $arrContextOptions = [
                    "ssl" => [
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ],
                ];

                $imageData = @file_get_contents($imageUrl, false, stream_context_create($arrContextOptions));
                
                if ($imageData === false) {
                    continue;
                }

$checkfilesize = strlen($imageData);
if ($checkfilesize < 1000) {
	continue;
}

                // Prepare data for Channel Images API
                $extension = substr(strrchr($filename, '.'), 1);
                $tempKey = time() . rand(1, 999);

                $apiData = [
                    'field_id' => $field_id,
                    'entry_id' => $entry_id,
                    'channel_id' => $entryData['channel_id'],
                    'site_id' => $entryData['site_id'],
                    'member_id' => ee()->session->userdata['member_id'],
                    'temp_key' => $tempKey,
                    'image_order' => $key, // 0-indexed
                    'filename' => $filename,
                    'extension' => $extension,
                    'image_data' => $imageData,
                    'title' => ucfirst(str_replace('_', ' ', str_replace('.' . $extension, '', $filename))),
                    'description' => '',
                    'category' => '',
                    'cifield_1' => '',
                    'cifield_2' => '',
                    'cifield_3' => '',
                    'cifield_4' => '',
                    'cifield_5' => '',
                ];

                // Use Channel Images API to add the image
                $imageId = $ciApi->add_image($apiData);
                
                if ($imageId === false) {
                    // Log error if needed
                    // error_log("Failed to add image {$filename} for entry {$entry_id}");
                } else {
                    // Get the ACTUAL filename that was saved (might be .webp if converted)
                    $saved_image = ee()->db->select('filename')
                        ->where('image_id', $imageId)
                        ->get('exp_channel_images')
                        ->row();
                    
                    if ($saved_image && !empty($saved_image->filename)) {
                        $currentImages[] = $saved_image->filename;
                    }
                }
            }

            // Clean up old images not in current import
            if (!empty($currentImages)) {
                ee()->db->where_not_in('filename', $currentImages);
            }
            ee()->db->where('entry_id', $entry_id);
            ee()->db->where('field_id', $field_id);
            ee()->db->where('is_draft', 0);
            ee()->db->delete('exp_channel_images');

            // Clean up temp directories
            $ciApi->clean_temp_dirs($field_id);
        }
    }

}
