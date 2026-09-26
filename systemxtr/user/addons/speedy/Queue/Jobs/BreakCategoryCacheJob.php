<?php

namespace BoldMinded\Speedy\Queue\Jobs;

use BoldMinded\Queue\Dependency\Illuminate\Contracts\Queue\Job;
use BoldMinded\Queue\Dependency\Illuminate\Contracts\Queue\ShouldBeUnique;
use BoldMinded\Queue\Dependency\Illuminate\Contracts\Queue\ShouldQueue;
use BoldMinded\Queue\Queue\Jobs\AbstractJob;

class BreakCategoryCacheJob extends AbstractJob implements ShouldQueue, ShouldBeUnique
{
    public function fire(Job $job, array $payload = []): bool
    {
        $categoryIds = $payload['categoryIds'] ?? [];
        $siteId = $payload['siteId'] ?? 0;

        if (!empty($payload)) {
            $categories = ee('Model')->get('Category')
                ->filter('cat_id', 'IN', $categoryIds)
                ->all();

            $cacheBreaker = ee('speedy:CacheBreaker');

            if ($siteId) {
                $cacheBreaker->setSiteId($siteId);
            }

            $cacheBreaker->_breakCategoryCache($categories);
        }

        // Assume the breaking happened, breakEntryCache doesn't return anything
        $job->delete();

        return true;
    }
}
