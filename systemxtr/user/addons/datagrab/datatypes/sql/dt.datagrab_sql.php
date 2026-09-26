<?php

use BoldMinded\DataGrab\DataTypes\AbstractDataType;
use BoldMinded\DataGrab\DataTypes\StructuredArray;
use ExpressionEngine\Service\Database\DBConfig;
use ExpressionEngine\Service\Database\Database;

class Datagrab_sql extends AbstractDataType
{
    use StructuredArray;

    public string $type = 'SQL';

    public array $datatype_info = [
        'name' => 'SQL',
        'version' => '1.0',
        'description' => 'Import data from an external SQL database query',
        'allow_comments' => false,
        'allow_subloop' => true,
    ];

    public array $settings = [
        'server'   => '',
        'database' => '',
        'username' => '',
        'password' => '',
        'query'    => '',
    ];

    /* --------------------------------------------------------------------
     * Settings form (same fields as old Ajw_sql)
     * ------------------------------------------------------------------ */

    public function settings_form(array $values = []): array
    {
        return [
            [
                'title' => 'Database Server',
                'fields' => [
                    'server' => [
                        'type'  => 'text',
                        'value' => $this->get_value($values, 'server'),
                    ]
                ]
            ],
            [
                'title' => 'Database Name',
                'fields' => [
                    'database' => [
                        'type'  => 'text',
                        'value' => $this->get_value($values, 'database'),
                    ]
                ]
            ],
            [
                'title' => 'Database User',
                'fields' => [
                    'username' => [
                        'type'  => 'text',
                        'value' => $this->get_value($values, 'username'),
                    ]
                ]
            ],
            [
                'title' => 'Database Password',
                'fields' => [
                    'password' => [
                        'type'  => 'password',
                        'value' => $this->get_value($values, 'password'),
                    ]
                ]
            ],
            [
                'title' => 'SQL Query',
                'fields' => [
                    'query' => [
                        'required' => true,
                        'type'     => 'textarea',
                        'value'    => $this->get_value($values, 'query'),
                        'settings' => ['rows' => 8],
                    ]
                ]
            ]
        ];
    }

    /* --------------------------------------------------------------------
     * SAVE SETTINGS → write datagrab_database.php (OLD BEHAVIOR)
     * ------------------------------------------------------------------ */

    public function save_settings(array $settings): array
    {
        $this->writeDatabaseConfig($settings);
        return $settings;
    }

    private function writeDatabaseConfig(array $settings): void
    {
        $path = SYSPATH . 'user/config/datagrab_database.php';

        $content  = "<?php\n";
        $content .= "\$config['database']['datagrab'] = [\n";
        $content .= "    'hostname' => '" . addslashes($settings['server']) . "',\n";
        $content .= "    'database' => '" . addslashes($settings['database']) . "',\n";
        $content .= "    'username' => '" . addslashes($settings['username']) . "',\n";
        $content .= "    'password' => '" . addslashes($settings['password']) . "',\n";
        $content .= "    'dbdriver' => 'mysql',\n";
        $content .= "    'dbprefix' => '',\n";
        $content .= "    'port'     => '',\n";
        $content .= "];\n";

        file_put_contents($path, $content);
    }

    /* --------------------------------------------------------------------
     * FETCH (UNCHANGED WORKING VERSION)
     * ------------------------------------------------------------------ */

    public function fetch(string $data = '')
    {
        $queryString = trim($this->settings['query'] ?? '');

        if ($queryString === '') {
            $this->addError('No SQL query provided.');
            return -1;
        }

        try {
            $config = ee('Config')->getFile('datagrab_database');

            $dbConfig = new DBConfig($config);
            $dbConfig->getGroupConfig('datagrab');

            $db = new Database($dbConfig);

            $query = $db->newQuery()->query($queryString);

            if (! $query || $query->num_rows() === 0) {
                $this->addError('Query returned no rows.');
                return -1;
            }

            $rows = $query->result_array();

            $this->items = $rows;
            $this->itemsFlat = $rows;

            return 1;

        } catch (\Throwable $e) {
            $this->addError('Database error: ' . $e->getMessage());
            return -1;
        }
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function next()
    {
        $item = current($this->items);
        next($this->items);
        return $item;
    }
}
