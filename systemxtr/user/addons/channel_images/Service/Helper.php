<?php

namespace EEHarbor\ChannelImages\Service;

class Helper
{
    protected $package_name = CHANNEL_IMAGES_CLASS_NAME;
    protected $package_version = CHANNEL_IMAGES_VERSION;
    protected $actionUrlCache = array();
    protected $site_id;
	public $image_ext;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->site_id = ee()->config->item('site_id');
        //ee()->load->library('image_helper');
        require_once PATH_THIRD.'channel_images/libraries/image_helper.php';
    }

    public function get_upload_preferences($group_id = null, $id = null, $ignore_site_id = false)
    {
        if (version_compare(APP_VER, '2.4', '>=')) {
            ee()->load->model('file_upload_preferences_model');
            $row = ee()->file_upload_preferences_model->get_file_upload_preferences($group_id, $id, $ignore_site_id);
            ee()->cache->save('upload_prefs/'.$id, $row, 500);
            return $row;
        }

        if (version_compare(APP_VER, '2.1.5', '>=')) {
            // for admins, no specific filtering, just give them everything
            if ($group_id == 1) {
                // there a specific upload location we're looking for?
                if ($id != '') {
                    ee()->db->where('id', $id);
                }

                ee()->db->from('upload_prefs');
                if ($ignore_site_id != true) {
                    ee()->db->where('site_id', ee()->config->item('site_id'));
                }
                ee()->db->order_by('name');

                $result = ee()->db->get();
            } else {
                // non admins need to first be checked for restrictions
                // we'll add these into a where_not_in() check below
                ee()->db->select('upload_id');

                if (version_compare(APP_VER, '6.0', '>=')) {
                    $upload_destinations = ee('Model')->get('UploadDestination')->all();
                    $denied = array();
                    foreach ($upload_destinations as $dir) {
                        if ( ! $dir->memberHasAccess(ee()->session->getMember()))
                        {
                            $denied[] = $dir->id;
                        }
                    }
                    if (!empty($denied))
                    {
                        ee()->db->where_not_in('id', $denied);
                    }
                } else {
                    $no_access = ee()->db->get_where('upload_no_access', array('member_group'=>$group_id));

                    if ($no_access->num_rows() > 0) {
                        $denied = array();
                        foreach ($no_access->result() as $result) {
                            $denied[] = $result->upload_id;
                        }
                        ee()->db->where_not_in('id', $denied);
                    }
                }

                // there a specific upload location we're looking for?
                if ($id) {
                    ee()->db->where('id', $id);
                }

                ee()->db->from('upload_prefs');
                if ($ignore_site_id != true) {
                    ee()->db->where('site_id', ee()->config->item('site_id'));
                }
                ee()->db->order_by('name');

                $result = ee()->db->get();
            }
        } else {
            ee()->load->model('tools_model');
            $result = ee()->tools_model->get_upload_preferences($group_id, $id);
        }

        // If an $id was passed, just return that directory's preferences
        if (! empty($id)) {
            $result = $result->row_array();
            ee()->cache->save('upload_prefs/'.$id, $result, 500);
            return $result;
        }

        // Use upload destination ID as key for row for easy traversing
        $return_array = array();
        foreach ($result->result_array() as $row) {
            $return_array[$row['id']] = $row;
        }

        ee()->cache->save('upload_prefs/'.$id, $return_array, 500);

        return $return_array;
    }

    public function getRouterUrl($type='url', $method='channel_images_router')
    {
        // -----------------------------------------
        // Grab action_id
        // -----------------------------------------
        if (isset($this->actionUrlCache[$method]['action_id']) === false) {
            $action = ee('Model')->get('Action')
            ->filter('class', ucfirst($this->package_name))
            ->filter('method', $method)
            ->fields('action_id')
            ->first();

            if (!$action) {
                return false;
            }

            $action_id = $action->action_id;
        } else {
            $action_id = $this->actionUrlCache[$method]['action_id'];
        }

        // -----------------------------------------
        // Return FULL action URL
        // -----------------------------------------
        if ($type == 'url') {
            // Grab Site URL
            $url = ee()->functions->fetch_site_index(0, 0);

            if (defined('MASKED_CP') == false or MASKED_CP == false) {
                // Replace site url domain with current working domain
                $server_host = (isset($_SERVER['HTTP_HOST']) == true && $_SERVER['HTTP_HOST'] != false) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
                $url = preg_replace('#http\://(([\w][\w\-\.]*)\.)?([\w][\w\-]+)(\.([\w][\w\.]*))?\/#', "http://{$server_host}/", $url);
            }

            // Create new URL
            $ajax_url = $url.QUERY_MARKER.'ACT=' . $action_id;

            // Config Override for action URLs?
            $config = ee()->config->item($this->package_name);
            $over = isset($config['action_url']) ? $config['action_url'] : array();

            if (is_array($over) === true && isset($over[$method]) === true) {
                $url = $over[$method];
            }

            // Protocol Relative URL
            $ajax_url = str_replace(array('https://', 'http://'), '//', $ajax_url);

            return $ajax_url;
        }

        return $action_id;
    }

    public function getThemeUrl($root=false)
    {
        if (defined('URL_THIRD_THEMES') === true) {
            $theme_url = URL_THIRD_THEMES;
        } else {
            $theme_url = ee()->config->slash_item('theme_folder_url').'third_party/';
        }

        $theme_url = str_replace(array('http://','https://'), '//', $theme_url);

        if ($root) {
            return $theme_url;
        }

        $theme_url .= $this->package_name . '/';

        return $theme_url;
    }

    public function mcpAssets($type, $name=null, $dir=null, $addon=false)
    {
        $ajaxUrl  = $this->getRouterUrl('url');
        $themeUrl = $this->getThemeUrl();
        $addon = $this->package_name ?: 'eeharbor';

        // -----------------------------------------
        // CSS
        // -----------------------------------------
        if ($type == 'css' && !ee()->session->cache($addon, $name)) {
            $url = $dir ? "{$themeUrl}{$dir}/{$name}" : "{$themeUrl}css/{$name}";
            ee()->cp->add_to_head('<link rel="stylesheet" href="' . $url . '?v='.$this->package_version.'" type="text/css" media="print, projection, screen" />');
            ee()->session->set_cache($addon, $name, 'yes');
        }

        // -----------------------------------------
        // Javascript
        // -----------------------------------------
        if ($type == 'js' && !ee()->session->cache($addon, $name)) {
            $url = $dir ? "{$themeUrl}{$dir}/{$name}" : "{$themeUrl}js/{$name}";
            ee()->cp->add_to_foot('<script src="' . $url . '?v='.$this->package_version.'" type="text/javascript"></script>');
            ee()->session->set_cache($addon, $name, 'yes');
        }

        // -----------------------------------------
        // Global Inline Javascript
        // -----------------------------------------
        if ($type == 'gjs') {
            if (!ee()->session->cache($addon, 'gjs.' . $this->package_name)) {
                $js = " var ChannelImages = ChannelImages ? ChannelImages : {};
                        ChannelImages.AJAX_URL = '{$ajaxUrl}&site_id={$this->site_id}';
                        ChannelImages.THEME_URL = '{$themeUrl}';
                        ChannelImages.site_id = '{$this->site_id}';
                ";

                ee()->cp->add_to_foot('<script type="text/javascript">' . $js . '</script>');
                ee()->session->set_cache($addon, 'gjs.' . $this->package_name, 'yes');
            }
        }
    }

    public function isSsl()
    {
        $is_SSL = false;

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
            $is_SSL = true;
        }

        return $is_SSL;
    }

    public function formatBytes($bytes)
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2).' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2).' MB';
        } elseif ($bytes < 1099511627776) {
            return round($bytes / 1073741824, 2).' GB';
        } else {
            return round($bytes / 1099511627776, 2).' TB';
        }
    }

    // ********************************************************************************* //




    ////// LEGACY

    /**
     * Get Entry_ID from tag paramaters
     *
     * Supports: entry_id="", url_title="", channel=""
     *
     * @return mixed - INT or BOOL
     */
    public function get_entry_id_from_param($get_channel_id=false)
    {
        $entry_id = false;
        $channel_id = false;

        ee()->load->helper('number');

        if (ee()->TMPL->fetch_param('entry_id') != false && \Image_helper::is_natural_number(ee()->TMPL->fetch_param('entry_id')) != false) {
            $entry_id = ee()->TMPL->fetch_param('entry_id');
        } elseif (ee()->TMPL->fetch_param('url_title') != false) {
            $channel = false;
            $channel_id = false;

            if (ee()->TMPL->fetch_param('channel') != false) {
                $channel = ee()->TMPL->fetch_param('channel');
            }

            if (ee()->TMPL->fetch_param('channel_id') != false && \Image_helper::is_natural_number(ee()->TMPL->fetch_param('channel_id'))) {
                $channel_id = ee()->TMPL->fetch_param('channel_id');
            }

            ee()->db->select('exp_channel_titles.entry_id');
            ee()->db->select('exp_channel_titles.channel_id');
            ee()->db->from('exp_channel_titles');
            if ($channel) {
                ee()->db->join('exp_channels', 'exp_channel_titles.channel_id = exp_channels.channel_id', 'left');
            }
            ee()->db->where('exp_channel_titles.url_title', ee()->TMPL->fetch_param('url_title'));
            if ($channel) {
                ee()->db->where('exp_channels.channel_name', $channel);
            }
            if ($channel_id) {
                ee()->db->where('exp_channel_titles.channel_id', $channel_id);
            }
            ee()->db->limit(1);
            $query = ee()->db->get();

            if ($query->num_rows() > 0) {
                $channel_id = $query->row('channel_id');
                $entry_id = $query->row('entry_id');
                $query->free_result();
            } else {
                return false;
            }
        }

        if ($get_channel_id != false) {
            if (ee()->TMPL->fetch_param('channel') != false) {
                $channel_id = ee()->TMPL->fetch_param('channel_id');
            }

            if ($channel_id == false) {
                ee()->db->select('channel_id');
                ee()->db->where('entry_id', $entry_id);
                ee()->db->limit(1);
                $query = ee()->db->get('exp_channel_titles');
                $channel_id = $query->row('channel_id');

                $query->free_result();
            }

            $entry_id = array( 'entry_id'=>$entry_id, 'channel_id'=>$channel_id );
        }



        return $entry_id;
    }

    /**
     * Get Channel ID from tag paramaters
     *
     * Supports: channel="", channel_id=""
     *
     * @return mixed - INT or BOOL
     */
    public function get_channel_id_from_param()
    {
        $channel_id = false;

        //----------------------------------------
        // Store them all
        //----------------------------------------
        $param_channel      = ee()->TMPL->fetch_param('channel');
        $param_channel_id   = ee()->TMPL->fetch_param('channel_id');

        //----------------------------------------
        // Channel ID?
        //----------------------------------------
        if (strpos($param_channel_id, '|') !== false) {
            $channel_id = array();
            $temp = explode('|', $param_channel_id);

            foreach ($temp as $item) {
                if (\Image_helper::is_natural_number($item) == false) {
                    continue;
                }
                $channel_id[] = $item;
            }

            return $channel_id;
        } else {
            if (\Image_helper::is_natural_number($param_channel_id) != false) {
                return $param_channel_id;
            }
        }


        //----------------------------------------
        // Grab all Channels!
        //----------------------------------------
        if ($param_channel != false) {
            $channels = array();

            // Maybe we did this already?
            if (isset($this->EE->session->cache['EEHarbor']['AllChannelsLight']) == false) {
                $query = ee()->db->query("SELECT channel_id, channel_name FROM exp_channels");
                foreach ($query->result() as $row) {
                    $channels[$row->channel_name] = $row->channel_id;
                }
                $this->EE->session->cache['EEHarbor']['AllChannelsLight'] = $channels;
            } else {
                $channels = $this->EE->session->cache['EEHarbor']['AllChannelsLight'];
            }
        } else {
            return false;
        }

        //----------------------------------------
        // Channel?
        //----------------------------------------
        if (strpos($param_channel, '|') !== false) {
            $channel_id = array();
            $temp = explode('|', $param_channel);

            foreach ($temp as $item) {
                if (isset($channels[$item]) == false) {
                    return false;
                }
                $channel_id[] = $channels[$item];
            }

            return $channel_id;
        } else {
            if (isset($channels[$param_channel]) == false) {
                return false;
            } else {
                return $channels[$param_channel];
            }
        }

        return $channel_id;
    }

    // ********************************************************************************* //

    /**
     * Fetch data between var pairs
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $source - Source
     * @return string
     */
    public function fetch_data_between_var_pairs($varname='', $source = '')
    {
        if (! preg_match('/'.LD.($varname).RD.'(.*?)'.LD.'\/'.$varname.RD.'/s', $source, $match)) {
            return;
        }

        return $match['1'];
    }

    // ********************************************************************************* //

    /**
     * Fetch data between var pairs (including optional parameters)
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $source - Source
     * @return string
     */
    public function fetch_data_between_var_pairs_params($open='', $close='', $source = '')
    {
        if (! preg_match('/'.LD.preg_quote($open).'.*?'.RD.'(.*?)'.LD.'\/'.$close.RD.'/s', $source, $match)) {
            return;
        }

        return $match['1'];
    }

    // ********************************************************************************* //

    /**
     * Replace var_pair with final value
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $replacement - Replacement
     * @param string $source - Source
     * @return string
     */
    public function swap_var_pairs($varname = '', $replacement = '\\1', $source = '')
    {
        return preg_replace("/".LD.$varname.RD."(.*?)".LD.'\/'.$varname.RD."/s", $replacement, $source);
    }

    // ********************************************************************************* //

    /**
     * Replace var_pair with final value (including optional parameters)
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $replacement - Replacement
     * @param string $source - Source
     * @return string
     */
    public function swap_var_pairs_params($open = '', $close = '', $replacement = '\\1', $source = '')
    {
        return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.'\/'.$close.RD."/s", $replacement, $source);
    }

    // ********************************************************************************* //

    /**
     * Custom No_Result conditional
     *
     * Same as {if no_result} but with your own conditional.
     *
     * @param string $cond_name
     * @param string $source
     * @param string $return_source
     * @return unknown
     */
    public function custom_no_results_conditional($cond_name, $source, $return_source=false)
    {
        if (strpos($source, LD."if {$cond_name}".RD) !== false) {
            if (preg_match('/'.LD."if {$cond_name}".RD.'(.*?)'. LD.'\/if'.RD.'/s', $source, $cond)) {
                return $cond[1];
            }
        }


        if ($return_source !== false) {
            return $source;
        }

        return;
    }
}

/* End of file Helper.php */
/* Location: ./system/user/addons/channel_images/Service/Helper.php */
