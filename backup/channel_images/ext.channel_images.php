<?php

use EEHarbor\ChannelImages\FluxCapacitor\Base\Ext;

/**
 * Channel Images Module Extension File
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2011 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com
 * @see             http://expressionengine.com/user_guide/development/module_tutorial.html#core_module_file
 */
class Channel_images_ext extends Ext
{
    public $name            = 'Channel Images Extension';
    public $description     = 'Supports the Channel Images Module in various functions.';
    public $docs_url        = 'https://eeharbor.com';
    public $settings_exist  = false;
    public $settings        = array();
    public $hooks           = array('wygwam_config', 'wygwam_tb_groups', 'wygwam_before_display', 'wygwam_before_save', 'wygwam_before_replace', 'editor_before_display', 'editor_before_save', 'editor_before_replace');

    public $site_id;
    private static $_included_ckeditor_resources = false;

    // ********************************************************************************* //

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->site_id = ee()->config->item('site_id');
    }

    // ********************************************************************************* //

    /**
     * This hook will enable you to override your Wygwam fields� CKEditor config settings right on page load, taking your Wygwam customizations to a whole new level.
     *
     * @param array $config The array of config settings that are about to be JSON-ified and sent to CKEditor during field initialization.
     * @param array $settings The full array of your field�s settings, as they were before being translated into the $config array.
     * @access public
     * @see http://pixelandtonic.com/wygwam/docs/wygwam_config
     * @return array
     */
    public function wygwam_config($config, $settings)
    {
        // Check if we're not the only one using this hook
        if (ee()->extensions->last_call !== false) {
            $config = ee()->extensions->last_call;
        }

        // Check if our toolbar button has been added
        $include_btn = false;

        if (isset($config['toolbar']) === false) {
            return $config;
        }

        foreach ($config['toolbar'] as $tbgroup) {
            if (is_array($tbgroup) && in_array('ChannelImages', $tbgroup)) {
                $include_btn = true;
                break;
            }
        }

        if ($include_btn) {
            // Add our plugin to CKEditor
            if (!empty($config['extraPlugins'])) {
                $config['extraPlugins'] .= ',';
            }

            $config['extraPlugins'] .= 'channelimages';

            $this->_include_ckeditor_resources();
        }

        return $config;
    }

    // ********************************************************************************* //

    public function wygwam_tb_groups($tb_groups=array())
    {
        if (($last_call = ee()->extensions->last_call) !== false) {
            $tb_groups = $last_call;
        }

        $tb_groups[] = array('ChannelImages');

        // Is this the toolbar editor?
        if (ee()->input->get('M') == 'settings') {
            // Give our toolbar button an icon
            $icon_url = URL_THIRD_THEMES.'channel_images/img/select_images.png';
            ee()->cp->add_to_head('<style type="text/css">.cke_button__channelimages_icon { background: url('.$icon_url.') !important; }</style>');
        }

        return $tb_groups;
    }

    // ********************************************************************************* //

    public function wygwam_before_save($obj, $data)
    {
        // Check if we're not the only one using this hook
        if (ee()->extensions->last_call !== false) {
            $data = ee()->extensions->last_call;
        }

        if (class_exists('Channel_Images_API') === false) {
            require PATH_THIRD.'channel_images/api.channel_images.php';
        }
        $API = new Channel_Images_API();
        $data = $API->convertUrlsToTags($data);

        return $data;
    }

    // ********************************************************************************* //

    public function wygwam_before_display($obj, $data)
    {
        // Check if we're not the only one using this hook
        if (ee()->extensions->last_call !== false) {
            $data = ee()->extensions->last_call;
        }

        if (class_exists('Channel_Images_API') === false) {
            require PATH_THIRD.'channel_images/api.channel_images.php';
        }
        $API = new Channel_Images_API();

        $entry_id = (! empty($obj->content_id())) ? $obj->content_id() : ee()->input->get_post('entry_id');

        $data = $API->generateUrlsFromTags($data, $entry_id);

        return $data;
    }

    // ********************************************************************************* //

    public function wygwam_before_replace($obj, $data)
    {
        // Check if we're not the only one using this hook
        if (ee()->extensions->last_call !== false) {
            $data = ee()->extensions->last_call;
        }

        if (class_exists('Channel_Images_API') === false) {
            require PATH_THIRD.'channel_images/api.channel_images.php';
        }
        $API = new Channel_Images_API();

        $entry_id = 0;
        if (isset($obj->row['entry_id']) === true) {
            $entry_id = $obj->row['entry_id'];
        }

        $data = $API->generateUrlsFromTags($data, $entry_id, true);

        return $data;
    }

    // ********************************************************************************* //

    public function editor_before_save($obj, $data)
    {
        // Check if we're not the only one using this hook
        if (ee()->extensions->last_call !== false) {
            $data = ee()->extensions->last_call;
        }

        if (class_exists('Channel_Images_API') === false) {
            require PATH_THIRD.'channel_images/api.channel_images.php';
        }
        $API = new Channel_Images_API();
        $data = $API->convertUrlsToTags($data);

        return $data;
    }

    // ********************************************************************************* //

    public function editor_before_display($obj, $data)
    {
        // Check if we're not the only one using this hook
        if (ee()->extensions->last_call !== false) {
            $data = ee()->extensions->last_call;
        }

        if (class_exists('Channel_Images_API') === false) {
            require PATH_THIRD.'channel_images/api.channel_images.php';
        }
        $API = new Channel_Images_API();

        $entry_id = ee()->input->get_post('entry_id');
        $data = $API->generateUrlsFromTags($data, $entry_id);

        return $data;
    }

    // ********************************************************************************* //

    public function editor_before_replace($obj, $data)
    {
        // Check if we're not the only one using this hook
        if (ee()->extensions->last_call !== false) {
            $data = ee()->extensions->last_call;
        }

        if (class_exists('Channel_Images_API') === false) {
            require PATH_THIRD.'channel_images/api.channel_images.php';
        }
        $API = new Channel_Images_API();

        $entry_id = 0;
        if (isset($obj->row['entry_id']) === true) {
            $entry_id = $obj->row['entry_id'];
        }

        $data = $API->generateUrlsFromTags($data, $entry_id, true);

        return $data;
    }

    // ********************************************************************************* //

    private function _include_ckeditor_resources()
    {
        // Is this the first time we've been called?
        if (!self::$_included_ckeditor_resources) {
            // Tell CKEditor where to find our plugin
            $plugin_url = URL_THIRD_THEMES.'channel_images/ckeditor/';
            ee()->cp->add_to_foot('<script type="text/javascript">CKEDITOR.plugins.addExternal("channelimages", "'.$plugin_url.'");</script>');

            // Don't do that again
            self::$_included_ckeditor_resources = true;
        }
    }

    // ********************************************************************************* //

    /**
     * Called by ExpressionEngine when the user activates the extension.
     *
     * @access      public
     * @return      void
     **/
    public function activate_extension()
    {
        foreach ($this->hooks as $hook) {
            $data = array( 'class'     =>  __CLASS__,
                            'method'    =>  $hook,
                            'hook'      =>  $hook,
                            'settings'  =>  serialize($this->settings),
                            'priority'  =>  100,
                            'version'   =>  $this->version,
                            'enabled'   =>  'y'
                );

            // insert in database
            ee()->db->insert('extensions', $data);
        }
    }

    // ********************************************************************************* //

    /**
     * Called by ExpressionEngine when the user disables the extension.
     *
     * @access      public
     * @return      void
     **/
    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }

    // ********************************************************************************* //

    /**
     * Called by ExpressionEngine updates the extension
     *
     * @access public
     * @return void
     **/
    public function update_extension($current=false)
    {
        if ($current == $this->version) {
            return false;
        }

        // Get all existing ones
        $dbexts = array();
        $query = ee()->db->select('*')->from('extensions')->where('class', __CLASS__)->get();

        foreach ($query->result() as $row) {
            $dbexts[$row->hook] = $row;
        }

        // Add the new ones
        foreach ($this->hooks as $hook) {
            if (isset($dbexts[$hook]) === true) {
                continue;
            }

            $data = array(
                'class'     =>  __CLASS__,
                'method'    =>  $hook,
                'hook'      =>  $hook,
                'settings'  =>  serialize($this->settings),
                'priority'  =>  100,
                'version'   =>  $this->version,
                'enabled'   =>  'y'
            );

            // insert in database
            ee()->db->insert('extensions', $data);
        }

        // Delete old ones
        foreach ($dbexts as $hook => $ext) {
            if (in_array($hook, $this->hooks) === true) {
                continue;
            }

            ee()->db->where('hook', $hook);
            ee()->db->where('class', __CLASS__);
            ee()->db->delete('extensions');
        }

        // Update the version number for all remaining hooks
        ee()->db->where('class', __CLASS__)->update('extensions', array('version' => $this->version));
    }

    // ********************************************************************************* //
} // END CLASS

/* End of file ext.channel_images.php */
/* Location: ./system/user/addons/channel_images/ext.channel_images.php */
