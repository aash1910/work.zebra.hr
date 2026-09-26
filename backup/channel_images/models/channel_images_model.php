<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * Channel Images Model File
 *
 * @package         EEHarbor_ChannelImages
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com
 */
class Channel_images_model
{
    public $LOCS = array();
    protected $site_id;
    protected $parse_iptc;
    protected $parse_exif;
    protected $parse_xmp;
    protected $IS_SSL;
    protected $total_images;
    protected $absolute_total_images;
    protected $image_position;
    protected $parse_filesize;
    protected $parse_dimensions;
    protected $parse_switch;
    protected $switch_matches;
    protected $encode_html;
    protected $decode_html;
    protected $locked_act_url;	
    protected $locked_url;
    protected $IP;

    private $flash_lookup = array(
        '0x0' => 'No Flash',
        '0x1' => 'Fired',
        '0x5' => 'Fired, Return not detected',
        '0x7' => 'Fired, Return detected',
        '0x8' => 'On, Did not fire',
        '0x9' => 'On, Fired',
        '0xd' => 'On, Return not detected',
        '0xf' => 'On, Return detected',
        '0x10' => 'Off, Did not fire',
        '0x14' => 'Off, Did not fire, Return not detected',
        '0x18' => 'Auto, Did not fire',
        '0x19' => 'Auto, Fired',
        '0x1d' => 'Auto, Fired, Return not detected',
        '0x1f' => 'Auto, Fired, Return detected',
        '0x20' => 'No flash function',
        '0x30' => 'Off, No flash function',
        '0x41' => 'Fired, Red-eye reduction',
        '0x45' => 'Fired, Red-eye reduction, Return not detected',
        '0x47' => 'Fired, Red-eye reduction, Return detected',
        '0x49' => 'On, Red-eye reduction',
        '0x4d' => 'On, Red-eye reduction, Return not detected',
        '0x4f' => 'On, Red-eye reduction, Return detected',
        '0x50' => 'Off, Red-eye reduction',
        '0x58' => 'Auto, Did not fire, Red-eye reduction',
        '0x59' => 'Auto, Fired, Red-eye reduction',
        '0x5d' => 'Auto, Fired, Red-eye reduction, Return not detected',
        '0x5f' => 'Auto, Fired, Red-eye reduction, Return detected',
    );

    private $orientation_lookup = array(
        1 => 'Horizontal (normal)',
        2 => 'Mirror horizontal',
        3 => 'Rotate 180',
        4 => 'Mirror vertical',
        5 => 'Mirror horizontal and rotate 270 CW',
        6 => 'Rotate 90 CW',
        7 => 'Mirror horizontal and rotate 90 CW',
        8 => 'Rotate 270 CW',
    );

