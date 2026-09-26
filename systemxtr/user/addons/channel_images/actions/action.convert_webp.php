<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images CONVERT TO WEBP action
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com>
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class ImageAction_convert_webp extends ImageAction
{

    /**
     * Action info - Required
     *
     * @access public
     * @var array
     */
    public $info = array(
        'title'     =>  'Convert to WebP',
        'name'      =>  'convert_webp',
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

        // Check if WebP is supported by either GD or Imagick
        if ((function_exists('imagewebp') && (imagetypes() & IMG_WEBP)) || class_exists('Imagick')) {
            $this->info['enabled'] = true;
        }
    }

    // ********************************************************************************* //

    public function run($file, $temp_dir)
    {
        // Use saved quality if present, otherwise default to 80
        $quality = isset($this->settings['quality']) && is_numeric($this->settings['quality'])
            ? (int)$this->settings['quality']
            : 80;

        // Clamp to valid WebP range
        $quality = max(0, min(100, $quality));
        
        $success = false;
        
        // Try Imagick first if available
        if (class_exists('Imagick')) {
            try {
                $image = new Imagick();
                $image->readImage($file);
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality($quality);
                // IMPORTANT: Write WebP content to the SAME filename (overwrite in place)
                $image->writeImage($file);
                $image->clear();
                $image->destroy();
                $success = true;
            } catch (Exception $e) {
                // Fall through to GD method
                $success = false;
            }
        }
        
        // Fall back to GD if Imagick failed or not available
        if (!$success && function_exists('imagewebp') && (imagetypes() & IMG_WEBP)) {
            $res = $this->open_image($file);
            
            if ($res == true) {
                // Save as WebP to the SAME filename (overwrite in place)
                if (imagewebp(self::$imageResource, $file, $quality)) {
                    $success = true;
                }
                imagedestroy(self::$imageResource);
            }
        }
        
        // Update the extension tracker so other actions know it's WebP now
        if ($success) {
            self::$imageExt = 'webp';
            return true;
        }
        
        return false;
    }

    // ********************************************************************************* //

    public function settings($settings)
    {
        $vData = $settings;
    
        // Set defaults
        if (!isset($vData['quality'])) {
            $vData['quality'] = '80';
        }
    
        // Pass variables to the view
        $vData['quality'] = $vData['quality'];
        // $action_field_name is automatically handled by the module
    
        // Load the view file
        return ee()->load->view('actions/convert_webp', $vData, true);
    }

    // ********************************************************************************* //

    public function save_settings($settings)
    {
        // No need for cache here (not size-related), but return to persist settings
        return $settings;
    }

}

/* End of file action.convert_webp.php */
/* Location: ./system/expressionengine/third_party/channel_images/actions/action.convert_webp.php */