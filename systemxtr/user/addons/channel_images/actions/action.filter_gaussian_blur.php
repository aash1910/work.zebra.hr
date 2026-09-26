<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images FILTER: GAUSSIAN_BLUR action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_filter_gaussian_blur extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Filter: Gaussian Blur',
        'name'      =>  'filter_gaussian_blur',
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

    public function run($file, $temp_dir)
    {
        $res = $this->open_image($file);

        if ($res != true) {
            return false;
        }

        if (isset($this->settings['repeat']) === true && $this->settings['repeat'] > 1) {
            for ($i=0; $i < $this->settings['repeat']; $i++) {
                @imagefilter(self::$imageResource, IMG_FILTER_GAUSSIAN_BLUR);
            }
        } else {
            @imagefilter(self::$imageResource, IMG_FILTER_GAUSSIAN_BLUR);
        }

        $this->save_image($file);

        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['repeat']) == false) {
            $vData['repeat'] = '1';
        }

        return ee()->load->view('actions/ce_image_gaussian_blur', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.ce_image_gaussian_blur.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.ce_image_gaussian_blur.php */
