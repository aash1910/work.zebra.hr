<?php

/**
 * Channel Images API File
 *
 * @package         EEHarbor_ChannelFiles
 * @author          EEHarbor <https://eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license         https://eeharbor.com/license
 * @link            https://eeharbor.com
 */
class Channel_Images_API
{
    private $valid_mime = array('jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp');
    private $LOCS = array();

    public $last_error = array();
    public $channel_images;
    public $moduleSettings;
    public $site_id;
    public $temp_dir;
    public $metadata;
    public $image_metadata;

    /**
     * Constructor
     *
     * @access public
     *
     * Calls the parent constructor
     */
    public function __construct()
    {
        ee()->load->add_package_path(PATH_THIRD . 'channel_images/');
        ee()->load->model('channel_images_model');
        ee()->lang->loadfile('channel_images');
        $this->site_id = ee()->config->item('site_id');

        if (isset($this->channel_images) === false) {
            $this->channel_images = new stdClass();
        }

        $this->moduleSettings = ee('channel_images:Settings')->settings;
    }

    // ********************************************************************************* //

    public function delete_image($image , $upload_location='', $location_settings='', $action_groups='')
    {
        if (isset($image->field_id) == false) {
            return false;
        }

        // Grab the field settings
        // They may have already given us the settings, check and see, if anything is default we need to grab our own
        if ($upload_location === '' || $location_settings === '' || $action_groups === '') {
            $settings = ee('channel_images:Settings')->getFieldtypeSettings($image->field_id);
        } else {
            $settings['upload_location'] = $upload_location;
            $settings['locations'][$upload_location] = $location_settings;
            $settings['action_groups'] = $action_groups;
        }

        $location_type = $settings['upload_location'];
        $location_class = 'CI_Location_'.$location_type;
        $location_settings = $settings['locations'][$location_type];
        $location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

        // Load Main Class
        if (class_exists('Image_Location') == false) {
            require PATH_THIRD.'channel_images/locations/image_location.php';
        }
        if (class_exists($location_class) == false) {
            require $location_file;
        }
        $LOC = new $location_class($location_settings);

        // Delete From DB
        ee()->db->where('image_id', $image->image_id);
        ee()->db->or_where('link_image_id', $image->image_id);
        ee()->db->delete('exp_channel_images');

        // Is there another instance of the image still there?
        ee()->db->select('image_id');
        ee()->db->from('exp_channel_images');
        ee()->db->where('entry_id', $image->entry_id);
        ee()->db->where('field_id', $image->field_id);
        ee()->db->where('filename', $image->filename);
        $query = ee()->db->get();

        if ($query->num_rows() == 0) {
            // Loop over all action groups
            foreach ($settings['action_groups'] as $group) {
                $name = strtolower($group['group_name']);

                if ($image->filename != null) {
                    $name = str_replace('.'.$image->extension, "__{$name}.{$image->extension}", $image->filename);
                }

                $res = $LOC->delete_file($image->entry_id, $name);
            }

            // Delete original file from system
            $res = $LOC->delete_file($image->entry_id, $image->filename);
        }

        return true;
    }

    // ********************************************************************************* //

    public function clean_temp_dirs($field_id)
    {
        $temp_path = $this->moduleSettings['cache_path'].'channel_images/field_'.$field_id.'/';

        if (file_exists($temp_path) !== true) {
            return;
        }

        ee()->load->helper('file');

        // Loop over all files
        $tempdirs = @scandir($temp_path);

        foreach ($tempdirs as $tempdir) {
            if ($tempdir == '.' or $tempdir == '..') {
                continue;
            }
            if ((ee()->localize->now - $tempdir) < 7200) {
                continue;
            }

            @chmod($temp_path.$tempdir, 0777);
            @delete_files($temp_path.$tempdir, true);
            @rmdir($temp_path.$tempdir);
        }
    }

    // ********************************************************************************* //

    public function process_field_string($string)
    {
        $conf = ee()->config->item('channel_images');

        //ee()->firephp->log('BEFORE: '.$string);

        // Disable XSS?
        if (isset($conf['xss_field_strings']) === true && $conf['xss_field_strings'] == 'yes') {
            $string = ee()->security->xss_clean($string);
        }

        //ee()->firephp->log('AFTER XSS: '.$string);
        $string = htmlentities($string, ENT_QUOTES, "UTF-8");
        //ee()->firephp->log('AFTER HTML ENTITIES: '.$string);

        return $string;
    }

