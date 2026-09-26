<?php  if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Channel Images Location Class
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com/channel-images
 */
class Image_Location
{
	
	public $lsettings = array();
	public $cdn_uri;
	public $CF_AUTH;
	
    /**
     * Constructor
     *
     * @access public
     */
    public function __construct($settings=array())
    {
        // Creat EE Instance
        //$this->EE =& get_instance();
        //ee()->load->add_package_path(PATH_THIRD . 'channel_images/');
        //ee()->load->library('image_helper');
    }

    // ********************************************************************************* //

    public function create_dir($dir)
    {
        return false;
    }

    // ********************************************************************************* //

    public function delete_dir($dir)
    {
        return false;
    }

    // ********************************************************************************* //

    public function upload_file($source_file, $dest_filename, $dest_folder)
    {
        return false;
    }

    // ********************************************************************************* //

    public function download_file($dir, $filename, $dest_folder)
    {
        return false;
    }

    // ********************************************************************************* //

    public function delete_file($dir, $filename)
    {
        return false;
    }

    // ********************************************************************************* //

    public function parse_image_url($dir, $filename)
    {
        return '';
    }

    // ********************************************************************************* //

    public function test_location()
    {
        exit('TEST FAILED!');
    }

    // ********************************************************************************* //
} // END CLASS

/* End of file image_location.php  */
/* Location: ./system/expressionengine/third_party/channel_images/locations/image_location.php */
