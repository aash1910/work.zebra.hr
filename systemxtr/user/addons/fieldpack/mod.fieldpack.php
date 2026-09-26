<?php

// This is what we have to do for EE2 support
require_once 'addon.setup.php';

use EEHarbor\Fieldpack\FluxCapacitor\FluxCapacitor;
use EEHarbor\Fieldpack\FluxCapacitor\Base\Mod;

/**
 * Fieldpack Module
 *
 * @package   Fieldpack
 * @author    EEHarbor <help@eeharbor.com>
 * @copyright Copyright (c) 2016 EEHarbor
 */
class Fieldpack extends Mod
{
    public function __construct()
    {
        parent::__construct();
    }
}
