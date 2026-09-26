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
class ImageAction_im_watermark_image extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Imagick: Watermark (Image)',
        'name'      =>  'im_watermark_image',
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

        if (class_exists('Imagick')) {
            $this->info['enabled'] = true;
        }
    }

    // ********************************************************************************* //

    public function run($file, $temp_dir)
    {
        $image = new Imagick();
        $image->readImage($file);
        $watermark = new Imagick();
        $watermark->readImage($this->settings['overlay_path']);

        if (isset($this->settings['opacity']) === true && $this->settings['opacity'] != false) {
            $watermark->setImageOpacity($this->settings['opacity']);
        }

        /*
                // how big are the images?
                $iWidth = $image->getImageWidth();
                $iHeight = $image->getImageHeight();
                $wWidth = $watermark->getImageWidth();
                $wHeight = $watermark->getImageHeight();

                if ($iHeight < $wHeight || $iWidth < $wWidth) {
                    // resize the watermark
                    $watermark->scaleImage($iWidth, $iHeight);

                    // get new size
                    $wWidth = $watermark->getImageWidth();
                    $wHeight = $watermark->getImageHeight();
                }

                // calculate the position
                $x = ($iWidth - $wWidth) / 2;
                $y = ($iHeight - $wHeight) / 2;
        */

        $image->compositeImage($watermark, imagick::COMPOSITE_OVER, $this->settings['horizontal_offset'], $this->settings['vertical_offset']);
        $image->writeImage($file);
        $image->clear();
        $image->destroy();

        $watermark->clear();
        $watermark->destroy();

        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['overlay_path']) == false) {
            $vData['overlay_path'] = '';
        }
        if (isset($vData['horizontal_offset']) == false) {
            $vData['horizontal_offset'] = '0';
        }
        if (isset($vData['vertical_offset']) == false) {
            $vData['vertical_offset'] = '0';
        }
        if (isset($vData['opacity']) == false) {
            $vData['opacity'] = '1';
        }

        return ee()->load->view('actions/im_watermark_image', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.watermark_image.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.watermark_image.php */
