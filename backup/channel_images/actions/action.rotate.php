<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images ROTATE action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_rotate extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Rotate Image',
        'name'      =>  'rotate',
        'version'   =>  '1.0',
        'enabled'   =>  true,
    );
	
	protected $size; 
	protected $im;
	
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
        $progressive = (isset($this->settings['field_settings']['progressive_jpeg']) === true && $this->settings['field_settings']['progressive_jpeg'] == 'yes') ? true : false;

        $this->size = getimagesize($file);

        $width = $this->size[0];
        $height = $this->size[1];

        if (isset($this->settings['only_if']) === true) {
            // Do we need to rotate?
            if ($this->settings['only_if'] == 'width_bigger' && $width < $height) {
                return true;
            } elseif ($this->settings['only_if'] == 'height_bigger' && $height < $width) {
                return true;
            }
        }

        switch ($this->size[2]) {
            case 1:
                if (imagetypes() & IMG_GIF) {
                    $this->im = imagecreatefromgif($file);
                } else {
                    return 'No GIF Support!';
                }
                break;
            case 2:
                if (imagetypes() & IMG_JPG) {
                    $this->im = imagecreatefromjpeg($file);
                } else {
                    return 'No JPG Support!';
                }
                break;
            case 3:
                if (imagetypes() & IMG_PNG) {
                    $this->im=imagecreatefrompng($file);
                } else {
                    return 'No PNG Support!';
                }
                break;
            default:
                return 'File Type??';
        }

        $this->settings['background_color'];
        $this->settings['degrees'];

        $this->im = imagerotate($this->im, 360-$this->settings['degrees'], hexdec($this->settings['background_color']));

        switch ($this->size[2]) {
            case 1:
                imagegif($this->im, $file);
                break;
            case 2:
                if ($progressive === true) {
                    @imageinterlace($this->im, 1);
                }
                imagejpeg($this->im, $file, 100);
                break;
            case 3:
                imagepng($this->im, $file);
                break;
        }

        imagedestroy($this->im);

        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;

        if (isset($vData['background_color']) == false) {
            $vData['background_color'] = 'ffffff';
        }
        if (isset($vData['degrees']) == false) {
            $vData['degrees'] = '90';
        }
        if (isset($vData['only_if']) == false) {
            $vData['only_if'] = 'always';
        }

        return ee()->load->view('actions/rotate', $vData, true);
    }

    // ********************************************************************************* //
}

/* End of file action.rotate.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.rotate.php */
