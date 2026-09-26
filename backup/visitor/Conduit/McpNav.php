<?php

namespace EEHarbor\Visitor\Conduit;

use EEHarbor\Visitor\FluxCapacitor\Conduit\McpNav as FluxNav;

class McpNav extends FluxNav
{
    protected function defaultItems()
    {
        $this->setToolbarIcon(lang('v:settings'), 'settings_mace');

        $default_items = array(
            'HEADING-Members' => lang('v:members_as_entries'),
            'installation' => lang('v:installation'),
            'sync' => lang('v:sync_mdata'),
            'settings_mace' => lang('v:settings'),
        );

        return $default_items;
    }
}
