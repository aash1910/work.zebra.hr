<?php

declare (strict_types=1);
namespace Store\Dependency\Money\Exchange;

use Store\Dependency\Money\Currency;
/** @internal for sole consumption by {@see IndirectExchange} */
final class IndirectExchangeQueuedItem
{
    public bool $discovered = \false;
    public self|null $parent = null;
    public function __construct(public Currency $currency)
    {
    }
}
