<?php if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class ChannelImagesUpdate_70101
{

    /**
     * Constructor
     *
     * @access public
     *
     * Calls the parent constructor
     */
    public function __construct()
    {
    }

    // ********************************************************************************* //

    public function do_update()
    {
        if (ee()->db->field_exists('sizes_metadata', 'channel_images') !== false) {
            $fields = array('sizes_metadata' => array('name' => 'sizes_metadata', 'type' => 'TEXT'));
            ee()->dbforge->modify_column('channel_images', $fields);
        }
    }

    // ********************************************************************************* //
}

/* End of file 5_04_00.php */
/* Location: ./system/user/addons/channel_images/updates/5_04_00.php */
