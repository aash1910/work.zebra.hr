<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images WATERMARK IMAGE action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_watermark_image extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Watermark (Image)',
        'name'      =>  'watermark_image',
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
        ee()->load->library('image_lib');

        $p = $this->settings;

        $config['wm_type'] = 'overlay';
        $config['source_image'] = $file;
        $config['dynamic_output'] = false;
        $config['quality'] = '100%';
        $config['wm_padding'] = $p['padding'];
        $config['wm_hor_offset']    = $p['horizontal_offset'];
        $config['wm_vrt_offset']    = $p['vertical_offset'];

        /*
        switch ($p['vertical_alignment'])
        {
            case 'center':
                $config['wm_vrt_alignment'] = 'C';
                break;
            case 'left':
                $config['wm_vrt_alignment'] = 'L';
                break;
            case 'right':
                $config['wm_vrt_alignment'] = 'R';
                break;
            default:
                $config['wm_vrt_alignment'] = 'C';
                break;
        }

        switch ($p['horizontal_alignment'])
        {
            case 'top':
                $config['wm_hor_alignment'] = 'T';
                break;
            case 'middle':
                $config['wm_hor_alignment'] = 'M';
                break;
            case 'bottom':
                $config['wm_hor_alignment'] = 'B';
                break;
            default:
                $config['wm_hor_alignment'] = 'B';
                break;
        }
        */

        $config['wm_vrt_alignment'] = $p['vertical_alignment'];
        $config['wm_hor_alignment'] = $p['horizontal_alignment'];

        // Overlay
        $config['wm_overlay_path'] = $p['overlay_path'];
        $config['wm_opacity']   = $p['opacity'];
        $config['wm_x_transp']  = $p['x_transp'];
        $config['wm_y_transp']  = $p['y_transp'];

        ee()->image_lib->initialize($config);
        ee()->image_lib->watermark();
        ee()->image_lib->clear();

        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['padding']) == false) {
            $vData['padding'] = '0';
        }
        if (isset($vData['horizontal_alignment']) == false) {
            $vData['horizontal_alignment'] = '';
        }
        if (isset($vData['vertical_alignment']) == false) {
            $vData['vertical_alignment'] = '';
        }
        if (isset($vData['horizontal_offset']) == false) {
            $vData['horizontal_offset'] = '0';
        }
        if (isset($vData['vertical_offset']) == false) {
            $vData['vertical_offset'] = '0';
        }

        if (isset($vData['overlay_path']) == false) {
            $vData['overlay_path'] = '';
        }
        if (isset($vData['opacity']) == false) {
            $vData['opacity'] = '50';
        }
        if (isset($vData['x_transp']) == false) {
            $vData['x_transp'] = '4';
        }
        if (isset($vData['y_transp']) == false) {
            $vData['y_transp'] = '4';
        }

        return ee()->load->view('actions/watermark_image', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.watermark_image.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.watermark_image.php */
