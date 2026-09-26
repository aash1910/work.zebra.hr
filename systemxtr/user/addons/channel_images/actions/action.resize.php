<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images RESIZE action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_resize extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Resize Image',
        'name'      =>  'resize',
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
        @set_include_path(PATH_THIRD.'channel_images/libraries/PHPThumb/');
        @set_include_path(PATH_THIRD.'channel_images/libraries/PHPThumb/thumb_plugins/');

        // Allow Upsizing?
        $upsize = true;
        if (isset($this->settings['upsizing']) == true && $this->settings['upsizing'] == 'no') {
            $upsize = false;
        }

        // Include the library
        if (class_exists('PhpThumbFactory') == false) {
            require_once PATH_THIRD.'channel_images/libraries/PHPThumb/ThumbLib.inc.php';
        }

        $progressive = (isset($this->settings['field_settings']['progressive_jpeg']) === true && $this->settings['field_settings']['progressive_jpeg'] == 'yes') ? 'yes' : 'no';

        // Create Instance
        $thumb = PhpThumbFactory::create($file, array('resizeUp' => $upsize, 'jpegQuality' => $this->settings['quality'], 'jpegProgressive' => $progressive));

        // Resize it!
        $thumb->resize($this->settings['width'], $this->settings['height']);

        // Save it
        $thumb->save($file);

        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['width']) == false) {
            $vData['width'] = '100';
        }
        if (isset($vData['height']) == false) {
            $vData['height'] = '100';
        }
        if (isset($vData['quality']) == false) {
            $vData['quality'] = '100';
        }
        if (isset($vData['upsizing']) == false) {
            $vData['upsizing'] = 'no';
        }

        return ee()->load->view('actions/resize', $vData, true);
    }

    // ********************************************************************************* //

    public function save_settings($settings)
    {
        ee()->cache->save('channel_images/group_final_size', $settings, 500);
        //$this->EE->session->cache['channel_images']['group_final_size'] = $settings;
        return $settings;
    }
}

/* End of file action.resize.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.resize.php */
