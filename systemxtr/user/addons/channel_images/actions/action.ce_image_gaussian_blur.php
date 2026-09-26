<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images CE IMAGE GAUSSIAN_BLUR action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_ce_image_gaussian_blur extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'CE Image: Gaussian Blur',
        'name'      =>  'ce_image_gaussian_blur',
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

        if (isset($this->settings['repeat']) === true && $this->settings['repeat'] > 1) {
            for ($i=0; $i < $this->settings['repeat']; $i++) {
                $CE->make($file, array(
                        'filters' => 'gaussian_blur'
                ));
            }
        } else {
            $CE->make($file, array(
                    'filters' => 'gaussian_blur'
            ));
        }

        $CE->close();

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
