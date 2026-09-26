<?php

namespace EEHarbor\ChannelImages\FluxCapacitor;

use EEHarbor\ChannelImages\FluxCapacitor\Bridge\EE;

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
        $params = array("module" => "channel_images", "module_name" => "Channel Images");
        parent::__construct($params);
    }
}
