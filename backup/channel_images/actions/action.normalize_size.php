<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images NORMALIZE IMAGE SIZE action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com>
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_normalize_size extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Normalize Image Size',
        'name'      =>  'normalize_size',
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
        // Get settings
        $canvas_width = isset($this->settings['canvas_width']) ? (int)$this->settings['canvas_width'] : 1000;
        $canvas_height = isset($this->settings['canvas_height']) ? (int)$this->settings['canvas_height'] : 1000;
        $padding = isset($this->settings['padding']) ? (int)$this->settings['padding'] : 0;
        $background_type = isset($this->settings['background_type']) ? $this->settings['background_type'] : 'white';
        $quality = isset($this->settings['quality']) ? (int)$this->settings['quality'] : 100;
        
        // Open the source image
        $res = $this->open_image($file);
        if ($res != true) {
            return false;
        }
        
        $this->image_progressive = (isset($this->settings['field_settings']['progressive_jpeg']) === true && $this->settings['field_settings']['progressive_jpeg'] == 'yes') ? true : false;
        
        // Get source image dimensions
        $source_width = self::$imageResource_dim['width'];
        $source_height = self::$imageResource_dim['height'];
        
        // Calculate available space after padding
        $available_width = $canvas_width - ($padding * 2);
        $available_height = $canvas_height - ($padding * 2);
        
        // Calculate resize dimensions (maintain aspect ratio, fit within available space)
        $ratio = min($available_width / $source_width, $available_height / $source_height);
        $new_width = floor($source_width * $ratio);
        $new_height = floor($source_height * $ratio);
        
        // Create canvas
        $canvas = imagecreatetruecolor($canvas_width, $canvas_height);
        
        // Set background based on type
        if ($background_type == 'transparent' && (imagetypes() & IMG_PNG)) {
            // Enable transparency
            imagesavealpha($canvas, true);
            imagealphablending($canvas, false);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
            imagealphablending($canvas, true);
        } else {
            // White background (or fallback for transparent if PNG not supported)
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
        }
        
        // Create resized version of source image
        $resized = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG/GIF
        if (imagetypes() & IMG_PNG) {
            imagesavealpha($resized, true);
            imagealphablending($resized, false);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);
            imagealphablending($resized, true);
        }
        
        // Resize source image
        imagecopyresampled(
            $resized,
            self::$imageResource,
            0, 0,
            0, 0,
            $new_width, $new_height,
            $source_width, $source_height
        );
        
        // Calculate centered position on canvas
        $x = floor(($canvas_width - $new_width) / 2);
        $y = floor(($canvas_height - $new_height) / 2);
        
        // Copy resized image onto canvas (centered)
        imagecopy($canvas, $resized, $x, $y, 0, 0, $new_width, $new_height);
        
        // Clean up
        imagedestroy(self::$imageResource);
        imagedestroy($resized);
        
        // Replace the resource with our canvas
        self::$imageResource = $canvas;
        
        // Update dimensions
        self::$imageResource_dim['width'] = $canvas_width;
        self::$imageResource_dim['height'] = $canvas_height;
        
        // Save the image
        $this->save_image($file);
        
        return true;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;
    
        // Set defaults
        if (!isset($vData['canvas_width'])) {
            $vData['canvas_width'] = '1000';
        }
        if (!isset($vData['canvas_height'])) {
            $vData['canvas_height'] = '1000';
        }
        if (!isset($vData['padding'])) {
            $vData['padding'] = '0';
        }
        if (!isset($vData['background_type'])) {
            $vData['background_type'] = 'white';
        }
        if (!isset($vData['quality'])) {
            $vData['quality'] = '100';
        }
    
        // Pass variables to the view (they will be extracted)
        $vData['canvas_width']   = $vData['canvas_width'];
        $vData['canvas_height']  = $vData['canvas_height'];
        $vData['padding']        = $vData['padding'];
        $vData['background_type']= $vData['background_type'];
        $vData['quality']        = $vData['quality'];
    
        // Load the view file
        return ee()->load->view('actions/normalize_size', $vData, true);
    }

    // ********************************************************************************* //

    public function save_settings($settings)
    {
        ee()->cache->save('channel_images/group_final_size', $settings, 500);
        return $settings;
    }
}

/* End of file action.normalize_size.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.normalize_size.php */
