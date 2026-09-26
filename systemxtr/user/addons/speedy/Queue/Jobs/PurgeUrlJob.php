<?php

namespace BoldMinded\Speedy\Queue\Jobs;

use BoldMinded\Queue\Dependency\Illuminate\Contracts\Queue\Job;
use BoldMinded\Queue\Dependency\Illuminate\Contracts\Queue\ShouldBeUnique;
use BoldMinded\Queue\Dependency\Illuminate\Contracts\Queue\ShouldQueue;
use BoldMinded\Queue\Queue\Jobs\AbstractJob;
use BoldMinded\Speedy\Service\Purgers\PurgerFactory;

class PurgeUrlJob extends AbstractJob implements ShouldQueue, ShouldBeUnique
{
    public function fire(Job $job, array $payload = []): bool
    {
        $url = $payload['url'] ?? '';

        if (!$url) {
            return false;
        }

        $purger = (new PurgerFactory())->create();

        $response = $purger->purgeUrl($url);

        if ($response) {
            $job->delete();
        }

        return $response;
    }
}
