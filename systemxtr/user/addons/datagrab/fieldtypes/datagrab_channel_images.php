<?php

use BoldMinded\DataGrab\FieldTypes\AbstractFieldType;
use BoldMinded\DataGrab\FieldTypes\ImportField;
use BoldMinded\DataGrab\Service\Importer;

/**
 * DataGrab Channel Images fieldtype class
 *
 * @package   DataGrab
 * @author    BoldMinded, LLC <support@boldminded.com>
 * @copyright Copyright (c) BoldMinded, LLC
 */
class Datagrab_channel_images extends AbstractFieldType
{
    protected string $docUrl = 'https://docs.boldminded.com/datagrab/docs/field-types/channel-images';
    
    protected int $totalChannelImages = 29;

    public function __construct()
    {
        parent::__construct();
        ee()->load->add_package_path(PATH_THIRD . 'channel_images/');
    }

    public function register_setting(string $fieldName): array
    {
        $subSettings = [];
        
        // Register each image as a sub-setting
        for ($i = 1; $i <= $this->totalChannelImages; $i++) {
            $subSettings[] = 'image' . $i;
        }
        
        return [
            $fieldName => $subSettings
        ];
    }

    public function display_configuration(
        Importer $importer,
        string   $fieldName,
        string   $fieldLabel,
        string   $fieldType,
        bool     $fieldRequired = false,
        array    $data = []
    ): array {
        $config = [];
        $config['label'] = $this->displayLabel($fieldLabel, $fieldName, $fieldRequired, 'channel_images');

        $fieldSets = ee('View')
            ->make('ee:_shared/form/section')
            ->render([
                'name' => 'fieldset_group',
                'settings' => $this->getFormFields(
                    $fieldName,
                    [],
                    $data,
                    $this->getSavedFieldValues($data, $fieldName),
                )
            ]);

        $config['value'] = $fieldSets;

        return $config;
    }

    public function getFormFields(
        string $fieldName,
        array $fieldSettings,
        array $data = [],
        array $savedFieldValues = [],
        string $contentType = 'channel',
    ): array {
        $fieldOptions = [];

        for ($i = 1; $i <= $this->totalChannelImages; $i++) {
            $imageKey = 'image' . $i;

            $fieldOptions[] = [
                'title' => "Image {$i}",
                'desc' => "Select data field for image {$i}",
                'fields' => [
                    $fieldName . '[' . $imageKey . ']' => [
                        'type' => 'dropdown',
                        'choices' => $data['data_fields'],
                        'value' => $savedFieldValues[$imageKey] ?? '',
                    ]
                ]
            ];
        }

        return $fieldOptions;
    }

    public function hasFieldValue(array $settings = []): string
    {
        // Check if any image field has a value
        for ($i = 1; $i <= $this->totalChannelImages; $i++) {
            $imageKey = 'image' . $i;
            if (!empty($settings[$imageKey])) {
                return $settings[$imageKey];
            }
        }
        return '';
    }

    public function preparePostData(ImportField $importField): array
    {
        // Channel Images uses postProcessEntry to actually create images via API
        return [];
    }

