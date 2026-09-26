<?php

namespace BoldMinded\Speedy\Service\Purgers;

final class UrlCollector
{
    private array $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function collect(): array
    {
        $urls = ee('Model')->get('speedy:Url')
            ->filter('key', 'IN', $this->items)
            ->all()
            ->pluck('url');

        return $urls;
    }
}
