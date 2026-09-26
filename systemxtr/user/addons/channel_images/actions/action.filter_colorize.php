<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images COLORIZE action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_filter_colorize extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Filter: Colorize',
        'name'      =>  'filter_colorize',
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

        @imagefilter(self::$imageResource, IMG_FILTER_COLORIZE, $this->settings['red'], $this->settings['green'], $this->settings['blue'], $this->settings['alpha']);

        $this->save_image($file);

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

        return ee()->load->view('actions/filter_colorize', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.filter_colorize.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.filter_colorize.php */
