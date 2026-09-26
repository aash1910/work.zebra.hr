<?php

namespace EEHarbor\Visitor\FluxCapacitor;

use EEHarbor\Visitor\FluxCapacitor\Bridge\EE;

/**
 * Flux Capacitor
 *
 * @package         Flux Capacitor
 * @version         4.11.0
 * @author          Tom Jaeger <Tom@EEHarbor.com>
 * @link            https://eeharbor.com
 * @copyright       Copyright (c) 2018, Tom Jaeger/EEHarbor
 */
class FluxCapacitor extends EE
{
    const L = false;

    public function __construct()
    {
        $params = array("module" => "visitor", "module_name" => "Visitor");
        parent::__construct($params);
    }
}
