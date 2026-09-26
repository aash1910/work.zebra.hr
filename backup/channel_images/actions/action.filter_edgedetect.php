<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images CE IMAGE EDGEDETECT action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_filter_edgedetect extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'CE Image: Edgedetect',
        'name'      =>  'filter_edgedetect',
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

        @imagefilter(self::$imageResource, IMG_FILTER_EDGEDETECT);

        $this->save_image($file);

        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        //if (isset($vData['edgedetect']) == FALSE) $vData['edgedetect'] = '10';

        return ee()->load->view('actions/ce_image_edgedetect', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.filter_edgedetect.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.filter_edgedetect.php */
