<?php
namespace EEHarbor\ChannelImages\FluxCapacitor\Base;

use EEHarbor\ChannelImages\FluxCapacitor\FluxCapacitor;

/**
 * EEHarbor Pi parent class
 *
 * @package         EEHarbor plugin Parent
 * @version         4.11.0
 * @author          Tom Jaeger <Tom@EEHarbor.com>
 * @link            https://eeharbor.com
 * @copyright       Copyright (c) 2018, Tom Jaeger/EEHarbor
 */

class Pi
{
    public $flux;
    public $version;

    public function __construct()
    {
        $this->flux = new FluxCapacitor;
        $this->version = $this->flux->getConfig('version');
    }

    public static function info()
    {
        array(
            'pi_name'           => $this->flux->getConfig('name'),
            'pi_version'        => $this->flux->getConfig('version'),
            'pi_author'         => 'EEHarbor',
            'pi_author_url'     => 'https://eeharbor.com',
            'pi_description'    => $this->flux->getConfig('description')
        );
    }
}
