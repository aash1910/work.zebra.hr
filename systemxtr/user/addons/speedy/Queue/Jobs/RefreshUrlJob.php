<?php

namespace BoldMinded\Speedy\Queue\Jobs;

use BoldMinded\Queue\Dependency\Illuminate\Contracts\Queue\Job;
use BoldMinded\Queue\Dependency\Illuminate\Contracts\Queue\ShouldBeUnique;
use BoldMinded\Queue\Dependency\Illuminate\Contracts\Queue\ShouldQueue;
use BoldMinded\Queue\Queue\Jobs\AbstractJob;

class RefreshUrlJob extends AbstractJob implements ShouldQueue, ShouldBeUnique
{
    public function fire(Job $job, array $payload = []): bool
    {
        $url = $payload['url'] ?? '';
        $interval = $payload['interval'] ?? 0;

        if (!$url) {
            return false;
        }

        // Since this is queued as separate jobs this shouldn't be much of an issue
        // but still slow things down a bit and prevent stampeding the server.
        if ($interval) {
            @sleep($interval);
        }

        $response = ee('speedy:Request')->get($url);

        if ($response) {
            $job->delete();
        }

        return $response;
    }
}
