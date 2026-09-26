<?php

// This is what we have to do for EE2 support
require_once 'addon.setup.php';

use EEHarbor\Fieldpack\FluxCapacitor\FluxCapacitor;
use EEHarbor\Fieldpack\FluxCapacitor\Base\Mcp;

class Fieldpack_mcp extends Mcp
{
    public function __construct()
    {
        parent::__construct();
    }
}