    public function postProcessEntry(ImportField $importField): void
    {
        $entryId = $importField->entryId;
        $fieldId = $importField->fieldId;
        $item = $importField->importItem;
        
        // Get field settings to check upload location
        $fieldSettings = ee('channel_images:Settings')->getFieldtypeSettings($fieldId);
        
        if (!isset($fieldSettings['upload_location'])) {
            $importField->importer->logger->log('Channel Images: No upload_location set in field settings');
            return;
        }
        
        // Get channel_id for this entry
        $query = ee()->db->select('channel_id, site_id')
            ->from('exp_channel_titles')
            ->where('entry_id', $entryId)
            ->get();
        
        if ($query->num_rows() == 0) {
            $importField->importer->logger->log("Channel Images: Could not find channel_id for entry $entryId");
            return;
        }
        
        $entryData = $query->row_array();
        $channelId = $entryData['channel_id'];
        $siteId = $entryData['site_id'];
        
        // Load Channel Images API
        if (!class_exists('Channel_Images_API')) {
            require_once PATH_THIRD . 'channel_images/api.channel_images.php';
        }
        
        $ciApi = new Channel_Images_API();
        $currentImages = [];
        
        // Process each potential image field
        for ($i = 1; $i <= $this->totalChannelImages; $i++) {
            $imageKey = 'image' . $i;
            
            // Access the column name from the config
            $columnName = $importField->fieldImportConfig[$imageKey] ?? '';
            
            if (empty($columnName) || empty($item[$columnName])) {
                continue;
            }

            $fileUrl = trim($item[$columnName]);
            
            // Process filename based on source
            if (strpos($fileUrl, 'vidaxlpim.blob.core.windows.net/pimsalsify') !== false) {
                // Azure Blob URLs - preserve exact structure
                $parsed = parse_url($fileUrl);
                $decodedPath = urldecode($parsed['path']);
                $originalFilename = basename($decodedPath);
                $originalFilename = strtolower(ee()->security->sanitize_filename($originalFilename));
                $filename = str_replace([' ', '+'], '_', $originalFilename);
            } else {
                // Standard URL processing
                $originalFilename = str_replace('/', '_', substr(parse_url($fileUrl, PHP_URL_PATH), 1));
                $originalFilename = strtolower(ee()->security->sanitize_filename($originalFilename));
                $filename = str_replace([' ', '+', '%'], ['_', '', ''], $originalFilename);
            }
            
            // Also format through CI's formatter to ensure consistency
            $filename = $ciApi->format_filename($filename);
            
            // Check if image already exists for this entry/field
            $existingQuery = ee()->db->get_where('exp_channel_images', [
                'filename' => $filename,
                'entry_id' => $entryId,
                'field_id' => $fieldId,
                'is_draft' => 0
            ]);
            
            if ($existingQuery->num_rows() > 0) {
                // Get the ACTUAL filename from DB (might be .webp if previously converted)
                $existing = $existingQuery->row();
                $currentImages[] = $existing->filename;
                continue;
            }
            
            // Don't add to $currentImages yet - we need to know the final filename after conversion
            
            // Download image
            $arrContextOptions = [
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
            ];

            $imageData = @file_get_contents($fileUrl, false, stream_context_create($arrContextOptions));
            
            if ($imageData === false) {
                continue;
            }
            
            // Prepare data for Channel Images API
            $extension = substr(strrchr($filename, '.'), 1);
            
            $tempKey = time() . rand(1, 999);
            
            $apiData = [
                'field_id' => $fieldId,
                'entry_id' => $entryId,
                'channel_id' => $channelId,
                'site_id' => $siteId,
                'member_id' => ee()->session->userdata['member_id'],
                'temp_key' => $tempKey,
                'image_order' => $i - 1,
                'filename' => $filename,
                'extension' => $extension,
                'image_data' => $imageData,
                'title' => ucfirst(str_replace('_', ' ', str_replace('.' . $extension, '', $filename))),
                'description' => '',
                'category' => '',
                'cifield_1' => '',
                'cifield_2' => '',
                'cifield_3' => '',
                'cifield_4' => '',
                'cifield_5' => '',
            ];
            
            $imageId = $ciApi->add_image($apiData);
            
            if ($imageId === false) {
                $importField->importer->logger->log("Failed to add image $filename");
            } else {
                // Get the ACTUAL filename that was saved (might be .webp if converted)
                $saved_image = ee()->db->select('filename')
                    ->where('image_id', $imageId)
                    ->get('exp_channel_images')
                    ->row();
                
                if ($saved_image && !empty($saved_image->filename)) {
                    $currentImages[] = $saved_image->filename;
                }
            }
        }
        
        // Clean up temp directories
        $ciApi->clean_temp_dirs($fieldId);
        
        // Remove images not in current import
        if (!empty($currentImages)) {
            ee()->db->where_not_in('filename', $currentImages);
        }
        ee()->db->where('entry_id', $entryId);
        ee()->db->where('field_id', $fieldId);
        ee()->db->where('is_draft', 0);
        ee()->db->delete('exp_channel_images');
    }
}
