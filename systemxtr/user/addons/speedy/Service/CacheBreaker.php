<?php

namespace BoldMinded\Speedy\Service;

use BoldMinded\Speedy\Library\Basee\Logger;
use BoldMinded\Speedy\Model\CacheBreaking;
use BoldMinded\Speedy\Queue\Jobs\BreakCacheJob;
use BoldMinded\Speedy\Queue\Jobs\BreakCategoryCacheJob;
use BoldMinded\Speedy\Queue\Jobs\BreakEntryCacheJob;
use BoldMinded\Speedy\Queue\Jobs\PurgeUrlJob;
use BoldMinded\Speedy\Queue\Jobs\RefreshUrlJob;
use BoldMinded\Speedy\Service\Drivers\DriverInterface;
use BoldMinded\Speedy\Service\Purgers\PurgerFactory;
use BoldMinded\Speedy\Service\Purgers\UrlCollector;
use ExpressionEngine\Model\Category\Category;
use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Service\Model\Collection;

class CacheBreaker
{
    /** @var Diagnostics  */
    private $diagnostics;

    /** @var \BoldMinded\Speedy\Service\DriverFactory */
    private $drivers;

    /** @var bool */
    private $enable_refresh;

    /** @var Logger */
    private $logger;

    /** @var string */
    private $prefix;

    /** @var int */
    private $refresh_interval;

    /** @var \BoldMinded\Speedy\Service\Request\Facade */
    private $request;

    private int $siteId = 1;

    private bool $shouldUseQueue = false;

    /**
     * CacheBreaker constructor.
     */
    public function __construct()
    {
        $this->diagnostics = ee('speedy:Diagnostics');
        $this->request = ee('speedy:Request');
        $this->drivers = ee('speedy:DriverFactory');
        $this->logger = ee('speedy:Logger');

        $this->prefix = SPEEDY_CLASS_NAME . '/' . ee()->config->item('site_short_name') . '/';
        $this->refresh_interval = (int) ee()->config->item('speedy_refresh_interval');
        $this->enable_refresh = ee()->config->item('speedy_enable_refresh') !== 'no';
        $this->siteId = ee()->config->item('site_id');

        $this->shouldUseQueue = ee('Addon')->get('queue')?->isInstalled() && bool_config_item('speedy_use_queue');
    }

    public function setSiteId(int $siteId): CacheBreaker
    {
        $this->siteId = $siteId;

        return $this;
    }

    /**
     * Dispatches a cache break for all items.
     */
    public function breakCache()
    {
        // Attempt to break the cache asynchronously/
        if (ee()->config->item('speedy_break_async') === 'yes') {
            $params = [];

            $secret = ee()->config->item('speedy_secret');
            if ($secret !== false && trim($secret) !== '') {
                $params['secret'] = substr(hash('md5', $secret), 10);
            }

            // If successful, we're done; otherwise fall through to sync call.
            if ($this->request->getAction('Speedy', '_break_cache', $params)) {
                return;
            }
        }

        if ($this->shouldUseQueue) {
            ee('queue:QueueManager')->push(BreakCacheJob::class, $this->siteId);
            return;
        }

        $this->_breakCache();
    }

    /**
     * Dispatches a cache break for a single entry.
     */
    public function breakEntryCache(ChannelEntry $entry)
    {
        // Attempt to break the cache asynchronously
        if (ee()->config->item('speedy_break_async') === 'yes') {
            $params = ['ids' => $entry->getId()];

            $secret = ee()->config->item('speedy_secret');
            if ($secret !== false && trim($secret) !== '') {
                $params['secret'] = substr(hash('md5', $secret), 10);
            }

            // If successful, we're done; otherwise fall through to sync call.
            if ($this->request->getAction('Speedy', '_break_entry_cache', $params)) {
                return;
            }
        }

        if ($entry instanceof ChannelEntry) {
            $entry = new Collection([$entry]);
        }

        if ($this->shouldUseQueue) {
            ee('queue:QueueManager')->push(BreakEntryCacheJob::class, [
                'entryIds' => $entry->pluck('entry_id'),
                'siteId' => $this->siteId,
            ]);

            return;
        }

        $this->_breakEntryCache($entry);
    }

