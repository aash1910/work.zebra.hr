<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images GREYSCALE action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_jpeg_adjust_quality extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'JPEG Adjust Quality',
        'name'      =>  'jpeg_adjust_quality',
        'version'   =>  '1.0',
        'enabled'   =>  true,
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
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['quality']) == false) {
            $vData['quality'] = '85';
        }

        return ee()->load->view('actions/jpeg_adjust_quality', $vData, true);
    }

    // ********************************************************************************* //

    public function run($file, $temp_dir)
    {
        $res = $this->open_image($file);
        if ($res != true) {
            return false;
        }

        $this->image_progressive = (isset($this->settings['field_settings']['progressive_jpeg']) === true && $this->settings['field_settings']['progressive_jpeg'] == 'yes') ? true : false;

        if (self::$imageExt == 'jpg' || self::$imageExt == 'jpeg') {
            $this->image_jpeg_quality = $this->settings['quality'];
            $this->save_image($file);
        }

        return true;
    }

    // ********************************************************************************* //
}

/* End of file action.jpeg_adjust_quality.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.jpeg_adjust_quality.php */
