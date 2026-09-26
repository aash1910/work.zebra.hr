<?php

namespace Store\Dependency\GuzzleHttp;

use Store\Dependency\Psr\Http\Message\MessageInterface;
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
