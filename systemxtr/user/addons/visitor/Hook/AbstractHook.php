<?php

namespace EEHarbor\Visitor\Hook;

/**
 * Abstract Hook Class
 *
 * @package         EEHarbor_Visitor
 * @author          EEHarbor <https://www.eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <https://www.parscale.com>
 * @license         https://www.eeharbor.com/license/
 * @link            https://www.eeharbor.com
 */
abstract class AbstractHook
{
    protected $site_id;
    protected $settings = array();
    public $lastCall;
    public $endScript = false;

    public function __construct()
    {
        $this->site_id = ee()->config->item('site_id');
        $this->settings = ee('visitor:Settings')->settings;
    }

    // ********************************************************************************* //

    //abstract public function execute();

    // ********************************************************************************* //
}

/* End of file AbstractHook.php */
/* Location: ./system/user/addons/Visitor/Hook/AbstractHook.php */
