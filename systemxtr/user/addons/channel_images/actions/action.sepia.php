<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images SEPIA action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_sepia extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Sepia',
        'name'      =>  'sepia',
        'version'   =>  '1.0',
        'enabled'   =>  true,
    );

	protected $Ageimage = array();

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

    public function settings($settings)
    {
        return ee()->lang->line('ci:sepia:exp');
    }

    // ********************************************************************************* //

    public function run($file, $temp_dir)
    {
        $res = $this->open_image($file);
        if ($res != true) {
            return false;
        }

        $this->image_progressive = (isset($this->settings['field_settings']['progressive_jpeg']) === true && $this->settings['field_settings']['progressive_jpeg'] == 'yes') ? true : false;

        $this->Ageimage = array(1, 0, 60);

        imagetruecolortopalette(self::$imageResource, 1, 256);
        for ($c=0;$c<256;$c++) {
			
			$colorsTotal = imagecolorstotal(self::$imageResource);
			
            $col=imagecolorsforindex(self::$imageResource, $colorsTotal - 1);
            $new_col=floor($col['red']*0.2125+$col['green']*0.7154+$col['blue']*0.0721);
            $noise=rand(-$this->Ageimage[1], $this->Ageimage[1]);
            if ($this->Ageimage[2]>0) {
                $r=$new_col+$this->Ageimage[2]+$noise;
                $g=floor($new_col+$this->Ageimage[2]/1.86+$noise);
                $b=floor($new_col+$this->Ageimage[2]/-3.48+$noise);
            } else {
                $r=$new_col+$noise;
                $g=$new_col+$noise;
                $b=$new_col+$noise;
            }
            imagecolorset(self::$imageResource, $c, max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
        }

        $this->save_image($file);

        return true;
    }

    // ********************************************************************************* //
}

/* End of file action.sepa.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.sepia.php */
