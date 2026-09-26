<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images CE IMAGE BORDER action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_ce_image_border extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'CE Image: Border',
        'name'      =>  'ce_image_border',
        'version'   =>  '1.0',
        'enabled'   =>  false,
    );

    /**
     * Constructor
     *
     * @access public
     *
     * Calls the parent constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (file_exists(PATH_THIRD.'ce_img/pi.ce_img.php') != false) {
            $this->info['enabled'] = true;
        }
    }

    // ********************************************************************************* //

    public function run($file, $temp_dir)
    {
        if (class_exists('Ce_image') == false) {
            include PATH_THIRD.'ce_img/libraries/Ce_image.php';
        }
        $CE = new Ce_image(array('cache_dir' => '', 'unique' => 'none', 'overwrite_cache' => true, 'allow_overwrite_original' => true));

        $CE->make($file, array(
                'border' => array($this->settings['thickness'], $this->settings['color'])
        ));

        $CE->close();

        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['thickness']) == false) {
            $vData['thickness'] = '1';
        }
        if (isset($vData['color']) == false) {
            $vData['color'] = '000000';
        }

        return ee()->load->view('actions/ce_image_border', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.crop_center.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.crop_center.php */
