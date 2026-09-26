# Fast Entries - Database Optimization Guide

## Problem: Slow COUNT Queries with Categories

If you're experiencing slow pagination (1+ seconds), it's likely due to missing database indexes on the category tables.

### The Slow Query

```sql
SELECT COUNT(DISTINCT ct.entry_id) as total
FROM exp_channel_titles ct
INNER JOIN exp_channels ch ON ch.channel_id = ct.channel_id
INNER JOIN exp_category_posts cp ON cp.entry_id = ct.entry_id
INNER JOIN exp_categories cat ON cat.cat_id = cp.cat_id
WHERE ch.channel_name IN ('shop1', 'shop2')
AND ct.status = 'open'
AND cat.cat_url_title = 'furniture'
```

This can take 1-2 seconds without proper indexes.

## Solution: Add Database Indexes

Run these SQL commands in your database (via phpMyAdmin, Sequel Pro, or command line):

### 1. Index on category_posts.entry_id
```sql
ALTER TABLE `exp_category_posts` 
ADD INDEX `idx_entry_id` (`entry_id`);
```

### 2. Index on categories.cat_url_title
```sql
ALTER TABLE `exp_categories` 
ADD INDEX `idx_cat_url_title` (`cat_url_title`);
```

### 3. Composite index on channel_titles (for multiple channels)
```sql
ALTER TABLE `exp_channel_titles` 
ADD INDEX `idx_channel_status` (`channel_id`, `status`);
```

### 4. Index on channels.channel_name
```sql
ALTER TABLE `exp_channels` 
ADD INDEX `idx_channel_name` (`channel_name`);
```

## Expected Performance Improvement

**Before indexes:**
- COUNT query: ~1.5-2 seconds
- Total page load: ~2-3 seconds

**After indexes:**
- COUNT query: ~0.01-0.05 seconds (20-100x faster!)
- Total page load: ~0.1-0.3 seconds

## Verify Indexes Are Working

After adding indexes, you can verify they're being used with EXPLAIN:

```sql
EXPLAIN SELECT COUNT(DISTINCT ct.entry_id) as total
FROM exp_channel_titles ct
INNER JOIN exp_channels ch ON ch.channel_id = ct.channel_id
INNER JOIN exp_category_posts cp ON cp.entry_id = ct.entry_id
INNER JOIN exp_categories cat ON cat.cat_id = cp.cat_id
WHERE ch.channel_name IN ('shop1', 'shop2')
AND ct.status = 'open'
AND cat.cat_url_title = 'furniture';
```

Look for `Using index` in the `Extra` column - this means MySQL is using your indexes.

## Alternative: Enable Query Caching

If you can't add indexes (shared hosting, etc.), you can cache the count results.

### Add to pi.fast_entries.php (after line ~45):

```php
// Check cache first
$cache_key = 'fast_entries_count_' . md5($channel . $status . $category_url_title);
$cached_count = ee()->cache->get($cache_key, Cache::GLOBAL_SCOPE);

if ($cached_count !== false) {
    $total_rows = (int) $cached_count;
} else {
    $total_rows = $this->get_total_count($channel, $status, $category_url_title);
    // Cache for 5 minutes
    ee()->cache->save($cache_key, $total_rows, 300, Cache::GLOBAL_SCOPE);
}
```

**Note:** Caching means the count may be stale for up to 5 minutes after new entries are added.

## Best Practice

**Always add the indexes.** They help not just this plugin, but all of ExpressionEngine's category queries.

## Checking Current Indexes

To see what indexes you already have:

```sql
SHOW INDEX FROM exp_category_posts;
SHOW INDEX FROM exp_categories;
SHOW INDEX FROM exp_channel_titles;
SHOW INDEX FROM exp_channels;
```

## Safe to Run?

Yes! Adding indexes:
- ✅ Does NOT modify any data
- ✅ Does NOT break existing queries
- ✅ Only makes queries faster
- ✅ Can be removed if needed with `DROP INDEX`

## Troubleshooting

### "Duplicate key name" error
If you get this error, the index already exists (good!). Skip that one.

### Still slow after adding indexes?
1. Check indexes are actually created: `SHOW INDEX FROM table_name`
2. Run EXPLAIN on your query to verify indexes are being used
3. Check your server load - might be a server resource issue
4. Consider enabling query result caching (see above)

## Server Recommendations

For best performance:
- MySQL 5.7+ or MariaDB 10.2+
- InnoDB storage engine (default in modern MySQL)
- At least 256MB MySQL memory allocation