    // ********************************************************************************* //

    /**
     * Add Image
     * @param array $data image data
     * @access public
     * @return mixed - false on error or Image ID on success
     */
    public function add_image($data=array())
    {
        $error =& $this->last_error;

        if (isset($data['field_id']) === false) {
            $error = 'Missing Field ID';
            return false;
        }
        if (isset($data['entry_id']) === false) {
            $error = 'Missing Entry ID';
            return false;
        }

        if (isset($data['temp_key']) === false) {
            $data['temp_key'] = time();
        }
        if (isset($data['site_id']) === false) {
            $data['site_id'] = $this->site_id;
        }
        if (isset($data['member_id']) === false) {
            $data['member_id'] = ee()->session->userdata['member_id'];
        }

        if (isset($data['channel_id']) === false) {
            $query = ee()->db->select('channel_id')->from('exp_channel_titles')->where('entry_id', $data['entry_id'])->get();
            if ($query->num_rows() == 0) {
                $error = "Couldn't resolve channnel_id from entry_id";
                return false;
            } else {
                $data['channel_id'] = $query->row('channel_id');
            }
        }

        if (isset($data['image_order']) === false) {
            $data['image_order'] = 0;
        }
        if (isset($data['title']) === false) {
            $data['title'] = '';
        }
        if (isset($data['description']) === false) {
            $data['description'] = '';
        }
        if (isset($data['category']) === false) {
            $data['category'] = '';
        }
        if (isset($data['cifield_1']) === false) {
            $data['cifield_1'] = '';
        }
        if (isset($data['cifield_2']) === false) {
            $data['cifield_2'] = '';
        }
        if (isset($data['cifield_3']) === false) {
            $data['cifield_3'] = '';
        }
        if (isset($data['cifield_4']) === false) {
            $data['cifield_4'] = '';
        }
        if (isset($data['cifield_5']) === false) {
            $data['cifield_5'] = '';
        }
        if (isset($data['extension']) === false) {
            $data['extension'] = '';
        }

        // -----------------------------------------
        // Temp Dir to run Actions
        // -----------------------------------------
        $this->temp_dir = $this->moduleSettings['cache_path'].'channel_images/field_'.$data['field_id'].'/'.$data['temp_key'].'/';

        if (@is_dir($this->temp_dir) === false) {
            @mkdir($this->temp_dir, 0777, true);
            @chmod($this->temp_dir, 0777);
        }

        // -----------------------------------------
        // Load Settings
        // -----------------------------------------
        $settings = ee('channel_images:Settings')->getFieldtypeSettings($data['field_id']);
        if (isset($settings['upload_location']) == false) {
            $error = "Couldn't Find Upload Location?? It's Not Set.";
            return false;
            return false;
        }

        //----------------------------------------
        // Image URL
        //----------------------------------------
        if (isset($data['image_url']) === true) {
            if (isset($data['filename']) === false or $data['filename'] == false) {
                $data['filename'] = basename($data['image_url']);
            }

            $data['filename'] = $this->format_filename($data['filename']);
            $data['extension'] = substr(strrchr($data['filename'], '.'), 1);
            if (isset($this->valid_mime[ $data['extension'] ]) === false) {
                $error = "Not Falid MIME (extension)";
                return false;
            }
        }

        // -----------------------------------------
        // Grab all the files from the DB
        // -----------------------------------------
        ee()->db->select('*');
        ee()->db->from('exp_channel_images');
        ee()->db->where('entry_id', $data['entry_id']);
        ee()->db->where('field_id', $data['field_id']);
        ee()->db->where('filename', $data['filename']);
        ee()->db->where('is_draft', 0);
        $query = ee()->db->get();

        if ($query->num_rows() > 0) {
            ee()->db->set('title', $this->process_field_string($data['title']));
            ee()->db->set('url_title', $this->process_field_string(ee('Format')->make('Text', $data['title'])->urlSlug()));
            ee()->db->set('description', $this->process_field_string($data['description']));
            ee()->db->set('category', $this->process_field_string($data['category']));
            ee()->db->set('cifield_1', $this->process_field_string($data['cifield_1']));
            ee()->db->set('cifield_2', $this->process_field_string($data['cifield_2']));
            ee()->db->set('cifield_3', $this->process_field_string($data['cifield_3']));
            ee()->db->set('cifield_4', $this->process_field_string($data['cifield_4']));
            ee()->db->set('cifield_5', $this->process_field_string($data['cifield_5']));
            ee()->db->where('image_id', $query->row('image_id'));
            ee()->db->update('exp_channel_images');
            return $query->row('image_id');
        }


        //----------------------------------------
        // Image Data
        //----------------------------------------
        if (isset($data['image_data']) === true || isset($data['image_url']) === true) {
            if (isset($data['image_url']) === true && $data['image_url'] != false) {
                $data['image_data'] = ee('channel_images:Helper')->fetch_url_file($data['image_url']);
            }

            if (isset($data['filename']) === false or $data['filename'] == false) {
                $error = "Not Falid MIME (extension)";
                return false;
            }

            //file_put_contents($this->temp_dir.$data['filename'], $data['image_data']);
            $img = @imagecreatefromstring(trim($data['image_data']));

            if ($img === false) {
                $error = "imagecreatefromstring failed!";
                return false;
            }

            if ($data['extension'] == 'jpg') {
                imagejpeg($img, $this->temp_dir.$data['filename'], 100);
            } elseif ($data['extension'] == 'png') {
                imagepng($img, $this->temp_dir.$data['filename']);
            } elseif ($data['extension'] == 'gif') {
                imagegif($img, $this->temp_dir.$data['filename']);
            } else {
                $data['extension'] = 'jpg';
                imagejpeg($img, $this->temp_dir.$data['filename']);
            }

            @imagedestroy($img);

            @chmod($this->temp_dir.$data['filename'], 0777);
        }

        // Last Check
        if (file_exists($this->temp_dir.$data['filename']) === false) {
            $error = "Tripple checked is file was in temp dir and it's not there!";
            return false;
        }
        if (isset($this->valid_mime[ $data['extension'] ]) === false) {
            $error = "Tripple checked mime-type and it's not allowed";
            return false;
        }

        // File Size
        if (isset($data['filesize']) === false || $data['filesize'] == false) {
            $data['filesize'] = @filesize($this->temp_dir.$data['filename']);
        }

        // Run the Actions!
        $this->run_actions($data['filename'], $data['field_id']);

        // KRUNO WEBP
        // ============================================================================
        // FIX WEBP EXTENSIONS - Check if files are actually WebP and rename them
        // ============================================================================
        
        // Get all the size variations that were created
        $size_filenames = array($data['filename']); // Start with original
        
        foreach ($settings['action_groups'] as $group) {
            $name = strtolower($group['group_name']);
            if ($data['filename'] != null) {
                $size_filename = str_replace('.'.$data['extension'], "__{$name}.{$data['extension']}", $data['filename']);
                $size_filenames[] = $size_filename;
            }
        }
        
        // Check each file's actual format in temp directory
        $extension_changed = false;
        $new_extension = null;
        
        foreach ($size_filenames as $check_file) {
            $full_path = $this->temp_dir . $check_file;
            
            if (file_exists($full_path)) {
                // Check the ACTUAL file format using finfo
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $full_path);
                    finfo_close($finfo);
                    
                    // If it's WebP but has wrong extension
                    if ($mime === 'image/webp' && $data['extension'] !== 'webp') {
                        $extension_changed = true;
                        $new_extension = 'webp';
                        // Don't break; continue to confirm all are WebP (optional: comment out if you want to assume uniform)
                    }
                }
            }
        }
        
        // If not all sizes are WebP, bail out to avoid mixed formats (which the module doesn't support)
        if ($extension_changed) {
            $all_webp = true;
            foreach ($size_filenames as $check_file) {
                $full_path = $this->temp_dir . $check_file;
                if (file_exists($full_path)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $full_path);
                    finfo_close($finfo);
                    if ($mime !== 'image/webp') {
                        $all_webp = false;
                        break;
                    }
                }
            }
            if (!$all_webp) {
                $extension_changed = false; // Revert; can't safely change extension
            }
        }
        
        // Proceed with change
        if ($extension_changed && $new_extension) {
            // Get WebP quality safely from the first group that has it
            $quality = 80;
            
            if (!empty($settings['action_groups'])) {
                foreach ($settings['action_groups'] as $group) {
                    if (isset($group['actions']['convert_webp']['quality']) 
                        && is_numeric($group['actions']['convert_webp']['quality'])) {
                        $quality = (int)$group['actions']['convert_webp']['quality'];
                        $quality = max(0, min(100, $quality)); // clamp
                        break;
                    }
                }
            }

            // If original exists (keep_original=yes), convert its content to WebP
            $original_path = $this->temp_dir . $data['filename'];
            if (file_exists($original_path)) {
                $success = false;
                
                // Try Imagick first
                if (class_exists('Imagick')) {
                    try {
                        $image = new Imagick();
                        $image->readImage($original_path);
                        $image->setImageFormat('webp');
                        $image->setImageCompressionQuality($quality);
                        $image->writeImage($original_path);
                        $image->clear();
                        $image->destroy();
                        $success = true;
                    } catch (Exception $e) {
                        $success = false;
                    }
                }
                
                // Fall back to GD
                if (!$success && function_exists('imagewebp') && (imagetypes() & IMG_WEBP)) {
                    $img = null;
                    switch ($data['extension']) {
                        case 'jpg': $img = imagecreatefromjpeg($original_path); break;
                        case 'png': $img = imagecreatefrompng($original_path); break;
                        case 'gif': $img = imagecreatefromgif($original_path); break;
                    }
                    if ($img) {
                        imagewebp($img, $original_path, $quality);
                        imagedestroy($img);
                        $success = true;
                    }
                }
                
                // Update filesize after conversion
                if ($success) {
                    $data['filesize'] = @filesize($original_path);
                }
            }
        
            // Rename files in temp directory (only if they exist)
            $old_extension = $data['extension'];
            foreach ($size_filenames as $old_filename) {
                $new_filename = str_replace('.' . $old_extension, '.' . $new_extension, $old_filename);
                $old_path = $this->temp_dir . $old_filename;
                $new_path = $this->temp_dir . $new_filename;
                if (file_exists($old_path)) {
                    @rename($old_path, $new_path);
                }
            }
            
            // Update the data array
            $data['filename'] = str_replace('.' . $old_extension, '.' . $new_extension, $data['filename']);
            $data['extension'] = $new_extension;
        }
        
        // Optional post-fix check: only if keep_original=yes
        if (isset($settings['keep_original']) && $settings['keep_original'] == 'yes') {
            if (file_exists($this->temp_dir . $data['filename']) === false) {
                $error = "Original file missing after WebP processing";
                return false;
            }
        }

        //KRUNO END WEBP FIX

