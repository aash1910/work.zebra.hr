# Custom Resources - EE7 Migration

## Overview
This is the ExpressionEngine 7 compatible version of the Custom Resources plugin. It has been completely rewritten to work with EE7's architecture while maintaining all original functionality.

## Key Changes from EE3 → EE7

### Architecture Updates
1. **Plugin Structure**: Follows EE7 addon structure
2. **Database Schema Change - CRITICAL**: 
   - **EE3**: Custom field data was in `exp_channel_data` table with columns `field_id_X`
   - **EE7**: Each field gets its own table `exp_channel_data_field_X` (where X = field_id)
   - **EE7**: Each field table contains columns: `entry_id` and `field_id_X`
   - **EE7**: `exp_channel_data` table still exists but only has `entry_id`, `site_id`, `channel_id` (no field data)
   
   **Example:**
   ```sql
   -- EE3
   SELECT field_id_82 FROM exp_channel_data WHERE entry_id = 123
   
   -- EE7
   SELECT field_id_82 FROM exp_channel_data_field_82 WHERE entry_id = 123
   ```
   
3. **All Queries Updated**: Every function that accesses custom field data now uses the correct per-field tables
4. **Channel Images Integration**: Now uses Channel Images API directly (like DataGrab)
5. **Store Integration**: Maintains compatibility with Store addon
6. **Helper Methods**: Added explicit helper methods for cleaner code

### Channel Images Import Function
The `import_channel_images_from_external_db()` function has been completely rewritten to use the Channel Images API:

**Old Method (EE3)**:
- Downloaded images manually
- Created files in upload directory
- Ran action groups manually
- Inserted database records manually

**New Method (EE7)**:
- Uses `Channel_Images_API->add_image()`
- API handles all image processing
- API creates sized versions automatically
- API uploads to final location
- API creates database records
- Includes proper cleanup

### What Stayed the Same
- Azure Blob URL handling (your special logic preserved)
- File size checking (< 1000 bytes rejected)
- Cleanup of old images not in current import
- All your comments and special notes preserved

## Installation

### File Structure
```
/system/user/addons/customresources/
├── addon.setup.php
├── upd.customresources.php
├── pi.customresources.php
└── language/
    └── english/
        └── customresources_lang.php
```

### Steps
1. Create the directory structure above
2. Upload all files to `/system/user/addons/customresources/`
3. Go to CP → Developer → Add-Ons
4. Install "Custom Resources"

## Configuration

### Channel Images Import
Before using `import_channel_images_from_external_db()`, update these values in the function:

```php
$field_id = 42;  // Your Channel Images field ID
$db_host = 'localhost';
$db_user = 'your_user';
$db_password = 'your_password';
$db_name = 'your_database';
```

## Usage

All template tags work exactly the same as EE3:

### Get Distinct Options
```
{exp:customresources:get_distinct_options field_id="5" channel="products"}
    {option}
{/exp:customresources:get_distinct_options}
```

### Send Emails to Store Customers
```
{exp:customresources:send_email_to_user 
    status="pending" 
    email_id="1" 
    past_hour_from="24" 
    past_hour_to="1"}
```

### Import Channel Images
Access via URL:
```
http://yoursite.com?ACT=XX&total=100&from=0
```
(Replace XX with the action ID)

### Other Functions
- `delete_orders` - Delete Store orders
- `status_closed_stock` - Set entries to closed and reset stock
- `status_closed` - Set all channel entries to closed
- `status_instock` - Set all products to in-stock
- `generating_files` - Generate order export files
- `najprodavanije_ids` - Get most popular product IDs
- `categoryorders` - Get category order counts
- `delete_entries` - Delete entries by ID
- `listEntriesOlderThanAge` - List old entries
- `archiveOldEntries` - Archive entries by changing status
- `delete_entries_automated` - Auto-delete old entries
- `dateplusyear` - Add one year to date
- `broj_prodanih` - Get total sold quantity

## Important Notes

### Channel Images
- The import function now uses Channel Images API
- Images are processed automatically (no manual action groups)
- Temp directories are cleaned up automatically
- All sized versions are created automatically
- Database records are created automatically

### Security
- All database queries use `ee()->db->escape_str()` for user input
- External database connection should use environment variables in production
- Consider adding authentication for import functions

### Performance
- Import processes in batches (use `total` and `from` parameters)
- Large imports should be queued or run via CLI
- Consider adding progress logging

## Troubleshooting

### EE7 Database Structure
**IMPORTANT**: In EE7, each custom field has its own data table:
- Table structure: `exp_channel_data_field_X` (where X = field_id, NOT channel_id)
- Each table contains: `entry_id` and `field_id_X` columns
- `exp_channel_data` table still exists but only contains: `entry_id`, `site_id`, `channel_id`
- NO custom field data is stored in `exp_channel_data` anymore

**Examples:**
- Field ID 5 data → `exp_channel_data_field_5` table → column `field_id_5`
- Field ID 82 data → `exp_channel_data_field_82` table → column `field_id_82`

If you have custom queries or other extensions accessing field data, they must be updated to use the per-field tables.

### Images Not Importing
1. Check Channel Images field ID is correct
2. Verify external database credentials
3. Check file permissions on upload directory
4. Check PHP memory limit for large images
5. Verify SSL context settings for Azure Blob URLs

### Database Errors
- Update table prefixes if not using `exp_`
- Check database connection settings
- Verify Store addon is installed and up-to-date

## Support
For issues specific to EE7 migration, check:
- ExpressionEngine 7 documentation
- Channel Images documentation  
- Store addon documentation

## License
MIT License (same as original)

## Credits
- Original: ashraful1910@gmail.com
- EE7 Migration: Based on DataGrab Channel Images field type patterns
- Maintained by: Krunoslav Vuković
