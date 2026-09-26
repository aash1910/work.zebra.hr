<?php if (!defined('BASEPATH')) {
    die('No direct script access allowed');
}

/**
 * Channel Images Module RTE
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com
 * @see             http://expressionengine.com/user_guide/development/fieldtypes.html
 */
class Channel_images_rte
{
    public $info = array(
        'name'          => 'Channel Images',
        'version'       => CHANNEL_IMAGES_VERSION,
        'description'   => 'Allows you to use images uploaded through Channel Images in the RTE',
        'cp_only'       => 'n'
    );

    private $EE;

    /**
     * Constructor
     *
     * @access  public
     */
    public function __construct()
    {
        // Make a local reference of the ExpressionEngine super object

        ee()->load->add_package_path(PATH_THIRD . 'channel_images/');
        ee()->lang->loadfile('channel_images');
        //$this->EE->load->library('image_helper');
        //ee('channel_images:Helper')->define_theme_url();
    }

    // ********************************************************************************* //

    /**
     * Globals we need
     *
     * @access  public
     */
    public function globals()
    {
        return array(
            'rte.channel_images'=> array(
                'label'         => lang('channel_images'),
                'add_image'     => lang('ci:add_image'),
                'no_images'     => lang('ci:no_images'),
                'original'      => lang('ci:original'),
                'caption_text'  => lang('ci:caption_text'),
            )
        );
    }

    // ********************************************************************************* //

    public function libraries()
    {
        return array(
            'ui'    => array('dialog', 'tabs')
        );
    }

    // ********************************************************************************* //

    /**
     * Styles we need
     *
     * @access  public
     */
    public function styles()
    {
        $styles = file_get_contents(PATH_THIRD_THEMES.'channel_images/channel_images_rte.css', true);

        return str_replace('{theme_folder_url}', ee('channel_images:Helper')->getThemeUrl(), $styles);
    }

    // ********************************************************************************* //

    /**
     * JS Defintion
     *
     * @access  public
     */
    public function definition()
    {
        return file_get_contents(PATH_THIRD_THEMES.'channel_images/channel_images_rte.js', true);
    }

    // ********************************************************************************* //
} // END Channel_images_rte

/* End of file rte.channel_images.php */
/* Location: ./system/user/addons/channel_images/rte.channel_images.php */
