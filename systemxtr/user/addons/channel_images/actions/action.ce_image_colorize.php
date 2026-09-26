<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images CE IMAGE COLORIZE action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_ce_image_colorize extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'CE Image: Colorize',
        'name'      =>  'ce_image_colorize',
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

        if (isset($this->settings['alpha']) == false) {
            $this->settings['alpha'] = 0;
        }

        $CE->make(
            $file,
            array(
                'filters' => array(
                        array( 'colorize', $this->settings['red'], $this->settings['green'], $this->settings['blue'], $this->settings['alpha'])
                )
        ),
            true
        );

        $CE->close();

        //print_r($CE->debug_messages);

        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['red']) == false) {
            $vData['red'] = '0';
        }
        if (isset($vData['green']) == false) {
            $vData['green'] = '0';
        }
        if (isset($vData['blue']) == false) {
            $vData['blue'] = '0';
        }
        if (isset($vData['alpha']) == false) {
            $vData['alpha'] = '0';
        }

        return ee()->load->view('actions/ce_image_colorize', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.ce_image_colorize.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.ce_image_colorize.php */