    public $prefix = '';

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        // Creat EE Instance
        $this->site_id = ee()->config->item('site_id');
        //ee()->load->library('image_helper');
    }

    // ********************************************************************************* //

    public function get_images($entry_id = false, $field_id = false, $params = array(), $tagdata = '')
    {
        ee()->db->_reset_select();
        ee()->db->flush_cache();

        // Limit
        $limit = isset($params['limit']) ? $params['limit'] : 30;
        if (strpos($tagdata, LD . '/' . "{$this->prefix}paginate" . RD) === false) {
            ee()->db->limit($limit);
        }

        // Sort
        $sort = (isset($params['sort']) === true && $params['sort'] == 'desc') ? 'DESC' : 'ASC';

        // Order by? (only if primary_only is false, since this would override our orderby)
        if (isset($params['cover_only']) === false) {
            if (isset($params['orderby']) === false) {
                $params['orderby'] = 'image_order';
            }

            if ($params['orderby'] == 'title') {
                ee()->db->order_by('title', $sort);
            }
            if ($params['orderby'] == 'date') {
                ee()->db->order_by('upload_date', $sort);
            } elseif ($params['orderby'] == 'random') {
                ee()->db->order_by('RAND()', false);
            } else {
                ee()->db->order_by('image_order', $sort);
            }
        }

        // Category
        if (isset($params['category']) === true) {
            $cat = $params['category'];

            // Multiple Categories?
            if (strpos($cat, '|') !== false) {
                $cats = explode('|', $cat);
                foreach ($cats as &$cat) {
                    if ($cat == 'IS_EMPTY') {
                        $cat = '';
                    }
                }
                ee()->db->where_in('category', $cats);
            } else {
                if ($cat == 'IS_EMPTY') {
                    $cat = '';
                }
                ee()->db->where('category', $cat);
            }
        }

        // Exclude Category
        if (isset($params['exclude_category']) === true) {
            $cat = $params['exclude_category'];

            // Multiple Categories?
            if (strpos($cat, '|') !== false) {
                $cats = explode('|', $cat);
                foreach ($cats as &$cat) {
                    if ($cat == 'IS_EMPTY') {
                        $cat = '';
                    }
                }
                ee()->db->where_not_in('category', $cats);
            } else {
                if ($cat == 'IS_EMPTY') {
                    $cat = '';
                }

                ee()->db->where('category !=', $cat);
            }
        }

        // Field ID
        if ($field_id !== false) {
            if (is_array($field_id) === true) {
                ee()->db->where_in('field_id', $field_id);
            } else {
                ee()->db->where('field_id', $field_id);
            }
        }

        // Offset
        if (isset($params['offset']) === true) {
            ee()->db->limit($limit, $params['offset']);
        }

        // Do we need to skip the cover image?
        if (isset($params['skip_cover']) === true && $params['skip_cover'] != "no" && $params['skip_cover'] != "n" && $params['skip_cover'] != "false") {
            ee()->db->where('cover', 0);
        }

        // Cover Image
        /*To make templates backwards compatible we are checking for this in a bit of a goofy way.
        Prior to this we only checked if isset($params['cover_only']) == true.  So someone could have cover_only="no" and it would only show the cover which came into support.
        To keep as many templates backwards comp as possible we check for the no/n case*/
        if ( (isset($params['cover_only']) == true && $params['cover_only'] != "no" && $params['cover_only'] != "n" && $params['cover_only'] != "false") && (isset($params['force_cover']) === false or $params['force_cover'] != 'yes')) {
            ee()->db->limit(1);
            ee()->db->order_by('cover DESC, image_order ASC');
        } elseif ((isset($params['force_cover']) === true && $params['force_cover'] == 'yes')) {
            ee()->db->where('cover', 1);
        }

        // Image ID?
        if (isset($params['image_id']) === true) {
            $image_id = $params['image_id'];

            // Multiple File ID?
            if (strpos($image_id, '|') !== false) {
                $ids = explode('|', $image_id);
                ee()->db->where_in('image_id', $ids);
            } else {
                ee()->db->limit(1);
                ee()->db->where('image_id', $image_id);
            }
        }

        // URL Title
        if (isset($params['image_url_title']) === true) {
            ee()->db->limit(1);
            ee()->db->where('url_title', $params['image_url_title']);
        }

        // Entry ID
        if ($entry_id != false) {
            ee()->db->where('entry_id', $entry_id);
        }

        // Channel?
        if (isset($params['channel']) === true) {
            $cid = $this->get_channel_id($params['channel']);
            if (is_array($cid) === true) {
                ee()->db->where_in('channel_id', $cid);
            } else {
                ee()->db->where('channel_id', $cid);
            }
        }

        // Channel ID?
        if (isset($params['channel_id']) === true) {
            $channel_id = $params['channel_id'];

            // Multiple Channel ID?
            if (strpos($channel_id, '|') !== false) {
                $ids = explode('|', $channel_id);
                ee()->db->where_in('channel_id', $ids);
            } else {
                ee()->db->where('channel_id', $channel_id);
            }
        }

        // Member ID?
        if (isset($params['member_id']) === true) {
            $member_id = $params['member_id'];

            if ($member_id == 'CURRENT_USER') {
                ee()->db->where('member_id', ee()->session->userdata('member_id'));
            } elseif ($member_id != false) {
                // Multiple Authors?
                if (strpos($member_id, '|') !== false) {
                    $cols = explode('|', $member_id);
                    ee()->db->where_in('member_id', $cols);
                } else {
                    ee()->db->where('member_id', $member_id);
                }
            }
        }

        $is_draft = 0;

        if (isset(ee()->publisher_lib) === true && isset(ee()->publisher_lib->status) == -true) {
            if (ee()->publisher_lib->status == 'draft') {
                $is_draft = 1;
            }
        } elseif (isset($this->EE->session->cache['ep_better_workflow']['is_draft']) && $this->EE->session->cache['ep_better_workflow']['is_draft']) {
            $is_draft = 1;
        }

        ee()->db->where('is_draft', $is_draft);

        //----------------------------------------
        // Shoot the Query
        //----------------------------------------
        ee()->db->select('*');
        ee()->db->from('exp_channel_images');
        $query = ee()->db->get();

        $result = $query->result();
        $query->free_result();

        return $result;
    }

    // ********************************************************************************* //

    public function parse_template($entry_id = false, $field_id = false, $params = array(), $tagdata = '')
    {
        // Variable prefix
        $this->prefix = (isset($params['prefix']) === false) ? 'image:' : $params['prefix'] . ':';

        // Set a default value of false for the is_draft flag
        $is_draft = 0;

        // If we are loading a draft into the publish page update the flag to true
        if (ee()->session->cache('ep_better_workflow', 'is_draft')) {
            $is_draft = 1;
        }

        // Params can override this
        if (isset($params['is_draft']) === true) {
            if ($params['is_draft'] == 'yes') {
                $is_draft = 1;
            } elseif ($params['is_draft'] == 'no') {
                $is_draft = 0;
            }
        }

        $temp_params = $params;

        // Lets remove all unwanted params
        unset($temp_params['entry_id'], $temp_params['url_title']);

        // Make our hash
        $hash = crc32(serialize($temp_params));
        if (!empty(ee()->session->cache('channel_images', 'images/' . $hash . '/' . $entry_id))) {
            $images = ee()->session->cache('channel_images', 'images/' . $hash . '/' . $entry_id);
        } else {
            $images = $this->get_images($entry_id, $field_id, $params, $tagdata);
        }

        // Any Images?
        if (count($images) === 0) {
            ee()->TMPL->log_item("CHANNEL IMAGES: No images found.");
            return ee('channel_images:Helper')->custom_no_results_conditional($this->prefix . 'no_images', $tagdata);
        }

        $this->total_images = count($images);
        $this->absolute_total_images = count($images);
        $limit = isset($params['limit']) ? $params['limit'] : 30;
        $paginate = false;

        $this->image_position = array();

        // Loop over all images and store it's position of all images
        foreach ($images as $pos => $img) {
            $this->image_position[$img->image_id] = $pos + 1;
        }

        //----------------------------------------
        // Pagination
        //----------------------------------------
        if (preg_match('/' . LD . "{$this->prefix}paginate(.*?)" . RD . "(.+?)" . LD . '\/' . "{$this->prefix}paginate" . RD . "/s", $tagdata, $match)) {
            // Pagination variables
            $paginate       = true;
            $paginate_data  = $match['2'];
            $current_page   = 0;
            $total_pages    = 1;
            $qstring        = ee()->uri->query_string;
            $uristr         = ee()->uri->uri_string;
            $pagination_links = '';
            $page_previous = '';
            $page_next = '';

            // We need to strip the page number from the URL for two reasons:
            // 1. So we can create pagination links
            // 2. So it won't confuse the query with an improper proper ID

            if (preg_match("#(^|/)CI(\d+)(/|$)#", $qstring, $match)) {
                $current_page = $match['2'];

                if (function_exists('reduce_double_slashes')) {
                    $uristr  = reduce_double_slashes(str_replace($match['0'], '/', $uristr));
                    $qstring = trim(reduce_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
                } else {
                    $uristr  = ee()->functions->remove_double_slashes(str_replace($match['0'], '/', $uristr));
                    $qstring = trim(ee()->functions->remove_double_slashes(str_replace($match['0'], '/', $qstring)), '/');
                }
            }

            // Remove the {paginate}
            $tagdata = preg_replace("/" . LD . "{$this->prefix}paginate.*?" . RD . ".+?" . LD . '\/' . "{$this->prefix}paginate" . RD . "/s", "", $tagdata);

            // What is the current page?

            $current_page = ($current_page == '' or ($limit > 1 and $current_page == 1)) ? 0 : $current_page;

            if ($current_page > $this->total_images) {
                $current_page = 0;
            }

            $t_current_page = floor(($current_page / $limit) + 1);
            $total_pages    = intval(floor($this->total_images / $limit));

            if ($this->total_images % $limit) {
                $total_pages++;
            }

            if ($this->total_images > $limit) {
                ee()->load->library('pagination');

                $deft_tmpl = '';

                if ($uristr == '') {
                    if (ee()->config->item('template_group') == '') {
                        ee()->db->select('group_name');
                        $query = ee()->db->get_where('template_groups', array('is_site_default' => 'y'));

                        $deft_tmpl = $query->row('group_name') . '/index';
                    } else {
                        $deft_tmpl  = ee()->config->item('template_group') . '/';
                        $deft_tmpl .= (ee()->config->item('template') == '') ? 'index' : ee()->config->item('template');
                    }
                }

                if (function_exists('reduce_double_slashes')) {
                    $basepath = reduce_double_slashes(ee()->functions->create_url($uristr, false) . '/' . $deft_tmpl);
                } else {
                    $basepath = ee()->functions->remove_double_slashes(ee()->functions->create_url($uristr, false) . '/' . $deft_tmpl);
                }


                if (isset($params['paginate_base']) === true) {
                    // Load the string helper
                    ee()->load->helper('string');

                    $pbase = trim_slashes($params['paginate_base']);

                    $pbase = str_replace("/index", "/", $pbase);

                    if (!strstr($basepath, $pbase)) {
                        if (function_exists('reduce_double_slashes')) {
                            $basepath = reduce_double_slashes($basepath . '/' . $pbase);
                        } else {
                            $basepath = ee()->functions->remove_double_slashes($basepath . '/' . $pbase);
                        }
                    }
                }

                // Load Language
                ee()->lang->loadfile('channel_images');

                $config['first_url']    = rtrim($basepath, '/');
                $config['base_url']     = $basepath;
                $config['prefix']       = 'CI';
                $config['total_rows']   = $this->total_images;
                $config['per_page']     = $limit;
                $config['cur_page']     = $current_page;
                $config['suffix']       = '';
                $config['first_link']   = ee()->lang->line('ci:pag_first_link');
                $config['last_link']    = ee()->lang->line('ci:pag_last_link');
                $config['full_tag_open']        = '<span class="ci_paginate_links">';
                $config['full_tag_close']       = '</span>';
                $config['first_tag_open']       = '<span class="ci_paginate_first">';
                $config['first_tag_close']      = '</span>&nbsp;';
                $config['last_tag_open']        = '&nbsp;<span class="ci_paginate_last">';
                $config['last_tag_close']       = '</span>';
                $config['cur_tag_open']         = '&nbsp;<strong class="ci_paginate_current">';
                $config['cur_tag_close']        = '</strong>';
                $config['next_tag_open']        = '&nbsp;<span class="ci_paginate_next">';
                $config['next_tag_close']       = '</span>';
                $config['prev_tag_open']        = '&nbsp;<span class="ci_paginate_prev">';
                $config['prev_tag_close']       = '</span>';
                $config['num_tag_open']         = '&nbsp;<span class="ci_paginate_num">';
                $config['num_tag_close']        = '</span>';

                // Allows $config['cur_page'] to override
                $config['uri_segment'] = 0;

                ee()->pagination->initialize($config);
                $pagination_links = ee()->pagination->create_links();

                if ((($total_pages * $limit) - $limit) > $current_page) {
                    $page_next = $basepath . $config['prefix'] . ($current_page + $limit) . '/';
                }

                if (($current_page - $limit) >= 0) {
                    $page_previous = $basepath . $config['prefix'] . ($current_page - $limit) . '/';
                }
            } else {
                $current_page = 0;
            }

            $images = array_slice($images, $current_page, $limit);
            $this->total_images = count($images);
        }

        //----------------------------------------
        // Check for filesize (only for Local) Since it's an expensive operation
        //----------------------------------------
        $this->parse_filesize = false;
        if (strpos($tagdata, LD . $this->prefix . 'filesize') !== false) {
            $this->parse_filesize = true;
        }

        //----------------------------------------
        // Check for image_dimensions (only for Local) Since it's an expensive operation
        //----------------------------------------
        $this->parse_dimensions = false;
        if (strpos($tagdata, LD . $this->prefix . 'width') !== false or strpos($tagdata, LD . $this->prefix . 'height') !== false) {
            $this->parse_dimensions = true;
        }

        //----------------------------------------
        // Switch=""
        //----------------------------------------
        $this->parse_switch = false;
        $this->switch_matches = array();
        if (preg_match_all("/" . LD . "({$this->prefix}switch\s*=.+?)" . RD . "/is", $tagdata, $this->switch_matches) > 0) {
            $this->parse_switch = true;

            // Loop over all matches
            foreach ($this->switch_matches[0] as $key => $match) {
                $this->switch_vars[$key] = ee('Variables/Parser')->parseTagParameters($this->switch_matches[1][$key]); //ee()->functions->assign_parameters($this->switch_matches[1][$key]);
                $this->switch_vars[$key]['original'] = $this->switch_matches[0][$key];
            }
        }

        // Encode HTML Entities
        $this->encode_html = false;
        if (isset($params['encode_html_entities']) === true && $params['encode_html_entities'] == 'yes') {
            $this->encode_html = true;
        }

        // Decode HTML Entities
        $this->decode_html = false;
        if (isset($params['decode_html_entities']) === true && $params['decode_html_entities'] == 'yes') {
            $this->decode_html = true;
        }

        //----------------------------------------
        // Locked URL?
        //----------------------------------------
        $this->locked_url = false;
        if (strpos($tagdata, $this->prefix . 'locked_url') !== false) {
            $this->locked_url = true;

            // IP
            $this->IP = ee()->input->ip_address();

            // Grab Router URL
            $this->locked_act_url = ee('channel_images:Helper')->getRouterUrl('url', 'locked_image_url');
        }

        //----------------------------------------
        // IPTC?
        //----------------------------------------
        $this->parse_iptc = false;
        if (strpos($tagdata, $this->prefix . 'iptc') !== false) {
            $this->parse_iptc = true;
        }

        //----------------------------------------
        // EXIF
        //----------------------------------------
        $this->parse_exif = false;
        if (strpos($tagdata, $this->prefix . 'exif') !== false) {
            $this->parse_exif = true;
        }

        //----------------------------------------
        // XMP
        //----------------------------------------
        $this->parse_xmp = false;
        if (strpos($tagdata, $this->prefix . 'xmp') !== false) {
            $this->parse_xmp = true;
        }

        // SSL?
        $this->IS_SSL = ee('channel_images:Helper')->isSsl();

        $this->LOCS = array();

        //----------------------------------------
        // Performance :)
        //----------------------------------------
        if (!empty(ee()->session->cache('channel_images', 'locations'))) {
            $this->LOCS = ee()->session->cache('channel_images', 'locations');
        }

        $OUT = '';

        //----------------------------------------
        // Loop over all Images
        //----------------------------------------

        foreach ($images as $count => $image) {
            $OUT .= $this->parse_single_image_row($count, $image, $tagdata);
        }

        //----------------------------------------
        // Add pagination to result
        //----------------------------------------
        if ($paginate == true) {
            $paginate_data = str_replace(LD . $this->prefix . 'current_page' . RD, $t_current_page, $paginate_data);
            $paginate_data = str_replace(LD . $this->prefix . 'total_pages' . RD, $total_pages, $paginate_data);
            $paginate_data = str_replace(LD . $this->prefix . 'pagination_links' . RD, $pagination_links, $paginate_data);

            if (preg_match("/" . LD . "if {$this->prefix}previous_page" . RD . "(.+?)" . LD . '\/' . "if" . RD . "/s", $paginate_data, $match)) {
                if ($page_previous == '') {
                    $paginate_data = preg_replace("/" . LD . "if {$this->prefix}previous_page" . RD . ".+?" . LD . '\/' . "if" . RD . "/s", '', $paginate_data);
                } else {
                    $match['1'] = str_replace(array(LD . "{$this->prefix}path" . RD, LD . "{$this->prefix}auto_path" . RD), $page_previous, $match['1']);

                    $paginate_data = str_replace($match['0'], $match['1'], $paginate_data);
                }
            }

            if (preg_match("/" . LD . "if {$this->prefix}next_page" . RD . "(.+?)" . LD . '\/' . "if" . RD . "/s", $paginate_data, $match)) {
                if ($page_next == '') {
                    $paginate_data = preg_replace("/" . LD . "if {$this->prefix}next_page" . RD . ".+?" . LD . '\/' . "if" . RD . "/s", '', $paginate_data);
                } else {
                    $match['1'] = str_replace(array(LD . "{$this->prefix}path" . RD, LD . "{$this->prefix}auto_path" . RD), $page_next, $match['1']);

                    $paginate_data = str_replace($match['0'], $match['1'], $paginate_data);
                }
            }

            $position = (isset($params['paginate']) === true) ? $params['paginate'] : '';

            switch ($position) {
                case "top":
                    $OUT  = $paginate_data . $OUT;
                    break;
                case "both":
                    $OUT  = $paginate_data . $OUT . $paginate_data;
                    break;
                default:
                    $OUT .= $paginate_data;
                    break;
            }
        }

        // Apply Backspace
        $backspace = (isset($params['backspace']) === true) ? $params['backspace'] : 0;
        $OUT = ($backspace > 0) ? substr($OUT, 0, -$backspace) : $OUT;

        return $OUT;
    }

    // ********************************************************************************* //

    public function parse_single_image_row($count, $image, $tagdata)
    {
        $out = '';
        // Check for linked image!
        if ($image->link_entry_id > 0) {
            $image->entry_id = $image->link_entry_id;
            $image->field_id = $image->link_field_id;
        }

        // Get Field Settings!
        $settings = $this->get_field_settings($image->field_id);
        if (isset($settings['settings']) == true) {
            $settings = array_merge($settings, $settings['settings']);
        }
        $settings = $settings['channel_images'];

        //----------------------------------------
        // Load Location
        //----------------------------------------
        if (isset($this->LOCS[$image->field_id]) === false) {
            $location_type = $settings['upload_location'];
            $location_class = 'CI_Location_'.$location_type;
            $location_settings = $settings['locations'][$location_type];

            // Load Main Class
            if (class_exists('Image_Location') == false) {
                require PATH_THIRD . 'channel_images/locations/image_location.php';
            }

            // Try to load Location Class
            if (class_exists($location_class) == false) {
                $location_file = PATH_THIRD . 'channel_images/locations/' . $location_type . '/' . $location_type . '.php';
                require $location_file;
            }

            // Init!
            $this->LOCS[$image->field_id] = new $location_class($location_settings);
        }


        //----------------------------------------
        // Check for Mime Type
        //----------------------------------------
        if ($image->mime == false) {
            // Mime type
            $image->mime = 'image/jpeg';
            if ($image->extension == 'png') {
                $filemime = 'image/png';
            } elseif ($image->extension == 'gif') {
                $filemime = 'image/gif';
            } elseif ($image->extension == 'webp') {
                $filemime = 'image/webp';
            }
        }

        //----------------------------------------
        // Image URL
        //----------------------------------------
        $image_url = $this->LOCS[$image->field_id]->parse_image_url($image->entry_id, $image->filename);

        // Did something go wrong?
        if ($image_url == false) {
            ee()->TMPL->log_item('CHANNEL IMAGES: Image URL Failed for: ' . $image->entry_id . '/' . $image->filename);
            return '';
        }

        // SSL?
        if ($this->IS_SSL == true) {
            $image_url = str_replace('http://', 'https://', $image_url);
        }

        //----------------------------------------
        // Filedir (local only)
        //----------------------------------------
        $filedir = '';
        if ($settings['upload_location'] == 'local') {
            $filedir = str_replace($image->entry_id . '/' . $image->filename, '', $image_url);
        }

        //----------------------------------------
        // Encode/Decode fields
        //----------------------------------------
        if ($this->encode_html) {
            $image->title = htmlentities($image->title, ENT_QUOTES, 'UTF-8');
            $image->description = htmlentities($image->description, ENT_QUOTES, 'UTF-8');
            $image->cifield_1 = htmlentities($image->cifield_1, ENT_QUOTES, 'UTF-8');
            $image->cifield_2 = htmlentities($image->cifield_2, ENT_QUOTES, 'UTF-8');
            $image->cifield_3 = htmlentities($image->cifield_3, ENT_QUOTES, 'UTF-8');
            $image->cifield_4 = htmlentities($image->cifield_4, ENT_QUOTES, 'UTF-8');
            $image->cifield_5 = htmlentities($image->cifield_5, ENT_QUOTES, 'UTF-8');
        }

        if ($this->decode_html) {
            $image->title = html_entity_decode($image->title, ENT_QUOTES, 'UTF-8');
            $image->description = html_entity_decode($image->description, ENT_QUOTES, 'UTF-8');
            $image->cifield_1 = html_entity_decode($image->cifield_1, ENT_QUOTES, 'UTF-8');
            $image->cifield_2 = html_entity_decode($image->cifield_2, ENT_QUOTES, 'UTF-8');
            $image->cifield_3 = html_entity_decode($image->cifield_3, ENT_QUOTES, 'UTF-8');
            $image->cifield_4 = html_entity_decode($image->cifield_4, ENT_QUOTES, 'UTF-8');
            $image->cifield_5 = html_entity_decode($image->cifield_5, ENT_QUOTES, 'UTF-8');
        }

        $vars = array();
        $vars[$this->prefix . 'count'] = $count + 1;
        $vars[$this->prefix . 'index'] = $count; // Zero based count
        $vars[$this->prefix . 'absolute_count'] = $this->image_position[$image->image_id];
        $vars[$this->prefix . 'total'] = $this->total_images;
        $vars[$this->prefix . 'absolute_total'] = $this->absolute_total_images;
        $vars[$this->prefix . 'entry_id'] = $image->entry_id;
        $vars[$this->prefix . 'channel_id'] = $image->channel_id;
        $vars[$this->prefix . 'title'] = $image->title;
        $vars[$this->prefix . 'url_title'] = $image->url_title;
        $vars[$this->prefix . 'description'] = $image->description;
        $vars[$this->prefix . 'category'] = $image->category;
        $vars[$this->prefix . 'filename'] = $image->filename;
        $vars[$this->prefix . 'id'] = $image->image_id;
        $vars[$this->prefix . 'member_id'] = $image->member_id;
        $vars[$this->prefix . 'cover'] = $image->cover;
        $vars[$this->prefix . 'upload_date'] = $image->upload_date;
        $vars[$this->prefix . 'url'] = $image_url;
        $vars[$this->prefix . 'secure_url'] = str_replace('http://', 'https://', $image_url);
        $vars[$this->prefix . 'file_path'] = $filedir;
        $vars[$this->prefix . 'file_path_secure'] = str_replace('http://', 'https://', $filedir);
        $vars[$this->prefix . 'mimetype'] = $image->mime;
        $vars[$this->prefix . 'field:1'] = $image->cifield_1;
        $vars[$this->prefix . 'field:2'] = $image->cifield_2;
        $vars[$this->prefix . 'field:3'] = $image->cifield_3;
        $vars[$this->prefix . 'field:4'] = $image->cifield_4;
        $vars[$this->prefix . 'field:5'] = $image->cifield_5;

        //----------------------------------------
        // Check for filesize, Since it's an expensive operation
        //----------------------------------------
        if ($this->parse_filesize == true) {
            // If filesize is not defined, lets find it (only for local files)
            if ($image->filesize == false && $settings['upload_location'] == 'local') {
                $filepath = $this->LOCS[$image->field_id]->getLocationPrefs($settings['locations']['local']['location']);
                $filepath = $filepath->server_path  . $image->entry_id . '/' . $image->filename;
                $image->filesize = @filesize($filepath);
            } elseif ($image->filesize == false) {
                $image->filesize = 0;
            }

            $vars[$this->prefix . 'filesize'] = ee('channel_images:Helper')->formatBytes($image->filesize);
            $vars[$this->prefix . 'filesize_bytes'] = $image->filesize;
        }

        //----------------------------------------
        // Check for image_dimensions, Since it's an expensive operation
        //----------------------------------------
        if ($this->parse_dimensions == true) {
            // If filesize is not defined, lets find it (only for local files)
            if ($image->width == false && $settings['upload_location'] == 'local') {
                $filepath = $this->LOCS[$image->field_id]->getLocationPrefs($settings['locations']['local']['location']);
                $filepath = $filepath->server_path  . $image->entry_id . '/' . $image->filename;
                $imginfo = @getimagesize($filepath);
                $image->width = $imginfo[0];
                $image->height = $imginfo[1];
            } elseif ($image->width == false) {
                $image->width = '';
                $image->height = '';
            }

            $vars[$this->prefix . 'width'] = $image->width;
            $vars[$this->prefix . 'height'] = $image->height;
        }



        // -----------------------------------------
        // Locked URL
        // -----------------------------------------
        if ($this->locked_url == true) {
            $locked = array('image_id' => $image->image_id, 'size' => '', 'time' => ee()->localize->now + 600, 'ip' => $this->IP);
            $vars[$this->prefix . 'locked_url'] = $this->locked_act_url . '&key=' . base64_encode(serialize($locked));
        }

        // -----------------------------------------
        // IPTC!
        // -----------------------------------------
        if ($this->parse_iptc == true) {
            // http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/IPTC.html
            // http://phpgraphy.sourceforge.net/manual/latest/apas04.html
            $iptc = @unserialize(base64_decode($image->iptc));

            $vars = $this->parseIptc($vars, $iptc);
        }

        // -----------------------------------------
        // EXIF
        // -----------------------------------------
        if ($this->parse_exif == true) {
            // http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/EXIF.html
            // http://www.exiv2.org/tags.html
            // https://github.com/jedd/phoko/blob/master/app/config/phoko.php
            $exif = @unserialize(base64_decode($image->exif));
            //var_dump($exif);

            $vars = $this->parseExif($vars, $exif);
        }


        // -----------------------------------------
        // XMP
        // -----------------------------------------
        if ($this->parse_xmp == true) {
            // http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/XMP.html
            $xmp = @base64_decode($image->xmp);
            $xmp = $this->XMP2array($xmp);

            $vars = $this->parseXmp($vars, $xmp);
        }

        /* Coilpack Twig needs the images passed like this:
        [
          ['count' => '', 'index' => '', etc...] 
        ]
        where even a single image needs to itself be in an empty array.  So if we are the only image we have to do it this way.
        */

        if (method_exists(ee()->TMPL, 'set_data')) {
            ee()->TMPL->set_data(array_merge(ee()->TMPL->get_data() ?? [], [$vars]));
        }


        $temp = ee()->TMPL->parse_variables_row($tagdata, $vars);
        $temp = $this->parse_size_vars($temp, $settings, $image);

        // -----------------------------------------
        // Parse Switch {switch="one|twoo"}
        // -----------------------------------------
        if ($this->parse_switch) {
            // Loop over all switch variables
            foreach ($this->switch_vars as $switch) {
                $sw = '';

                // Does it exist? Just to be sure
                if (isset($switch[$this->prefix . 'switch']) !== false) {
                    $sopt = explode("|", $switch[$this->prefix . 'switch']);
                    $sw = $sopt[(($count) + count($sopt)) % count($sopt)];
                }

                $temp = str_replace($switch['original'], $sw, $temp);
            }
        }



        return $temp;
    }

    // ********************************************************************************* //

    public function parseExif($vars, $exif)
    {
        $vars[$this->prefix . 'exif:make'] = (isset($exif['Make'])) ? $exif['Make'] : '';
        $vars[$this->prefix . 'exif:model'] = (isset($exif['Model'])) ? $exif['Model'] : '';
        $vars[$this->prefix . 'exif:software'] = (isset($exif['Software'])) ? $exif['Software'] : '';
        $vars[$this->prefix . 'exif:image_description'] = (isset($exif['ImageDescription'])) ? $exif['ImageDescription'] : '';

        $vars[$this->prefix . 'exif:datetime_original'] = (isset($exif['DateTimeOriginal'])) ? $exif['DateTimeOriginal'] : '';
        $vars[$this->prefix . 'exif:flash'] = (isset($exif['Flash'])) ? @$this->flash_lookup['0x' . dechex((int)$exif['Flash'])] : '';
        $vars[$this->prefix . 'exif:orientation'] = (isset($exif['Orientation'])) ? @$this->orientation_lookup[$exif['Orientation']] : '';
        $vars[$this->prefix . 'exif:artist'] = (isset($exif['Artist'])) ? $exif['Artist'] : '';
        $vars[$this->prefix . 'exif:copyright'] = (isset($exif['Copyright'])) ? $exif['Copyright'] : '';
        $vars[$this->prefix . 'exif:exposure_time'] = (isset($exif['ExposureTime'])) ? $exif['ExposureTime'] . ' sec' : '';

        // Date
        $vars[$this->prefix . 'exif:date'] = '';
        if ($vars[$this->prefix . 'exif:datetime_original'] != false) {
            $pieces = explode(':', $vars[$this->prefix . 'exif:datetime_original']);
            $pieces = $pieces[0] . '-' . $pieces[1] . '-' . $pieces[2] . ':' . $pieces[3] . ':' . $pieces[4];
            $vars[$this->prefix . 'exif:date'] = strtotime($pieces);
        }

        // Focal Length
        $vars[$this->prefix . 'exif:focal_length'] = (isset($exif['FocalLength'])) ? $exif['FocalLength'] : '';
        if ($vars[$this->prefix . 'exif:focal_length'] != false) {
            $fraction = trim((string)($vars[$this->prefix . 'exif:focal_length']));
            // This method is slightly faster than using a preg function
            $slash_pos = strpos($fraction, '/');
            if ($slash_pos !== false) {
                $dividend = substr($fraction, 0, ($slash_pos));
                $divisor = substr($fraction, ($slash_pos + 1));
                $vars[$this->prefix . 'exif:focal_length'] = floor($dividend / $divisor) . ' mm';
            } else {
                // No slash means it's .. too hard to work out.
                $vars[$this->prefix . 'exif:focal_length'] = $fraction . ' mm';
            }
        }

        // FNumber
        $vars[$this->prefix . 'exif:fnumber'] = (isset($exif['FNumber'])) ? $exif['FNumber'] : '';
        if ($vars[$this->prefix . 'exif:fnumber'] != false) {
            $fraction = trim((string)($vars[$this->prefix . 'exif:fnumber']));
            // This method is slightly faster than using a preg function
            $slash_pos = strpos($fraction, '/');
            if ($slash_pos !== false) {
                $dividend = substr($fraction, 0, ($slash_pos));
                $divisor = substr($fraction, ($slash_pos + 1));
                $vars[$this->prefix . 'exif:fnumber'] = '&fnof;/' . floor($dividend / $divisor);
            } else {
                // No slash means it's .. too hard to work out.
                $vars[$this->prefix . 'exif:fnumber'] = '&fnof;/' . $fraction;
            }
        }

        // ISO
        $vars[$this->prefix . 'exif:iso'] = '';
        if (isset($exif['ISO'])) {
            $vars[$this->prefix . 'exif:iso'] = $exif['ISO'];
        }
        if (isset($exif['ISOSpeedRatings'])) {
            $vars[$this->prefix . 'exif:iso'] = $exif['ISOSpeedRatings'];
        }
        if (isset($exif['PhotographicSensitivity'])) {
            $vars[$this->prefix . 'exif:iso'] = $exif['PhotographicSensitivity'];
        }


        // GPS
        $vars[$this->prefix . 'exif:gps_lat'] = (isset($exif['GPSLatitude']) === true && empty($exif['GPSLatitude']) === false) ? $this->getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']) : '';
        $vars[$this->prefix . 'exif:gps_lon'] = (isset($exif['GPSLongitude']) === true && empty($exif['GPSLongitude']) === false) ? $this->getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']) : '';

        // GPS Altitude
        $vars[$this->prefix . 'exif:gps_alt'] = '';
        if (isset($exif['GPSAltitude']) === true) {
            list($num, $denom) = explode('/', $exif['GPSAltitude']);
            $exif['GPSAltitude'] = $num / $denom;

            if ($exif['GPSAltitudeRef'] === "\1") {
                $exif['GPSAltitude'] *= -1;
            }

            $vars[$this->prefix . 'exif:gps_alt'] = number_format($exif['GPSAltitude'], 2) . 'm';
        }

        return $vars;
    }

    // ********************************************************************************* //

    public function parseXmp($vars, $xmp)
    {
        if ($xmp == false) {
            return $vars;
        }

        $vars[$this->prefix . 'xmp:creator_email'] = (isset($xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiEmailWork'])) ? $xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiEmailWork'] : '';
        $vars[$this->prefix . 'xmp:creator_tel'] = (isset($xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiTelWork'])) ? $xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiTelWork'] : '';
        $vars[$this->prefix . 'xmp:creator_url'] = (isset($xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiUrlWork'])) ? $xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiUrlWork'] : '';

        $vars[$this->prefix . 'xmp:creator_address'] = (isset($xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiAdrExtadr'])) ? $xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiAdrExtadr'] : '';
        $vars[$this->prefix . 'xmp:creator_city'] = (isset($xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiAdrCity'])) ? $xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiAdrCity'] : '';
        $vars[$this->prefix . 'xmp:creator_zip'] = (isset($xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiAdrPcode'])) ? $xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiAdrPcode'] : '';
        $vars[$this->prefix . 'xmp:creator_region'] = (isset($xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiAdrRegion'])) ? $xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiAdrRegion'] : '';
        $vars[$this->prefix . 'xmp:creator_country'] = (isset($xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiAdrCtry'])) ? $xmp['Iptc4xmpCore:CreatorContactInfo']['Iptc4xmpCore:CiAdrCtry'] : '';

        $vars[$this->prefix . 'xmp:usage_terms'] = (isset($xmp['xapRights:UsageTerms'])) ? reset($xmp['xapRights:UsageTerms']) : '';

        $vars[$this->prefix . 'xmp:author'] = (isset($xmp['dc:creator'])) ? reset($xmp['dc:creator']) : '';
        $vars[$this->prefix . 'xmp:description'] = (isset($xmp['dc:description'])) ? reset($xmp['dc:description']) : '';
        $vars[$this->prefix . 'xmp:rights'] = (isset($xmp['dc:rights'])) ? reset($xmp['dc:rights']) : '';
        $vars[$this->prefix . 'xmp:title'] = (isset($xmp['dc:title'])) ? reset($xmp['dc:title']) : '';

        $vars[$this->prefix . 'xmp:source'] = (isset($xmp['rdf:Description']['Iptc4xmpExt:AOSource'])) ? $xmp['rdf:Description']['Iptc4xmpExt:AOSource'] : '';
        $vars[$this->prefix . 'xmp:copyright_notice'] = (isset($xmp['rdf:Description']['Iptc4xmpExt:AOCopyrightNotice'])) ? $xmp['rdf:Description']['Iptc4xmpExt:AOCopyrightNotice'] : '';

        /*
        $xmp = "<?xml version='1.0'?>\n" . $xmp;
        //echo $xmp;
        $xmp = @simplexml_load_string($xmp);


        if ($xmp !== FALSE)
        {
            $namespaces = $xmp->getNamespaces(true);
            foreach ($namespaces as $key => $val) {
                //var_dump($key.' '.$val);
                $xmp->registerXPathNamespace($key, $val);
            }

            print_r($xmp->xpath('//rdf/Iptc4xmpCore'));
        }*/


        //;
        //$vars[$this->prefix.'exif:model'] = (isset($exif['Model'])) ? $exif['Model'] : '';

        return $vars;
    }

    // ********************************************************************************* //

    public function parseIptc($vars, $iptc)
    {
        $vars[$this->prefix . 'iptc:object_name'] = (isset($iptc['2#005'][0])) ? $iptc['2#005'][0] : '';
        $vars[$this->prefix . 'iptc:keywords'] = (isset($iptc['2#025'][0])) ? implode(', ', $iptc['2#025']) : '';
        $vars[$this->prefix . 'iptc:special_instructions'] = (isset($iptc['2#040'][0])) ? $iptc['2#040'][0] : '';
        $vars[$this->prefix . 'iptc:date_created'] = (isset($iptc['2#055'][0])) ? $iptc['2#055'][0] : '';
        $vars[$this->prefix . 'iptc:time_created'] = (isset($iptc['2#060'][0])) ? $iptc['2#060'][0] : '';

        $vars[$this->prefix . 'iptc:byline'] = (isset($iptc['2#080'][0])) ? $iptc['2#080'][0] : '';
        $vars[$this->prefix . 'iptc:byline_title'] = (isset($iptc['2#085'][0])) ? $iptc['2#085'][0] : '';
        $vars[$this->prefix . 'iptc:city'] = (isset($iptc['2#090'][0])) ? $iptc['2#090'][0] : '';
        $vars[$this->prefix . 'iptc:sub_location'] = (isset($iptc['2#092'][0])) ? $iptc['2#092'][0] : '';
        $vars[$this->prefix . 'iptc:province_state'] = (isset($iptc['2#095'][0])) ? $iptc['2#095'][0] : '';
        $vars[$this->prefix . 'iptc:country_name'] = (isset($iptc['2#101'][0])) ? $iptc['2#101'][0] : '';
        $vars[$this->prefix . 'iptc:original_transmission_reference'] = (isset($iptc['2#103'][0])) ? $iptc['2#103'][0] : '';
        $vars[$this->prefix . 'iptc:headline'] = (isset($iptc['2#105'][0])) ? $iptc['2#105'][0] : '';
        $vars[$this->prefix . 'iptc:credit'] = (isset($iptc['2#110'][0])) ? utf8_encode($iptc['2#110'][0]) : '';
        $vars[$this->prefix . 'iptc:source'] = (isset($iptc['2#115'][0])) ? utf8_encode($iptc['2#115'][0]) : '';
        $vars[$this->prefix . 'iptc:copyright_notice'] = (isset($iptc['2#116'][0])) ? utf8_encode($iptc['2#116'][0]) : '';
        $vars[$this->prefix . 'iptc:caption_abstract'] = (isset($iptc['2#120'][0])) ? $iptc['2#120'][0] : '';
        $vars[$this->prefix . 'iptc:writer_editor'] = (isset($iptc['2#122'][0])) ? $iptc['2#122'][0] : '';

        $vars[$this->prefix . 'iptc:title'] = $vars[$this->prefix . 'iptc:object_name'];
        $vars[$this->prefix . 'iptc:author'] = $vars[$this->prefix . 'iptc:byline'];
        $vars[$this->prefix . 'iptc:author_title'] = $vars[$this->prefix . 'iptc:byline_title'];
        $vars[$this->prefix . 'iptc:state'] = $vars[$this->prefix . 'iptc:province_state'];
        $vars[$this->prefix . 'iptc:location'] = $vars[$this->prefix . 'iptc:sub_location'];
        $vars[$this->prefix . 'iptc:country'] = $vars[$this->prefix . 'iptc:country_name'];
        $vars[$this->prefix . 'iptc:otr'] = $vars[$this->prefix . 'iptc:original_transmission_reference'];
        $vars[$this->prefix . 'iptc:copyright'] = $vars[$this->prefix . 'iptc:copyright_notice'];
        $vars[$this->prefix . 'iptc:description'] = $vars[$this->prefix . 'iptc:caption_abstract'];
        $vars[$this->prefix . 'iptc:caption'] = $vars[$this->prefix . 'iptc:caption_abstract'];
        $vars[$this->prefix . 'iptc:caption_author'] = $vars[$this->prefix . 'iptc:writer_editor'];

        // Parse Date!
        $vars[$this->prefix . 'iptc:date'] = '';
        if ($vars[$this->prefix . 'iptc:date_created'] != false && $vars[$this->prefix . 'iptc:time_created'] != false) {
            $vars[$this->prefix . 'iptc:date'] = mktime(
                substr($vars[$this->prefix . 'iptc:time_created'], 0, 2),
                substr($vars[$this->prefix . 'iptc:time_created'], 2, 2),
                substr($vars[$this->prefix . 'iptc:time_created'], 4, 2),
                substr($vars[$this->prefix . 'iptc:date_created'], 4, 2),
                substr($vars[$this->prefix . 'iptc:date_created'], 6, 2),
                substr($vars[$this->prefix . 'iptc:date_created'], 0, 4)
            );
        }

        if ($vars[$this->prefix . 'iptc:date_created'] != false && $vars[$this->prefix . 'iptc:time_created'] == false) {
            $vars[$this->prefix . 'iptc:date'] = mktime(
                12,
                12,
                12,
                substr($vars[$this->prefix . 'iptc:date_created'], 4, 2),
                substr($vars[$this->prefix . 'iptc:date_created'], 6, 2),
                substr($vars[$this->prefix . 'iptc:date_created'], 0, 4)
            );
        }

        return $vars;
    }

    // ********************************************************************************* //

    public function parse_size_vars($OUT, $settings, $image)
    {
        // Get Extension
        $extension = '.' . $image->extension;

        if (isset($settings['action_groups']) == false or empty($settings['action_groups']) == true) {
            return $OUT;
        }

        //----------------------------------------
        // Size Metadata!
        //----------------------------------------
        $metadata = array();
        if ($image->sizes_metadata != false) {
            $temp = explode('/', $image->sizes_metadata);
            foreach ($temp as $row) {
                if ($row == false) {
                    continue;
                }
                $temp2 = explode('|', $row);

                // In some installs size is not set.
                if (isset($temp2[3]) === false or $temp2[3] == false) {
                    $temp2[3] = 0;
                }
                if (isset($temp2[2]) === false or $temp2[2] == false) {
                    $temp2[2] = 0;
                }
                if (isset($temp2[1]) === false or $temp2[1] == false) {
                    $temp2[1] = 0;
                }

                $metadata[$temp2[0]] = array('width' => $temp2[1], 'height' => $temp2[2], 'size' => $temp2[3]);
            }
        }

        // -----------------------------------------
        // Loop over all sizes!
        // -----------------------------------------
        foreach ($settings['action_groups'] as $group) {
            $name = strtolower($group['group_name']);
            $newname = str_replace($extension, "__{$name}{$extension}", $image->filename);

            // -----------------------------------------
            // Image URL (Size)
            // -----------------------------------------
            $image_url = $this->LOCS[$image->field_id]->parse_image_url($image->entry_id, $newname);

            // Did something go wrong?
            if ($image_url == false) {
                ee()->TMPL->log_item('CHANNEL IMAGES: Image URL Failed for: ' . $image->entry_id . '/' . $image->filename);
                continue;
            }

            // SSL?
            if ($this->IS_SSL == true) {
                $image_url = str_replace('http://', 'https://', $image_url);
            }

            $OUT = str_replace(LD . $this->prefix . 'filename:' . $name . RD, $newname, $OUT);
            $OUT = str_replace(LD . $this->prefix . 'url:' . $name . RD, $image_url, $OUT);
            $OUT = str_replace(LD . $this->prefix . 'secure_url:' . $name . RD, str_replace('http://', 'https://', $image_url), $OUT);

            // -----------------------------------------
            // Locked URLS (Size)
            // -----------------------------------------
            if ($this->locked_url == true) {
                $locked = array('image_id' => $image->image_id, 'size' => $name, 'time' => ee()->localize->now + 3600, 'ip' => $this->IP);
                $OUT = str_replace(LD . $this->prefix . 'locked_url:' . $name . RD, ($this->locked_act_url . '&key=' . base64_encode(serialize($locked))), $OUT);
            }

            //----------------------------------------
            // Check for filesize, Since it's an expensive operation
            //----------------------------------------
            if ($this->parse_filesize == true) {
                // If filesize is not defined, lets find it (only for local files)
                if (isset($metadata[$name]) == false && $settings['upload_location'] == 'local') {
                    $filepath = $this->LOCS[$image->field_id]->getLocationPrefs($settings['locations']['local']['location']);
                    $filepath = $filepath->server_path  . $image->entry_id . '/' . $newname;
                    $metadata[$name]['size'] = @filesize($filepath);
                }

                if (isset($metadata[$name]['size']) === false) {
                    $metadata[$name]['size'] = 0;
                }

                $OUT = str_replace(LD . $this->prefix . 'filesize:' . $name . RD, ee('channel_images:Helper')->formatBytes($metadata[$name]['size']), $OUT);
                $OUT = str_replace(LD . $this->prefix . 'filesize_bytes:' . $name . RD, $metadata[$name]['size'], $OUT);
            }

            //----------------------------------------
            // Check for image_dimensions, Since it's an expensive operation
            //----------------------------------------
            if ($this->parse_dimensions == true) {
                // If filesize is not defined, lets find it (only for local files)
                if (isset($metadata[$name]) === false && $settings['upload_location'] == 'local') {
                    $filepath = $this->LOCS[$image->field_id]->getLocationPrefs($settings['locations']['local']['location']);
                    $filepath = $filepath->server_path  . $image->entry_id . '/' . $newname;
                    $imginfo = @getimagesize($filepath);

                    // from support - this get imagesize can return a false which leads to php warnings of false to arrays
                    if ($imginfo == false) {
                        $metadata[$name]['width'] = '';
                        $metadata[$name]['height'] = '';
                    } else {
                        $metadata[$name]['width'] = $imginfo[0];
                        $metadata[$name]['height'] = $imginfo[1];
                    }
                }

                if (isset($metadata[$name]['width']) === false) {
                    $metadata[$name]['width'] = '';
                }
                if (isset($metadata[$name]['height']) === false) {
                    $metadata[$name]['height'] = '';
                }

                $OUT = str_replace(LD . $this->prefix . 'width:' . $name . RD, $metadata[$name]['width'], $OUT);
                $OUT = str_replace(LD . $this->prefix . 'height:' . $name . RD, $metadata[$name]['height'], $OUT);
            }
        }

        return $OUT;
    }

    // ********************************************************************************* //

    public function pre_fetch_data($entry_ids = array(), $params = array())
    {
        if (empty($entry_ids) == true) {
            return;
        }

        // Lets remove all unwanted params
        unset($params['entry_id'], $params['url_title']);

        // Make our hash
        $hash = crc32(serialize($params));

        $params['entry_id'] = implode('|', $entry_ids);

        $result = $this->get_images($params);

        if ($result == false) {
            return;
        }

        $images = array();
        foreach ($result as $row) {
            $images[] = $row;
        }

        ee()->session->set_cache('channel_images', 'images/' . $hash . '/' . $row->entry_id, $images);
    }

    // ********************************************************************************* //

    /**
     * Get Settings of a field
     *
     * @param int $field_id
     * @access public
     * @return array - Field Settings
     */
    public function get_field_settings($field_id)
    {
        if (empty(ee()->session->cache('channel_images', 'field_settings/' . $field_id))) {
            $query = ee()->db->select('field_settings')->from('exp_channel_fields')->where('field_id', $field_id)->get();
            if ($query->num_rows() == 0) {
                return false;
            }

            ee()->session->set_cache('channel_images', 'field_settings/' . $field_id, unserialize(base64_decode($query->row('field_settings'))), 500);
        }

        return ee()->session->cache('channel_images', 'field_settings/' . $field_id);
    }

    // ********************************************************************************* //

    // ********************************************************************************* //

    public function get_channel_id($channels)
    {
        if ($channels == false) {
            return false;
        }

        // Multiple Channels?
        if (strpos($channels, '|') !== false) {
            $channels = explode('|', $channels);
            $lookup = array();
            $return = array();

            foreach ($channels as $key => $value) {
                // Did we Cache this already?
                if (!empty(ee()->session->cache('channel_images', 'channel_to_id/' . $key))) {
                    $return[] = ee()->session->cache('channel_images', 'channel_to_id/' . $key);
                    continue;
                }

                $lookup[] = "'" . $value . "'";
            }

            if (empty($lookup) === false) {
                $query = ee()->db->query("SELECT channel_id, channel_name FROM exp_channels WHERE channel_name IN ({$lookup}) AND site_id = {$this->site_id} ");
                if ($query->num_rows() == 0) {
                    return false;
                }

                foreach ($query->result() as $row) {
                    ee()->session->set_cache('eeharbor', 'channel_to_id/' . $row->channel_name, $row->channel_id);
                    $return[] = $row->channel_id;
                }
            }

            if (empty($channels) === true) {
                return false;
            }
            return $channels;
        } else {
            // Did we Cache this already?
            if (empty(ee()->session->cache('eeharbor', 'channel_to_id/' . $channels))) {
                $query = ee()->db->query("SELECT channel_id FROM exp_channels WHERE channel_name = '{$channels}' AND site_id = {$this->site_id} ");
                if ($query->num_rows() == 0) {
                    return false;
                }

                ee()->session->set_cache('eeharbor', 'channel_to_id/' . $channels, $query->row('channel_id'));
            }

            return ee()->session->cache('eeharbor', 'channel_to_id/' . $channels);
        }
    }

    // ********************************************************************************* //

    public function get_fields_from_params($params)
    {
        $fields = array();
        $site_id = isset($params['site_id']) ? $params['site_id'] : $this->site_id;

        if (isset($params['field_id']) === true) {
            // Multiple fields?
            if (strpos($params['field_id'], '|') !== false) {
                return explode('|', $params['field_id']);
            } else {
                return $params['field_id'];
            }
        }

        if (isset($params['field']) === true) {
            // Multiple fields?
            if (strpos($params['field'], '|') !== false) {
                $pfields = explode('|', $params['field']);

                foreach ($pfields as $field) {
                    if (isset(ee()->session->cache['channel']['custom_channel_fields'][$site_id][$field]) === false) {
                        // Grab the field id
                        $query = ee()->db->query("SELECT field_id FROM exp_channel_fields WHERE field_name = '{$field}' AND (site_id = {$site_id} OR site_id = 0) ");
                        if ($query->num_rows() == 0) {
                            if (isset(ee()->TMPL) === true) {
                                ee()->TMPL->log_item('CHANNEL_IMAGES: Could not find field : ' . $field);
                            }
                            return false;
                        } else {
                            ee()->session->cache['channel']['custom_channel_fields'][$site_id][$field] = $query->row('field_id');
                        }
                    }

                    $fields[] = ee()->session->cache['channel']['custom_channel_fields'][$site_id][$field];
                }
            } else {
                if (isset(ee()->session->cache['channel']['custom_channel_fields'][$site_id][$params['field']]) === false) {
                    // Grab the field id
                    $query = ee()->db->query("SELECT field_id FROM exp_channel_fields WHERE field_name = '{$params['field']}' AND (site_id = {$site_id} OR site_id = 0) ");
                    if ($query->num_rows() == 0) {
                        if (isset(ee()->TMPL) === true) {
                            ee()->TMPL->log_item('CHANNEL_IMAGES: Could not find field : ' . $params['field']);
                        }
                        return false;
                    } else {
                        ee()->session->cache['channel']['custom_channel_fields'][$site_id][$params['field']] = $query->row('field_id');
                    }
                }

                return ee()->session->cache['channel']['custom_channel_fields'][$site_id][$params['field']];
            }
        }

        if (empty($fields) === true) {
            return false;
        }

        return $fields;
    }

    // ********************************************************************************* //

    // TEMP SOLUTION FOR EE 2.1.1 SIGH!!!
    public function _assign_libraries()
    {
    }

    // ********************************************************************************* //

    private function getGps($exifCoord, $hemi)
    {
        $degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

        $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

        return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
    }

    // ********************************************************************************* //

    private function gps2Num($coordPart)
    {
        $parts = explode('/', $coordPart);

        if (count($parts) <= 0) {
            return 0;
        }

        if (count($parts) == 1) {
            return $parts[0];
        }

        return floatval($parts[0]) / floatval($parts[1]);
    }

    // ********************************************************************************* //

    public function XMP2array($data)
    {
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); // Dont mess with my cAsE sEtTings
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); // Dont bother with empty info
        xml_parse_into_struct($parser, $data, $values);
        xml_parser_free($parser);
        //print_r($values);

        $xmlarray           = array();  // The XML array
        $xmp_array          = array();  // The returned array
        $stack              = array();  // tmp array used for stacking
        $list_array         = array();  // tmp array for list elements
        $list_element       = false;    // rdf:li indicator
        $temp_attr          = array();
        $last_open_tag      = '';

        foreach ($values as $val) {
            if ($val['type'] === "open") {
                if (isset($val['attributes'])) {
                    $temp_attr[$val['tag']] = $val['attributes'];
                } else {
                    array_push($stack, $val['tag']);
                }
                $last_open_tag = $val['tag'];
            } elseif ($val['type'] === "close") {
                // reset the compared stack
                if ($list_element == false) {
                    if (isset($stack['value']) === false || !$stack['value']) {
                        if (array_key_exists($val['tag'], $temp_attr)) {
                            $xmlarray[$val['tag']] = $temp_attr[$val['tag']];
                        }
                    }
                }
                $last_open_tag = '';
                array_pop($stack);
                // reset the rdf:li indicator & array
                $list_element = false;
                $list_array   = array();
            } elseif ($val['type'] === "complete") {
                if ($val['tag'] === "rdf:li") {
                    // first go one element back
                    if ($list_element == false) {
                        array_pop($stack);
                    }

                    $list_element = true;
                    // save it in our temp array
                    $list_array[] = isset($val['value']) ? $val['value'] : '';
                    //print_r( $val['value']);
                    // in the case it's a list element we seralize it
                    //$value = implode(",", $list_array);
                    $this->setArrayValue($xmlarray, $stack, $list_array);
                } else {
                    array_push($stack, $val['tag']);
                    if (array_key_exists('value', $val)) {
                        $this->setArrayValue($xmlarray, $stack, $val['value']);
                    } elseif (array_key_exists('attributes', $val)) {
                        $xmlarray[$val['tag']] = $val['attributes'];
                    }
                    array_pop($stack);
                }
            }
        } // foreach

        // cut off the useless tags
        $strip_keys = array('x:xmpmeta', 'rdf:RDF');

        foreach ($strip_keys as $k) {
            unset($xmlarray[$k]);
        }


        //$xmlarray = $this->exchangeKeys($xmlarray);
        //print_r($xmlarray);
        return $xmlarray;
    }

    public function setArrayValue(&$array, $stack, $value)
    {
        if ($stack) {
            $key = array_shift($stack);
            //print $key;
            //TODO:Review this, reports sometimes a error "Fatal error: Only variables can be passed by reference" (PHP 5.2.6)

            $this->setArrayValue($array[$key], $stack, $value);

            return $array;
        } else {
            $array = $value;
        }
    }
} // END CLASS

/* End of file Channel_images_model.php  */
/* Location: ./system/expressionengine/third_party/channel_images/models/Channel_images_model.php */
