<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images CE IMAGE PIXELATE action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_ce_image_pixelate extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'CE Image: Pixelate',
        'name'      =>  'ce_image_pixelate',
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
        if (defined('IMG_FILTER_PIXELATE') == false) {
            $this->info['enabled'] = false;
        }
    }

    // ********************************************************************************* //

    public function run($file, $temp_dir)
    {
        if (class_exists('Ce_image') == false) {
            include PATH_THIRD.'ce_img/libraries/Ce_image.php';
        }
        $CE = new Ce_image(array('cache_dir' => '', 'unique' => 'none', 'overwrite_cache' => true, 'allow_overwrite_original' => true));

        if ($this->settings['advanced'] == 'yes') {
            $this->settings['advanced'] = true;
        } else {
            $this->settings['advanced'] = false;
        }

        $CE->make($file, array(
                'filters' => array(
                        array( 'pixelate', $this->settings['block_size'], $this->settings['advanced'])
                )
        ));

        $CE->close();

        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['block_size']) == false) {
            $vData['block_size'] = '0';
        }
        if (isset($vData['advanced']) == false) {
            $vData['advanced'] = 'no';
        }

        return ee()->load->view('actions/ce_image_pixelate', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.ce_image_pixelate.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.ce_image_pixelate.php */
