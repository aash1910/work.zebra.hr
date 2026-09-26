<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Store_blacklist_mcp
{
    private $EE;

    public function __construct()
    {
        $this->EE = get_instance();
        $this->EE->load->library('view');
        $this->EE->cp->set_breadcrumb(ee('CP/URL')->make('addons/settings/store_blacklist'), lang('store_blacklist_module_name'));
        log_message('debug', 'Store_blacklist_mcp: constructor called');
    }

    public function index()
    {
        $this->EE->view->cp_page_title = lang('store_blacklist_module_name');
    
        $vars = [];
        $entries = $this->EE->db->get('store_blacklist')->result_array();
    
        foreach ($entries as &$entry) {
            $id = $entry['id'];
            $entry['emails'] = $this->EE->db->where('blacklist_id', $id)->get('store_blacklist_emails')->result_array();
            $entry['phones'] = $this->EE->db->where('blacklist_id', $id)->get('store_blacklist_phones')->result_array();
            $entry['addresses'] = $this->EE->db->where('blacklist_id', $id)->get('store_blacklist_addresses')->result_array();
        }
    
        $vars['entries'] = $entries;
    
        return ee('View')->make('store_blacklist:index')->render($vars);
    }

    public function logs()
    {
        $this->EE->view->cp_page_title = lang('Blacklist Log');
    
        $logFile = PATH_THIRD . 'store_blacklist/blacklist_attempts.json';
        $logs = [];
    
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if ($decoded) $logs[] = $decoded;
            }
        }
    
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
    
        $vars['logs'] = $logs;
    
        return ee('View')->make('store_blacklist:logs')->render($vars);
    }

    public function add()
    {
        $this->EE->view->cp_page_title = lang('Add Blacklist Entry');

        if ($this->EE->input->post('submit')) {
            $data = [
                'email' => $this->EE->input->post('primary_email', true) // Sanitize input
            ];
            $this->EE->db->insert('store_blacklist', $data);
            $blacklist_id = $this->EE->db->insert_id();
            log_message('debug', "Store_blacklist_mcp: Added blacklist entry ID {$blacklist_id}");

            // Additional emails
            $emails = array_filter(explode(',', $this->EE->input->post('additional_emails', true)));
            foreach ($emails as $email) {
                $this->EE->db->insert('store_blacklist_emails', [
                    'blacklist_id' => $blacklist_id,
                    'email' => trim($email)
                ]);
            }

            // Phone numbers
            $phones = array_filter(explode(',', $this->EE->input->post('phone_numbers', true)));
            foreach ($phones as $phone) {
                $this->EE->db->insert('store_blacklist_phones', [
                    'blacklist_id' => $blacklist_id,
                    'phone_number' => $this->sanitize_phone(trim($phone))
                ]);
            }

            // Addresses
            $addresses = $this->EE->input->post('addresses', true);
            if (is_array($addresses)) {
                foreach ($addresses as $addr) {
                    // Only insert if at least one field is non-empty
                    if (
                        (!empty($addr['firstname']) || !empty($addr['lastname']) || 
                         !empty($addr['address1']) || !empty($addr['address2']) || 
                         !empty($addr['city']) || !empty($addr['postcode']))
                    ) {
                        $this->EE->db->insert('store_blacklist_addresses', [
                            'blacklist_id' => $blacklist_id,
                            'firstname' => isset($addr['firstname']) ? $addr['firstname'] : '',
                            'lastname' => isset($addr['lastname']) ? $addr['lastname'] : '',
                            'address1' => isset($addr['address1']) ? $addr['address1'] : '',
                            'address2' => isset($addr['address2']) ? $addr['address2'] : '',
                            'city' => isset($addr['city']) ? $addr['city'] : '',
                            'postcode' => isset($addr['postcode']) ? $addr['postcode'] : ''
                        ]);
                    }
                }
            }

            ee()->session->set_flashdata('message_success', lang('Entry added'));
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/store_blacklist'));
        }

        return ee('View')->make('store_blacklist:add')->render();
    }

    public function edit($id)
    {
        $this->EE->view->cp_page_title = lang('Edit Blacklist Entry');

        if ($this->EE->input->post('submit')) {
            $data = [
                'email' => $this->EE->input->post('primary_email', true)
            ];
            $this->EE->db->where('id', $id)->update('store_blacklist', $data);
            log_message('debug', "Store_blacklist_mcp: Updated blacklist entry ID {$id}");

            // Clear existing related data
            $this->EE->db->where('blacklist_id', $id)->delete('store_blacklist_emails');
            $this->EE->db->where('blacklist_id', $id)->delete('store_blacklist_phones');
            $this->EE->db->where('blacklist_id', $id)->delete('store_blacklist_addresses');

            // Additional emails
            $emails = array_filter(explode(',', $this->EE->input->post('additional_emails', true)));
            foreach ($emails as $email) {
                $this->EE->db->insert('store_blacklist_emails', [
                    'blacklist_id' => $id,
                    'email' => trim($email)
                ]);
            }

            // Phone numbers
            $phones = array_filter(explode(',', $this->EE->input->post('phone_numbers', true)));
            foreach ($phones as $phone) {
                $this->EE->db->insert('store_blacklist_phones', [
                    'blacklist_id' => $id,
                    'phone_number' => $this->sanitize_phone(trim($phone))
                ]);
            }

            // Addresses
            $addresses = $this->EE->input->post('addresses', true);
            if (is_array($addresses)) {
                foreach ($addresses as $addr) {
                    // Only insert if at least one field is non-empty
                    if (
                        (!empty($addr['firstname']) || !empty($addr['lastname']) || 
                         !empty($addr['address1']) || !empty($addr['address2']) || 
                         !empty($addr['city']) || !empty($addr['postcode']))
                    ) {
                        $this->EE->db->insert('store_blacklist_addresses', [
                            'blacklist_id' => $id,
                            'firstname' => isset($addr['firstname']) ? $addr['firstname'] : '',
                            'lastname' => isset($addr['lastname']) ? $addr['lastname'] : '',
                            'address1' => isset($addr['address1']) ? $addr['address1'] : '',
                            'address2' => isset($addr['address2']) ? $addr['address2'] : '',
                            'city' => isset($addr['city']) ? $addr['city'] : '',
                            'postcode' => isset($addr['postcode']) ? $addr['postcode'] : ''
                        ]);
                    }
                }
            }

            ee()->session->set_flashdata('message_success', lang('Entry updated'));
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/store_blacklist'));
        }

        $vars = [];
        $vars['entry'] = $this->EE->db->where('id', $id)->get('store_blacklist')->row_array();
        $vars['emails'] = $this->EE->db->where('blacklist_id', $id)->get('store_blacklist_emails')->result_array();
        $vars['phones'] = $this->EE->db->where('blacklist_id', $id)->get('store_blacklist_phones')->result_array();
        $vars['addresses'] = $this->EE->db->where('blacklist_id', $id)->get('store_blacklist_addresses')->result_array();

        return ee('View')->make('store_blacklist:edit')->render($vars);
    }

    public function delete($id)
    {
        $this->EE->db->where('blacklist_id', $id)->delete('store_blacklist_emails');
        $this->EE->db->where('blacklist_id', $id)->delete('store_blacklist_phones');
        $this->EE->db->where('blacklist_id', $id)->delete('store_blacklist_addresses');
        $this->EE->db->where('id', $id)->delete('store_blacklist');
        ee()->session->set_flashdata('message_success', lang('Entry deleted'));
        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/store_blacklist'));
    }

    private function sanitize_phone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-numeric
        $phone = preg_replace('/^(\+?385|0)/', '', $phone); // Strip leading 385 or 0
        return $phone;
    }
}
?>