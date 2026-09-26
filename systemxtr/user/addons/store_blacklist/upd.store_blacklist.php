<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Store_blacklist_upd
{
    public $version = '1.0';
    private $EE;

    public function __construct()
    {
        $this->EE = get_instance();
        log_message('debug', 'Store_blacklist_upd: constructor called');
    }

    public function install()
    {
        log_message('debug', 'Store_blacklist_upd: install method called');

        $this->EE->db->insert('modules', [
            'module_name' => 'Store_blacklist',
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'n'
        ]);

        $this->EE->db->query("CREATE TABLE IF NOT EXISTS exp_store_blacklist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL
        ) CHARACTER SET utf8 COLLATE utf8_general_ci");

        $this->EE->db->query("CREATE TABLE IF NOT EXISTS exp_store_blacklist_emails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            blacklist_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            FOREIGN KEY (blacklist_id) REFERENCES exp_store_blacklist(id) ON DELETE CASCADE
        ) CHARACTER SET utf8 COLLATE utf8_general_ci");

        $this->EE->db->query("CREATE TABLE IF NOT EXISTS exp_store_blacklist_phones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            blacklist_id INT NOT NULL,
            phone_number VARCHAR(50) NOT NULL,
            FOREIGN KEY (blacklist_id) REFERENCES exp_store_blacklist(id) ON DELETE CASCADE
        ) CHARACTER SET utf8 COLLATE utf8_general_ci");

        $this->EE->db->query("CREATE TABLE IF NOT EXISTS exp_store_blacklist_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            blacklist_id INT NOT NULL,
            firstname VARCHAR(100),
            lastname VARCHAR(100),
            address1 VARCHAR(255),
            address2 VARCHAR(255),
            city VARCHAR(100),
            postcode VARCHAR(20),
            FOREIGN KEY (blacklist_id) REFERENCES exp_store_blacklist(id) ON DELETE CASCADE
        ) CHARACTER SET utf8 COLLATE utf8_general_ci");

        $this->EE->db->insert('actions', [
            'class' => 'Store_blacklist',
            'method' => 'get_blacklist'
        ]);

        log_message('debug', 'Store_blacklist_upd: install method completed');

        return TRUE;
    }

    public function uninstall()
    {
        $this->EE->db->where('module_name', 'Store_blacklist')->delete('modules');
        $this->EE->db->where('class', 'Store_blacklist')->delete('actions');
        $this->EE->db->query("DROP TABLE IF EXISTS exp_store_blacklist_addresses");
        $this->EE->db->query("DROP TABLE IF EXISTS exp_store_blacklist_phones");
        $this->EE->db->query("DROP TABLE IF EXISTS exp_store_blacklist_emails");
        $this->EE->db->query("DROP TABLE IF EXISTS exp_store_blacklist");
        return TRUE;
    }

    public function update($current = '')
    {
        return FALSE;
    }
}
?>