//KRUNO FORCE FILENAME
// ============================================================================
// RENAME FILE TO: {url_title}-{entry_id}-{random6}.{extension}
// ============================================================================

$url_title_row = ee()->db->select('url_title')
    ->from('exp_channel_titles')
    ->where('entry_id', $data['entry_id'])
    ->get()->row_array();

    $title_row = ee()->db->select('title')
    ->from('exp_channel_titles')
    ->where('entry_id', $data['entry_id'])
    ->get()->row_array();

$url_title_slug = !empty($url_title_row['url_title']) ? $url_title_row['url_title'] : 'entry';
$title_of_entry = !empty($title_row['title']) ? $title_row['title'] : 'entry';
$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
$random6 = '';
for ($i = 0; $i < 6; $i++) {
    $random6 .= $chars[rand(0, strlen($chars) - 1)];
}
$forced_filename = $url_title_slug . '-' . $data['entry_id'] . '-' . $random6 . '.' . $data['extension'];
$forced_title = $title_of_entry. '-' . $data['entry_id'] . '-' . $random6;
$forced_url_title = $url_title_slug . '-' . $data['entry_id'] . '-' . $random6;

// Rename original in temp dir
$old_path = $this->temp_dir . $data['filename'];
$new_path = $this->temp_dir . $forced_filename;
if (file_exists($old_path)) {
    @rename($old_path, $new_path);
}

