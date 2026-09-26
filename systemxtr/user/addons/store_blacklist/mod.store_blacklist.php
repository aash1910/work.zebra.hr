<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Store_blacklist
{
    public $return_data = '';
    private $EE;

    public function __construct()
    {
        $this->EE =& get_instance();
        log_message('debug', 'Store_blacklist: constructor called');
    }

    public function get_blacklist()
    {
        log_message('debug', 'Store_blacklist: get_blacklist method called');

        try {
            // Verify table existence
            $tables = [
                'store_blacklist',
                'store_blacklist_emails',
                'store_blacklist_phones',
                'store_blacklist_addresses'
            ];
            foreach ($tables as $table) {
                $check = $this->EE->db->table_exists($this->EE->db->dbprefix($table));
                if (!$check) {
                    log_message('error', "Store_blacklist: Table {$table} does not exist");
                    throw new Exception("Table {$table} does not exist");
                }
            }

$query = $this->EE->db
    ->select('b.id, b.email', FALSE)
    ->select('GROUP_CONCAT(DISTINCT e.email) AS emails', FALSE)
    ->select('GROUP_CONCAT(DISTINCT p.phone_number) AS phone_numbers', FALSE)
    ->select('GROUP_CONCAT(DISTINCT CONCAT_WS("||", 
        COALESCE(a.firstname, ""), 
        COALESCE(a.lastname, ""), 
        COALESCE(a.address1, ""), 
        COALESCE(a.address2, ""), 
        COALESCE(a.city, ""), 
        COALESCE(a.postcode, ""))) AS addresses', FALSE)
    ->from($this->EE->db->dbprefix('store_blacklist') . ' b')
    ->join($this->EE->db->dbprefix('store_blacklist_emails') . ' e', 'b.id = e.blacklist_id', 'left')
    ->join($this->EE->db->dbprefix('store_blacklist_phones') . ' p', 'b.id = p.blacklist_id', 'left')
    ->join($this->EE->db->dbprefix('store_blacklist_addresses') . ' a', 'b.id = a.blacklist_id', 'left')
    ->group_by('b.id')
    ->get();



            if ($query === false) {
                log_message('error', 'Store_blacklist: Database query failed: ' . $this->EE->db->_error_message());
                throw new Exception('Database query failed: ' . $this->EE->db->_error_message());
            }

            $blacklist = [];
            foreach ($query->result_array() as $row) {
                log_message('debug', 'Store_blacklist: Processing row ID ' . $row['id']);
                $entry = [
                    'email' => $row['email'],
                    'emails' => $row['emails'] ? array_filter(explode(',', $row['emails'])) : [],
                    'phone_numbers' => $row['phone_numbers'] ? array_filter(explode(',', $row['phone_numbers'])) : [],
                    'addresses' => []
                ];

                if ($row['addresses']) {
                    foreach (array_filter(explode(',', $row['addresses'])) as $addr) {
                        $parts = array_map('trim', explode('||', $addr));
                        if (count($parts) === 6) {
                            $entry['addresses'][] = [
                                'firstname' => $parts[0],
                                'lastname' => $parts[1],
                                'address1' => $parts[2],
                                'address2' => $parts[3],
                                'city' => $parts[4],
                                'postcode' => $parts[5]
                            ];
                        } else {
                            log_message('debug', 'Store_blacklist: Invalid address format: ' . print_r($parts, true));
                        }
                    }
                }

                $blacklist[] = $entry;
            }

            if (!defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                //PHP < 7.2 Define it as 0 so it does nothing
                define('JSON_INVALID_UTF8_SUBSTITUTE', 0);
            }
            $json = json_encode($blacklist, JSON_INVALID_UTF8_SUBSTITUTE);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Store_blacklist: JSON encoding failed: ' . json_last_error_msg());
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }

            log_message('debug', 'Store_blacklist: get_blacklist output: ' . substr($json, 0, 100) . '...');
            return $json;
        } catch (Exception $e) {
            log_message('error', 'Store_blacklist: Exception in get_blacklist: ' . $e->getMessage());
            return json_encode(['error' => $e->getMessage()]);
        }
    }
}
?>