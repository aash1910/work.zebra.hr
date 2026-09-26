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
class ImageAction_ce_image_reflection extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'CE Image: Reflection',
        'name'      =>  'ce_image_reflection',
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
        $CE = new Ce_image(array('cache_dir' => $temp_dir, 'unique' => 'none', 'overwrite_cache' => true, 'allow_overwrite_original' => true));

        $CE->make($file, array(
                'reflection' => array($this->settings['gap_height'], $this->settings['start_opacity'], $this->settings['end_opacity'], $this->settings['reflection_height'])
        ), true);

        $CE->close();

        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['gap_height']) == false) {
            $vData['gap_height'] = '0';
        }
        if (isset($vData['start_opacity']) == false) {
            $vData['start_opacity'] = '80';
        }
        if (isset($vData['end_opacity']) == false) {
            $vData['end_opacity'] = '0';
        }
        if (isset($vData['reflection_height']) == false) {
            $vData['reflection_height'] = '50%';
        }

        return ee()->load->view('actions/ce_image_reflection', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.ce_image_reflection.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.ce_image_reflection.php */
