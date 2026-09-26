<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Store_corvuspay_upd
{
    public $version = '1';

    public function install()
    {
        // Register module
        ee()->db->insert('modules', [
            'module_name'        => 'Store_corvuspay',
            'module_version'     => $this->version,
            'has_cp_backend'     => 'n',
            'has_publish_fields' => 'n',
        ]);

        $this->ensureAction();
        return true;
    }

    public function uninstall()
    {
        ee()->db->where('module_name', 'Store_corvuspay')->delete('modules');
        ee()->db->where('class', 'Store_corvuspay')->delete('actions');
        return true;
    }

    public function update($current = '')
    {
        $this->ensureAction();

        // Keep module version in sync
        ee()->db->where('module_name', 'Store_corvuspay')
            ->update('modules', ['module_version' => $this->version]);

        return true;
    }

    private function ensureAction(): void
    {
        try {
            $act = ee('Model')->get('Action')
                ->filter('class', 'Store_corvuspay')
                ->filter('method', 'relay')
                ->first();
            if (!$act) {
                ee('Model')->make('Action', [
                    'class'       => 'Store_corvuspay',
                    'method'      => 'relay',
                    'csrf_exempt' => 1, // GET on ?ACT=… so exempt
                ])->save();
            }
        } catch (\Throwable $e) {
            // Fallback for older EE
            $q = ee()->db->get_where('actions', ['class' => 'Store_corvuspay', 'method' => 'relay'], 1);
            if ($q->num_rows() === 0) {
                ee()->db->insert('actions', [
                    'class'       => 'Store_corvuspay',
                    'method'      => 'relay',
                    'csrf_exempt' => 1,
                ]);
            }
        }
    }
}
