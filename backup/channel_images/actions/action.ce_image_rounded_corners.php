<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images CE IMAGE REFLECTION action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_ce_image_rounded_corners extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'CE Image: Rounded Corners',
        'name'      =>  'ce_image_rounded_corners',
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
        $this->info['enabled'] = false; //Disable it temp
    }

    // ********************************************************************************* //

    public function run($file, $temp_dir)
    {
        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['corner_identifier']) == false) {
            $vData['corner_identifier'] = 'all';
        }
        if (isset($vData['radius']) == false) {
            $vData['radius'] = '30';
        }
        if (isset($vData['color']) == false) {
            $vData['color'] = 'ffffff';
        }

        return ee()->load->view('actions/ce_image_rounded_corners', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.ce_image_rounded_corners.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.ce_image_rounded_corners.php */
