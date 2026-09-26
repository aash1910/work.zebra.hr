<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Store\Model\Product;
use Store\Model\Sale;
use Store\Model\ProductModifier;

class Store_akcije
{
    public $return_data;

    public function __construct()
    {
        ee();

        ee()->load->helper('string');

        ee()->lang->loadfile('store_akcije');
    }

    private function upsert_field($field_num, $column, $entry_id, $value)
    {
        $table = "exp_channel_data_field_$field_num";

        ee()->db->where('entry_id', $entry_id)
            ->update($table, [$column => $value]);

        if (ee()->db->affected_rows() == 0) {
            // Was it because the row didn't exist,
            // or because the value was already identical?
            $exists = ee()->db
                ->select('entry_id')
                ->where('entry_id', $entry_id)
                ->limit(1)
                ->get($table)
                ->num_rows();

            if (!$exists) {
                ee()->db->insert($table, [
                    'entry_id' => $entry_id,
                    $column => $value
                ]);
            }
        }
    }

    function akcije()
    {
        // Get field IDs
        $cijene_num = ee()->TMPL->fetch_param('cijene');
        $cijene_id = "field_id_".$cijene_num;
        $najniza_num = ee()->TMPL->fetch_param('najniza');
        $najniza_id = "field_id_".$najniza_num;
        $akcijska_num = ee()->TMPL->fetch_param('akcijska');
        $akcijska_id = "field_id_".$akcijska_num;
        $channel_id = ee()->TMPL->fetch_param('channelid');
        $channel_ids = explode("|",$channel_id);
        $channel_ids = join("','",$channel_ids);
    
        // Zebra specific
        $akcijska_cijena = "";
    
        $akcija_field = "";
        $regular_price = "";
        $akcija_field_array = array();
        
        // Loop through all entries
        $akcija_field_query = "SELECT cf.$cijene_id as $cijene_id, nf.$najniza_id as $najniza_id, af.$akcijska_id as $akcijska_id, t.entry_id, p.price, t.status
        FROM exp_channel_titles t
        LEFT JOIN exp_channel_data_field_$cijene_num cf on t.entry_id = cf.entry_id
        LEFT JOIN exp_channel_data_field_$najniza_num nf on t.entry_id = nf.entry_id
        LEFT JOIN exp_channel_data_field_$akcijska_num af on t.entry_id = af.entry_id
        LEFT JOIN exp_store_products p on t.entry_id = p.entry_id
        WHERE t.channel_id IN ('$channel_ids') ORDER BY t.entry_id ASC LIMIT 1000000";
        $akcija_field_query1 = ee()->db->query($akcija_field_query);
        if ($akcija_field_query1->num_rows() > 0) {
            foreach ($akcija_field_query1->result() as $row) {
                $regular_price = "";
                $sale_price = "";
    
                $entry_id = $row->entry_id;
                $status = $row->status;
                // Get value from custom field for each entry as string
                $akcija_field = $row->$cijene_id;
                if ($akcija_field != "") {
                    $akcija_field_array = explode("|",$akcija_field);               
                } else {
                    $akcija_field_array = array();
                }
                // Get current price
                $regular_price = $row->price;
                $sale_price = $row->$akcijska_id;
                if ($sale_price != "") {
                    $sale_price = str_replace(".","",$sale_price);
                    $sale_price = str_replace(",",".",$sale_price);
                    $sale_price = number_format((float)$sale_price, 2, ".", "");
                } else {
                    $sale_price = ""; // Explicitly keep empty for non-sale
                }
                // Get lowest price
                $lowest_price = "";
                if ($akcija_field == "") {
                    $lowest_price = $regular_price; // Fallback to regular price for new products
                } else {
                    $lowest_price = min($akcija_field_array);
                }
    
                // Push today's price
                if ($sale_price != "") {
                    array_push($akcija_field_array, $sale_price);
                } else {
                    array_push($akcija_field_array, $regular_price);
                }
    
                // Shorten array to 30, display last 30
                if (count($akcija_field_array) > 30) {
                    $akcija_field_array = array_slice($akcija_field_array, -30, 30); 
                }
                // Write array as string in field
                $akcija_field = implode("|", $akcija_field_array);
    
                // Upsert lowest price and price history into their own field tables
                $this->upsert_field($najniza_num, $najniza_id, $entry_id, $lowest_price);
                $this->upsert_field($cijene_num, $cijene_id, $entry_id, $akcija_field);
            }
        }
    }
}

/* End of file pi.store_akcije.php */
