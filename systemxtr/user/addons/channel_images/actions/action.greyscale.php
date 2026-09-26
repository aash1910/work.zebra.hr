<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images GREYSCALE action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_greyscale extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Greyscale',
        'name'      =>  'greyscale',
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

        //if (function_exists('imagefilter') === false) $this->info['enabled'] = FALSE;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        return ee()->lang->line('ci:greyscale:exp');
    }

    // ********************************************************************************* //

    public function run($file, $temp_dir)
    {
        $res = $this->open_image($file);
        if ($res != true) {
            return false;
        }

        $this->image_progressive = (isset($this->settings['field_settings']['progressive_jpeg']) === true && $this->settings['field_settings']['progressive_jpeg'] == 'yes') ? true : false;

        if (function_exists('imagefilter') === true) {
            @imagefilter(self::$imageResource, IMG_FILTER_GRAYSCALE);
        } else {
            $img_width  = imageSX(self::$imageResource);
            $img_height = imageSY(self::$imageResource);

            // convert to grayscale
            $palette = array();
            for ($c=0;$c<256;$c++) {
                $palette[$c] = imagecolorallocate(self::$imageResource, $c, $c, $c);
            }

            for ($y=0;$y<$img_height;$y++) {
                for ($x=0;$x<$img_width;$x++) {
                    $rgb = imagecolorat(self::$imageResource, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $gs = (($r*0.299)+($g*0.587)+($b*0.114));
                    imagesetpixel(self::$imageResource, $x, $y, $palette[$gs]);
                }
            }
        }

        $this->save_image($file);

        return true;
    }

    // ********************************************************************************* //
}

/* End of file action.greyscale.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.greyscale.php */