// Rename size variants in temp dir
foreach ($settings['action_groups'] as $group) {
    $name = strtolower($group['group_name']);
    $old_size = str_replace('.' . $data['extension'], "__{$name}." . $data['extension'], $data['filename']);
    $new_size = str_replace('.' . $data['extension'], "__{$name}." . $data['extension'], $forced_filename);
    $old_size_path = $this->temp_dir . $old_size;
    $new_size_path = $this->temp_dir . $new_size;
    if (file_exists($old_size_path)) {
        @rename($old_size_path, $new_size_path);
    }
}

// Update data array so everything downstream uses the new filename
$data['filename'] = $forced_filename;
$data['title'] = $forced_title;
$data['url_title'] = $forced_url_title;

//KRUNO END FORCE FILENAME

        $this->upload_images($data['entry_id'], $data['field_id']);

        $data['width'] = isset($this->metadata[ $data['filename'] ]['width']) ? $this->metadata[ $data['filename'] ]['width'] : 0;
        $data['height'] = isset($this->metadata[ $data['filename'] ]['height']) ? $this->metadata[ $data['filename'] ]['height'] : 0;

        //KRUNO FIX
        // Set width/height from first size variant if original is missing or zero
        if (($data['width'] ?? 0) == 0 && ($data['height'] ?? 0) == 0 && !empty($settings['action_groups'])) {
            $first_group = reset($settings['action_groups']);
            
            if (is_array($first_group) && !empty($first_group['group_name'])) {
                $name = strtolower($first_group['group_name']);
                $size_filename = str_replace(
                    '.' . ($data['extension'] ?? 'jpg'), 
                    "__{$name}." . ($data['extension'] ?? 'webp'), 
                    $data['filename'] ?? ''
                );
                
                if (!empty($this->image_metadata[$size_filename]) && is_array($this->image_metadata[$size_filename])) {
                    $data['width']  = (int) ($this->image_metadata[$size_filename]['width']  ?? 0);
                    $data['height'] = (int) ($this->image_metadata[$size_filename]['height'] ?? 0);
                }
            }
        }
        //KRUNO END FIX


        if ($data['title'] == false && $data['filename'] != null) {
            $data['title'] = ucfirst(str_replace('_', ' ', str_replace('.'.$data['extension'], '', $data['filename'])));
        }

        // -----------------------------------------
        // Parse Size Metadata!
        // -----------------------------------------
        $mt = '';
        foreach ($settings['action_groups'] as $group) {
            $name = strtolower($group['group_name']);

            if($data['filename'] != null) {
                $size_filename = str_replace('.'.$data['extension'], "__{$name}.{$data['extension']}", $data['filename']);
            }

            $mt .= $name.'|' . implode('|', $this->image_metadata[$size_filename]) . '/';
        }

        ee()->db->set('site_id', $data['site_id']);
        ee()->db->set('entry_id', $data['entry_id']);
        ee()->db->set('channel_id', $data['channel_id']);
        ee()->db->set('member_id', $data['member_id']);
        ee()->db->set('is_draft', 0);
        ee()->db->set('link_image_id', 0);
        ee()->db->set('link_entry_id', 0);
        ee()->db->set('link_channel_id', 0);
        ee()->db->set('link_field_id', 0);
        ee()->db->set('upload_date', ee()->localize->now);
        ee()->db->set('field_id', $data['field_id']);
        ee()->db->set('image_order', $data['image_order']);
        ee()->db->set('filename', $data['filename']);
        ee()->db->set('extension', $data['extension']);
        ee()->db->set('mime', $this->valid_mime[ $data['extension'] ]);
        ee()->db->set('filesize', $data['filesize']);
        ee()->db->set('width', $data['width']);
        ee()->db->set('height', $data['height']);
        ee()->db->set('title', $this->process_field_string($data['title']));
        ee()->db->set('url_title', $this->process_field_string(ee('Format')->make('Text', $data['title'])->urlSlug()));
        ee()->db->set('description', $this->process_field_string($data['description']));
        ee()->db->set('category', $this->process_field_string($data['category']));
        ee()->db->set('cifield_1', $this->process_field_string($data['cifield_1']));
        ee()->db->set('cifield_2', $this->process_field_string($data['cifield_2']));
        ee()->db->set('cifield_3', $this->process_field_string($data['cifield_3']));
        ee()->db->set('cifield_4', $this->process_field_string($data['cifield_4']));
        ee()->db->set('cifield_5', $this->process_field_string($data['cifield_5']));
        ee()->db->set('cover', 0);
        ee()->db->set('sizes_metadata', $mt);
        ee()->db->insert('exp_channel_images');

        $image_id = ee()->db->insert_id();

        @rmdir($this->temp_dir);

        return $image_id;
    }

    // ********************************************************************************* //

    public function run_actions($filename, $field_id, $temp_dir=false)
    {
        // -----------------------------------------
        // Load Settings
        // -----------------------------------------
        $settings = ee('channel_images:Settings')->getFieldtypeSettings($field_id);
        if (isset($settings['upload_location']) == false) {
            return false;
        }

        // -----------------------------------------
        // Load Actions :O
        // -----------------------------------------
        $actions = ee('channel_images:Actions')->actions;

        // Just double check for actions groups
        if (isset($settings['action_groups']) == false) {
            $settings['action_groups'] = array();
        }

        // Extension
        $extension = '.' . substr(strrchr($filename, '.'), 1);

        // Tempdir?
        if ($temp_dir != false) {
            $this->temp_dir = $temp_dir;
        }

        // -----------------------------------------
        // Loop over all action groups!
        // -----------------------------------------
        foreach ($settings['action_groups'] as $group) {
            $size_name = $group['group_name'];

            if($filename != null) {
                $size_filename = str_replace($extension, "__{$size_name}{$extension}", $filename);
            }

            // Make a copy of the file
            @copy($this->temp_dir.$filename, $this->temp_dir.$size_filename);
            @chmod($this->temp_dir.$size_filename, 0777);

            // -----------------------------------------
            // Loop over all Actions and RUN! OMG!
            // -----------------------------------------
            foreach ($group['actions'] as $action_name => $action_settings) {
                // RUN!
                $actions[$action_name]->settings = $action_settings;
                $actions[$action_name]->settings['field_settings'] = $settings;
                $res = $actions[$action_name]->run($this->temp_dir.$size_filename, $this->temp_dir);

                if ($res !== true) {
                    @unlink($this->temp_dir.$size_filename);
                    return false;
                }
            }
        }

        // -----------------------------------------
        // Keep Original Image?
        // -----------------------------------------
        if (isset($settings['keep_original']) == true && $settings['keep_original'] == 'no') {
            @unlink($this->temp_dir.$filename);
        }

        return true;
    }

    // ********************************************************************************* //

    public function upload_images($entry_id, $field_id, $temp_dir=false)
    {
        // -----------------------------------------
        // Load Settings
        // -----------------------------------------
        $settings = ee('channel_images:Settings')->getFieldtypeSettings($field_id);
        if (isset($settings['upload_location']) == false) {
            return false;
        }

        // Tempdir?
        if ($temp_dir != false) {
            $this->temp_dir = $temp_dir;
        }

        // -----------------------------------------
        // Load Location
        // -----------------------------------------
        $location_type = $settings['upload_location'];
        $location_class = 'CI_Location_'.$location_type;
        $location_settings = $settings['locations'][$location_type];

        // Load Main Class
        if (class_exists('Image_Location') == false) {
            require PATH_THIRD.'channel_images/locations/image_location.php';
        }

        // Try to load Location Class
        if (class_exists($location_class) == false) {
            $location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';

            require $location_file;
        }

        // Init!

        $LOC = new $location_class($location_settings);

        // Create the DIR!
        $LOC->create_dir($entry_id);

        // Image Widths,Height,Filesize
        $this->image_metadata = array();

        // Try to load Location Class
        if (class_exists($location_class) == false) {
            $location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';
            require $location_file;
        }

        // Loop over all files
        $tempfiles = @scandir($this->temp_dir);

        if (is_array($tempfiles) == true) {
            foreach ($tempfiles as $tempfile) {
                if ($tempfile == '.' or $tempfile == '..') {
                    continue;
                }

                $file   = $this->temp_dir . '/' . $tempfile;

                $res = $LOC->upload_file($file, $tempfile, $entry_id);

                if ($res == false) {
                }

                // Parse Image Size
                $imginfo = @getimagesize($file);

                // Metadata!
                $this->image_metadata[$tempfile] = array('width' => @$imginfo[0], 'height' => @$imginfo[1], 'size' => @filesize($file));
                @unlink($file);
            }
        }

        @rmdir($this->temp_dir);
    }

    // ********************************************************************************* //

    public function format_filename($filename)
    {
        $filename = strtolower(ee()->security->sanitize_filename($filename));

        if ($filename != null) {
            // Replace the jpeg extension
            $filename = str_replace(array(' ', '+'), array('_', ''), $filename);
            $filename = str_replace('.jpeg', '.jpg', $filename);
        }

        $filename = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $filename);

        return $filename;
    }

    // ********************************************************************************* //

    public function convertUrlsToTags($data)
    {
        $url = ee('channel_images:Helper')->getRouterUrl('url', 'simple_image_url');
        $urls = array();

        preg_match_all("#<img.+?src=[\"'](.+?)[\"'].+?>#s", $data, $matches);

        if (isset($matches[1]) === false || empty($matches[1]) === true) {
            return $data;
        }

        foreach ($matches[1] as $url) {
            $item = array();
            $item['original'] = $url;

            $query = parse_url($url, PHP_URL_QUERY);

            if ($query !== null) {
                $query = str_replace('&amp;', '&', $query);

                parse_str($query, $output);
                $item['parts'] = $output;
    
                $urls[] = $item;
            }

        }

        // General Vars
        foreach ($urls as $item) {
            $var = '{ci_image';

            if (isset($item['parts']['d']) === false && isset($item['parts']['f']) === false) {
                continue;
            }

            // Temp Dir ?
            if (isset($item['parts']['temp_dir']) === true && $item['parts']['temp_dir'] == 'yes') {
                $item['parts']['d'] = 0;
            }

            if (isset($item['parts']['fid']) === true) {
                $var .= " field_id='{$item['parts']['fid']}'";
            }

            if (isset($item['parts']['d']) === true) {
                $var .= " entry_id='{$item['parts']['d']}'";
            }

            if (isset($item['parts']['f']) === true) {
                $var .= " filename='{$item['parts']['f']}'";
            }

            $var .= '}';

            $data = str_replace($item['original'], $var, $data);
        }

        return $data;
    }

    // ********************************************************************************* //

    public function generateUrlsFromTags($data, $entry_id=0, $template=false)
    {
        $vars = array();

        if ($data != null) {
            preg_match_all("/{ci_image (.*?)}/s", $data, $varMatches);
        }

        if (isset($varMatches[1]) === false || empty($varMatches[1]) === true) {
            return $data;
        }

        foreach ($varMatches[0] as $key => $val) {
            $inner = '';
            $matches = array();
            $inner = $varMatches[1][$key];

            $inner = str_replace('&#039;', '\'', $inner);
            $inner = str_replace('&quot;', '"', $inner);

            preg_match_all("/(\S+?)\s*=\s*(\042|\047)([^\\2]*?)\\2/is", $inner, $matches, PREG_SET_ORDER);

            if (isset($matches[0][3]) === false) {
                continue;
            }

            $item = array();
            $item['original'] = $val;
            $item['params'] = array();

            foreach ($matches as $match) {
                $item['params'][$match[1]] = (trim($match[3]) == '') ? $match[3] : trim($match[3]);
            }

            $vars[] = $item;
        }

        $url = ee('channel_images:Helper')->getRouterUrl('url', 'simple_image_url');

        foreach ($vars as $var) {
            $imgurl = $url;

            if (isset($var['params']['entry_id']) === true) {
                if ($var['params']['entry_id'] == false) {
                    $var['params']['entry_id'] = $entry_id;
                }
                $imgurl .= '&amp;d=' . $var['params']['entry_id'];
            }

            if (isset($var['params']['field_id']) === true) {
                $imgurl .= '&amp;fid=' . $var['params']['field_id'];
            }

            if (isset($var['params']['filename']) === true) {
                $imgurl .= '&amp;f=' . $var['params']['filename'];
            }

            if ($template) {
                $field_id = $var['params']['field_id'];

                // Get the field settings
                $settings = ee('channel_images:Settings')->getFieldtypeSettings($field_id);

                //----------------------------------------
                // Load Location
                //----------------------------------------
                if (isset($this->LOCS[$field_id]) === false) {
                    $location_type = $settings['upload_location'];
                    $location_class = 'CI_Location_'.$location_type;
                    $location_settings = $settings['locations'][$location_type];

                    // Load Main Class
                    if (class_exists('Image_Location') == false) {
                        require PATH_THIRD.'channel_images/locations/image_location.php';
                    }

                    // Try to load Location Class
                    if (class_exists($location_class) == false) {
                        $location_file = PATH_THIRD.'channel_images/locations/'.$location_type.'/'.$location_type.'.php';
                        require $location_file;
                    }

                    // Init!
                    $this->LOCS[$field_id] = new $location_class($location_settings);
                }

                $imgurl = $this->LOCS[$field_id]->parse_image_url($var['params']['entry_id'], $var['params']['filename']);
            }

            $data = str_replace($var['original'], $imgurl, $data);
        }

        return $data;
    }

    // ********************************************************************************* //
} // END CLASS

/* End of file api.channel_images.php  */
/* Location: ./system/user/addons/channel_images/api.channel_images.php */
