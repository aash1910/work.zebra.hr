<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images FILTER: PIXELATE action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_filter_pixelate extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Filter: Pixelate',
        'name'      =>  'filter_pixelate',
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

        if (defined('IMG_FILTER_PIXELATE') == false) {
            $this->info['enabled'] = false;
        }
    }

    // ********************************************************************************* //

    public function run($file, $temp_dir)
    {
        $res = $this->open_image($file);
        if ($res != true) {
            return false;
        }

        @imagefilter(self::$imageResource, IMG_FILTER_PIXELATE, $this->settings['block_size'], $this->settings['advanced']);

        $this->save_image($file);

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

/* End of file action.filter_pixelate.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.filter_pixelate.php */