    /**
     * Dispatches a cache break for a single category.
     */
    public function breakCategoryCache(Category $category)
    {
        // Attempt to break the cache asynchronously
        if (ee()->config->item('speedy_break_async') === 'yes') {
            $params = ['ids' => $category->getId()];

            $secret = ee()->config->item('speedy_secret');
            if ($secret !== false && trim($secret) !== '') {
                $params['secret'] = substr(hash('md5', $secret), 10);
            }

            // If successful, we're done; otherwise fall through to sync call.
            if ($this->request->getAction('Speedy', '_break_category_cache', $params)) {
                return;
            }
        }

        if ($category instanceof Category) {
            $category = new Collection([$category]);
        }

        if ($this->shouldUseQueue) {
            ee('queue:QueueManager')->push(BreakCategoryCacheJob::class, [
                'categoryId' => $category->pluck('cat_id'),
                'siteId' => $this->siteId,
            ]);

            return;
        }

        $this->_breakCategoryCache($category);
    }

    /**
     * Actually break the cache.
     *
     * This may be called from the action handler asynchronously, or from the
     * public breakEntryCache() method.
     *
     * @return bool
     */
    public function _breakCache()
    {
        // This is a potentially long-running operation, disable the time limit so PHP won't time out.
        set_time_limit(0);

        $clear_tags = [];
        $clear_items = [];
        $refresh_items = [];

        $can_refresh = $this->canRefreshItems() && $this->enable_refresh;

        // Collect all tags and tagged items.
        foreach ($this->collectAllTags() as $tag) {
            $clear_tags[] = $tag->tag;
            $clear_items[] = $tag->key;
            if ($can_refresh) {
                $refresh_items[] = $tag->key;
            }
        }

        // Collect all items from all drivers.
        foreach ($this->collectItemsFromPath('') as $item) {
            $clear_items[] = $item;
            if ($can_refresh) {
                $refresh_items[] = $item;
            }
        }

        $clear_tags = array_unique($clear_tags);
        $clear_items = array_unique($clear_items);
        $refresh_items = array_unique($refresh_items);

        try {
            if (count($clear_tags)) {
                $this->clearTags($clear_tags);
            }

            if (count($clear_items)) {
                $this->clearItems($clear_items);
            }

            if (count($refresh_items)) {
                $this->refreshItems($refresh_items);
            }

            if (ee()->extensions->active_hook('speedy_break_cache')) {
                ee()->extensions->call('speedy_break_cache', [
                    'clearTags' => $clear_tags,
                    'clearItems' => $clear_items,
                    'refreshItems' => $refresh_items,
                ]);
            }
        } catch (\Exception $exception) {
            error_log($exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Actually break the cache.
     *
     * This may be called from the action handler asynchronously, or from the
     * public breakEntryCache() method.
     *
     * @param mixed $entries
     */
    public function _breakEntryCache($entries)
    {
        $entries = $this->normalizeEntriesCollection($entries);

        if (count($entries) === 0) {
            return;
        }

        // This is a potentially long-running operation, disable the time limit
        // so PHP won't time out.
        set_time_limit(0);

        /** @var \BoldMinded\Speedy\Model\CacheBreaking[] $allSettings */
        $allSettings = ee('Model')->get('speedy:CacheBreaking')
            ->filter('entity_type', 'channel')
            ->all();

        if (count($allSettings) === 0) {
            return;
        }

        $clear_tags = [];
        $clear_items = [];
        $refresh_items = [];

        $can_refresh = $this->canRefreshItems() && $this->enable_refresh;

        foreach ($allSettings as $settings) {
            // Select only the $entries in the channel affected by this $settings.
            $entriesCollection = $this->collectRelevantEntries($settings, $entries);
            if (count($entriesCollection) === 0) {
                continue;
            }

            // Parse the tag/item templates configured for this $settings.
            $tags = $this->parseEntrySettingVariables($settings->tags, $entriesCollection);
            $items = $this->parseEntrySettingVariables($settings->items, $entriesCollection);

            // Collect the tags to be cleared
            foreach ($tags as $tag) {
                $clear_tags[] = $tag;
            }

            // Collect the items associated with the tags to be cleared
            foreach ($this->collectTaggedItems($tags) as $child) {
                $clear_items[] = $child;
                if ($can_refresh && $settings->refresh) {
                    $refresh_items[] = $child;
                }
            }

            // It is important to extrapolate paths instead of using the
            // CacheDriver::deletePath() method as we need to know which items
            // are being cleared in case we need to refresh them.
            // @todo: Maybe an earlier check can be introduced to skip this and use deletePath() later
            foreach ($items as $index => $item) {
                if ($this->isPathItem($item)) {
                    foreach ($this->collectItemsFromPath($item) as $child) {
                        $clear_items[] = $child;
                        if ($can_refresh && $settings->refresh) {
                            $refresh_items[] = $child;
                        }
                    }
                } else {
                    $clear_items[] = $this->prefixItem($item);
                    if ($can_refresh && $settings->refresh) {
                        $refresh_items[] = $this->prefixItem($item);
                    }
                }
            }
        }

        $clear_tags = array_unique($clear_tags);
        $clear_items = array_unique($clear_items);
        $refresh_items = array_unique($refresh_items);

        try {
            if (count($clear_tags)) {
                $this->clearTags($clear_tags);
            }

            if (count($clear_items)) {
                $this->clearItems($clear_items);
            }

            if (count($refresh_items)) {
                $this->refreshItems($refresh_items);
            }

            if (ee()->extensions->active_hook('speedy_break_entry_cache')) {
                ee()->extensions->call('speedy_break_entry_cache', [
                    'clearTags' => $clear_tags,
                    'clearItems' => $clear_items,
                    'refreshItems' => $refresh_items,
                ]);
            }
        } catch (\Exception $exception) {
            error_log($exception->getMessage());
        }
    }

    /**
     * Actually break the cache.
     *
     * This may be called from the action handler asynchronously, or from the
     * public breakEntryCache() method.
     *
     * @param mixed $categories
     */
    public function _breakCategoryCache($categories)
    {
        $categories = $this->normalizeCategoriesCollection($categories);

        if (count($categories) === 0) {
            return;
        }

        // This is a potentially long-running operation, disable the time limit
        // so PHP won't time out.
        set_time_limit(0);

        /** @var \BoldMinded\Speedy\Model\CacheBreaking[] $allSettings */
        $allSettings = ee('Model')->get('speedy:CacheBreaking')
            ->filter('entity_type', 'category_group')
            ->all();

        if (count($allSettings) === 0) {
            return;
        }

        $clear_tags = [];
        $clear_items = [];
        $refresh_items = [];

        $can_refresh = $this->canRefreshItems() && $this->enable_refresh;

        foreach ($allSettings as $settings) {
            // Select only the $entries in the channel affected by this $settings.
            $categoriesCollection = $this->collectRelevantCategories($settings, $categories);
            if (count($categoriesCollection) === 0) {
                continue;
            }

            // Parse the tag/item templates configured for this $settings.
            $tags = $this->parseCategorySettingVariables($settings->tags, $categoriesCollection);
            $items = $this->parseCategorySettingVariables($settings->items, $categoriesCollection);

            // Collect the tags to be cleared
            foreach ($tags as $tag) {
                $clear_tags[] = $tag;
            }

            // Collect the items associated with the tags to be cleared
            foreach ($this->collectTaggedItems($tags) as $child) {
                $clear_items[] = $child;
                if ($can_refresh && $settings->refresh) {
                    $refresh_items[] = $child;
                }
            }

            // It is important to extrapolate paths instead of using the
            // CacheDriver::deletePath() method as we need to know which items
            // are being cleared in case we need to refresh them.
            // @todo: Maybe an earlier check can be introduced to skip this and use deletePath() later
            foreach ($items as $index => $item) {
                if ($this->isPathItem($item)) {
                    foreach ($this->collectItemsFromPath($item) as $child) {
                        $clear_items[] = $child;
                        if ($can_refresh && $settings->refresh) {
                            $refresh_items[] = $child;
                        }
                    }
                } else {
                    $clear_items[] = $this->prefixItem($item);
                    if ($can_refresh && $settings->refresh) {
                        $refresh_items[] = $this->prefixItem($item);
                    }
                }
            }
        }

        $clear_tags = array_unique($clear_tags);
        $clear_items = array_unique($clear_items);
        $refresh_items = array_unique($refresh_items);

        try {
            if (count($clear_tags)) {
                $this->clearTags($clear_tags);
            }

            if (count($clear_items)) {
                $this->clearItems($clear_items);
            }

            if (count($refresh_items)) {
                $this->refreshItems($refresh_items);
            }

            if (ee()->extensions->active_hook('speedy_break_category_cache')) {
                ee()->extensions->call('speedy_break_category_cache', [
                    'clearTags' => $clear_tags,
                    'clearItems' => $clear_items,
                    'refreshItems' => $refresh_items,
                ]);
            }
        } catch (\Exception $exception) {
            error_log($exception->getMessage());
        }
    }

    /**
     * Refresh the items found at the following urls.
     *
     * This is registered as a shutdown function and should not be called directly.
     *
     * @param array $urls
     */
    public function _refreshUrls(array $urls)
    {
        if (count($urls) === 0 || !$this->canRefreshItems()) {
            return;
        }

        foreach ($urls as $url) {
            if ($this->shouldUseQueue) {
                ee('queue:QueueManager')->push(RefreshUrlJob::class, [
                    'url' => $url,
                    'interval' => $this->refresh_interval ?? 0,
                ]);
                continue;
            }

            $this->request->get($url);

            // Delay item refresh so we don't stampede the server.
            if ($this->refresh_interval) {
                @sleep($this->refresh_interval);
            }
        }
    }

    /**
     * @param array $tags
     */
    public function clearTags(array $tags)
    {
        if (count($tags) === 0) {
            return;
        }

        $regexTags = array_filter($tags, static fn ($tag) => strpos($tag, '^') !== false);
        $basicTags = array_filter($tags, static fn ($tag) => strpos($tag, '^') === false);

        if (count($basicTags) > 0) {
            $tagResult = ee('Model')->get('speedy:Tag')
                ->filter('tag', 'IN', $basicTags)
                ->filter('site_id', $this->siteId)
                ->all();

            $this->clearTagsFromCollection($tagResult);

            ee('Model')->get('speedy:Tag')
                ->filter('tag', 'IN', $basicTags)
                ->filter('site_id', $this->siteId)
                ->delete();
        }

        if (count($regexTags) > 0) {
            foreach ($regexTags as $regexTag) {
                $tagResult = ee('Model')->get('speedy:Tag')
                    ->filter('tag', 'REGEXP', $regexTag)
                    ->filter('site_id', $this->siteId)
                    ->all();

                $this->clearTagsFromCollection($tagResult);

                foreach ($tagResult as $tag) {
                    $tag->delete();
                }
            }
        }
    }

    private function clearTagsFromCollection(Collection $tagCollection)
    {
        /** @var DriverInterface $driver */
        foreach ($this->drivers->getDrivers() as $driver) {
            if ($driver->isSupported() && $driver->isConfigured()) {
                foreach ($tagCollection as $tagRow) {
                    // Remove the prefix. Each cache driver will re-add it, so we don't want to double up.
                    $key = str_replace($this->prefix, '', $tagRow->key);

                    $this->logger->info(
                        sprintf(
                            'Attempting to clear tag %s',
                            $key
                        )
                    );

                    $result = $driver->deleteItem($key);

                    $this->logger->info(
                        sprintf(
                            '%s %s',
                            $key, $result === true ? 'cleared' : 'not cleared'
                        )
                    );

                    $this->diagnostics->clearDiagnostics($key);
                }
            }
        }
    }

    /**
     * @param array $items
     */
    public function clearItems(array $items)
    {
        /** @var DriverInterface $driver */
        foreach ($this->drivers->getDrivers() as $driver) {
            if ($driver->isSupported() && $driver->isConfigured()) {
                foreach ($items as $item) {
                    // Remove the prefix. Each cache driver will re-add it, so we don't want to double up.
                    $item = str_replace($this->prefix, '', $item);

                    $this->logger->info(
                        sprintf(
                            'Attempting to clear item %s',
                            $item
                        )
                    );

                    $result = $driver->deleteItem($item);

                    $this->logger->info(
                        sprintf(
                            '%s %s',
                            $item, $result === true ? 'cleared' : 'not cleared'
                        )
                    );

                    $this->diagnostics->clearDiagnostics($item);
                }
            }
        }

        // Try purging from reverse proxy if defined. Defaults to Dummy purger and does nothing.
        $purger = (new PurgerFactory())->create();
        $urls = (new UrlCollector($items))->collect();

        if ($this->shouldUseQueue) {
            foreach ($urls as $url) {
                ee('queue:QueueManager')->push(PurgeUrlJob::class, [
                    'url' => $url,
                ]);
            }
        } else {
            $purger->purgeUrls($urls);
        }

        ee('Model')->get('speedy:Url')
            ->filter('key', 'IN', $items)
            ->filter('site_id', $this->siteId)
            ->delete();

        ee('Model')->get('speedy:Tag')
            ->filter('key', 'IN', $items)
            ->filter('site_id', $this->siteId)
            ->delete();
    }

    /**
     * @param array $items
     */
    private function refreshItems(array $items)
    {
        $site_url = rtrim(ee()->config->item('site_url'), '/');

        $clear_urls = [];

        foreach ($items as $item) {
            // Strip local/static prefix from the item.
            // The url of global or other items can not be determined so just skip them.
            if ($this->isLocalItem($item)) {
                $item = substr($item, strlen($this->prefixItem('local/')));
            } elseif ($this->isStaticItem($item)) {
                $item = substr($item, strlen($this->prefixItem('static/')));
            } else {
                continue;
            }

            $this->diagnostics->clearDiagnostics($item);

            $clear_urls[] = $site_url . '/' . $item;
        }

        $this->logger->info(
            sprintf(
                'Refreshing the following URLs %s',
                json_encode($clear_urls, JSON_PRETTY_PRINT)
            )
        );

        $this->_refreshUrls(array_unique($clear_urls));
    }

    /**
     * @param mixed $entries
     * @return ChannelEntry[]
     */
    private function normalizeEntriesCollection($entries)
    {
        if ($entries instanceof ChannelEntry) {
            return [$entries];
        }

        if ($entries instanceof Collection) {
            $entriesCollection = [];

            foreach ($entries as $entry) {
                if ($entry instanceof ChannelEntry) {
                    $entriesCollection[] = $entry;
                }
            }

            return $entriesCollection;
        }

        return [];
    }

    /**
     * @param mixed $categories
     * @return Category[]
     */
    private function normalizeCategoriesCollection($categories)
    {
        if ($categories instanceof Category) {
            return [$categories];
        }

        if ($categories instanceof Collection) {
            $categoriesCollection = [];

            foreach ($categories as $category) {
                if ($category instanceof Category) {
                    $categoriesCollection[] = $category;
                }
            }

            return $categoriesCollection;
        }

        return [];
    }

    /**
     * @param CacheBreaking $settings
     * @param ChannelEntry[] $entries
     * @return ChannelEntry[]
     */
    private function collectRelevantEntries($settings, $entries)
    {
        if ($settings->entity_id === 0) {
            return $entries;
        }

        $entriesCollection = [];

        foreach ($entries as $entry) {
            // If cache clearing is set to 1 or more specific statuses, and the current entry
            // is not of that status, then skip adding the entry to the collection to clear its cache.
            if (
                is_array($settings->statuses) &&
                !empty($settings->statuses) &&
                !in_array($entry->status_id, $settings->statuses)
            ) {
                continue;
            }

            $assignedCategories = $entry->Categories->pluck('cat_id') ?? [];
            $settingsCategories = $settings->categories ?? [];

            if (
                is_array($settings->categories) &&
                !empty($settings->categories) &&
                empty(array_intersect($assignedCategories, $settingsCategories))
            ) {
                continue;
            }

            if ($settings->entity_id === 0 || $settings->entity_id === (int) $entry->channel_id) {
                $entriesCollection[] = $entry;
            }
        }

        return $entriesCollection;
    }

    /**
     * @param CacheBreaking $settings
     * @param Category[] $categories
     * @return Category[]
     */
    private function collectRelevantCategories($settings, $categories)
    {
        if ($settings->entity_id === 0) {
            return $categories;
        }

        $categoriesCollection = [];

        foreach ($categories as $category) {
            if ($settings->entity_id === 0 || $settings->entity_id === (int) $category->CategoryGroup->group_id) {
                $categoriesCollection[] = $category;
            }
        }

        return $categoriesCollection;
    }

    /**
     * @return \BoldMinded\Speedy\Model\Tag[]
     */
    private function collectAllTags()
    {
        return ee('Model')->get('speedy:Tag')->all();
    }

    /**
     * @param string[] $tags
     * @return string[]
     */
    public function collectTaggedItems(array $tags)
    {
        if (empty($tags)) {
            return [];
        }

        $tagged_items = ee('Model')->get('speedy:Tag')
            ->filter('key', 'LIKE', $this->prefix . '%')
            ->filter('tag', 'IN', $tags)
            ->all();

        $keys = [];
        foreach ($tagged_items as $item) {
            $keys[] = $item->key;
        }

        return array_unique($keys);
    }

    /**
     * @param string $basePath
     * @return array
     */
    public function collectItemsFromPath(string $basePath)
    {
        $items = [];

        /** @var DriverInterface $driver */
        foreach ($this->drivers->getDrivers() as $driver) {
            if ($driver->isSupported() && $driver->isConfigured()) {
                $path_items = $driver->getItemsFromPath($basePath);

                foreach ($path_items as $item) {
                    //$items[] = $this->prefix . $base_path . $item;
                    $items[] = $basePath . $item;
                }
            }
        }

        return $items;
    }

    /**
     * @param string[] $strings
     * @param ChannelEntry[] $entries
     * @return string[]
     */
    private function parseEntrySettingVariables($strings, $entries)
    {
        $parsed_strings = [];
        $date_variables = 'entry_date|edit_date';

        foreach ($strings as $original) {
            if (trim($original) === '') {
                continue;
            }

            foreach ($entries as $entry) {
                $string = $original;

                // Parse date variables
                if (preg_match_all('~\{(' . $date_variables . ') format="(.*?)"\}~i', $string, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $timestamp = $entry->{$match[1]};
                        if ($timestamp) {
                            $timestamp = ee()->localize->format_date($match[2], $timestamp);
                        }

                        $string = str_replace($match[0], $timestamp, $string);
                    }
                }

                $replace = $this->buildEntrySettingVariables($entry);
                $parsed_strings[] = str_replace(array_keys($replace), array_values($replace), $string);
            }
        }

        return array_unique($parsed_strings);
    }

    private function buildEntrySettingVariables(ChannelEntry $entry): array
    {
        // @todo: This would be a great place for an extension hook

        $entryDate = $entry->entry_date;
        if ($entryDate instanceof \DateTime) {
            $entryDate = $entryDate->getTimestamp();
        }

        $editDate = $entry->edit_date;
        if ($editDate instanceof \DateTime) {
            $editDate = $editDate->getTimestamp();
        }

        $variables = [
            '{author_id}'       => $entry->author_id ?? '',
            '{author_username}' => $entry->Author?->username ?: '',
            '{channel_id}'      => $entry->channel_id ?? '',
            '{channel_name}'    => $entry->Channel->channel_name ?: '',
            '{channel_title}'   => $entry->Channel->channel_title ?: '',
            '{edit_date}'       => $editDate,
            '{entry_id}'        => $entry->entry_id ?? '',
            '{entry_date}'      => $entryDate,
            '{page_uri}'        => $entry->getPageURI() ? trim($entry->getPageURI(), '/') : '',
            '{title}'           => $entry->title ?? '',
            '{url_title}'       => $entry->url_title ?? '',
            '{username}'        => $entry->Author?->username ?: '',
            '{cat_url_title}'   => '',
            '{cat_url_title_1}' => '',
            '{cat_url_title_2}' => '',
            '{cat_url_title_3}' => '',
            '{cat_url_title_4}' => '',
            '{cat_url_title_5}' => '',
            '{cat_url_title_6}' => '',
            '{cat_url_title 7}' => '',
            '{cat_url_title_8}' => '',
            '{cat_url_title_9}' => '',
        ];

        if ($entry->Categories !== null) {
            $categoryUrlTitles = $entry->Categories->pluck('cat_url_title');

            if (count($categoryUrlTitles) === 1) {
                // If only 1 category is allowed to be assigned to an entry by the category
                // group rules, or if it just happens to have 1 of many available.
                $variables['{cat_url_title}'] = $categoryUrlTitles[0];
            }

            // If this is used, multiple cache breaking rules will need to be defined.
            // E.g. if 3 categories are assigned then 3 rules, {cat_url_title_1},
            // {cat_url_title_2}, and {cat_url_title_3} will be needed.
            foreach ($categoryUrlTitles as $index => $categoryUrlTitle) {
                $variables['{cat_url_title_' . $index + 1 .'}'] = $categoryUrlTitle;
            }

            // Not sure if this will work as expected.
            $variables['{cat_url_title_any}'] = implode('|', $categoryUrlTitles);
        }

        return $variables;
    }

    /**
     * @param string[] $strings
     * @param Category[] $categories
     * @return string[]
     */
    private function parseCategorySettingVariables($strings, $categories)
    {
        $parsed_strings = [];

        foreach ($strings as $original) {
            if (trim($original) === '') {
                continue;
            }

            foreach ($categories as $category) {
                $string = $original;

                $replace = $this->buildCategorySettingVariables($category);
                $parsed_strings[] = str_replace(array_keys($replace), array_values($replace), $string);
            }
        }

        return array_unique($parsed_strings);
    }

    private function buildCategorySettingVariables(Category $category): array
    {
        // @todo: This would be a great place for an extension hook
        $categoryGroupName = $category->CategoryGroup->group_name ?: '';
        $categoryGroupUrlTitle = (string) ee('Format')->make('Text', $categoryGroupName)->urlSlug();

        return [
            '{category_group_id}'           => $category->CategoryGroup->group_id ?: '',
            '{category_group_name}'         => $categoryGroupName,
            '{category_group_url_title}'    => $categoryGroupUrlTitle,
            '{category_id}'                 => $category->cat_id ?? '',
            '{category_name}'               => $category->cat_name ?: '',
            '{category_url_title}'          => $category->cat_url_title ?? '',
        ];
    }

    /**
     * @param string $item
     * @return string
     */
    private function prefixItem($item)
    {
        return $this->prefix . $item;
    }

    /**
     * @param string $item
     * @return bool
     */
    private function isPathItem($item)
    {
        return substr($item, -1) === '/';
    }

    /**
     * @param string $item
     * @return bool
     */
    private function isGlobalItem($item)
    {
        return strpos($item, $this->prefixItem('global/')) === 0;
    }

    /**
     * @param string $item
     * @return bool
     */
    private function isLocalItem($item)
    {
        return strpos($item, $this->prefixItem('local/')) === 0;
    }

    /**
     * @param string $item
     * @return bool
     */
    private function isStaticItem($item)
    {
        return strpos($item, $this->prefixItem('static/')) === 0;
    }

    /**
     * @return bool
     */
    private function canRefreshItems()
    {
        return $this->request->isSupported();
    }
}
