<?php

declare (strict_types=1);
namespace Store\Dependency\Money\Currencies;

use AppendIterator;
use IteratorIterator;
use Store\Dependency\Money\Currencies;
use Store\Dependency\Money\Currency;
use Store\Dependency\Money\Exception\UnknownCurrencyException;
use Traversable;
/**
 * Aggregates several currency repositories.
 */
final class AggregateCurrencies implements Currencies
{
    /**
     * @param Currencies[] $currencies
     */
    public function __construct(private readonly array $currencies)
    {
    }
    public function contains(Currency $currency): bool
    {
        foreach ($this->currencies as $currencies) {
            if ($currencies->contains($currency)) {
                return \true;
            }
        }
        return \false;
    }
    public function subunitFor(Currency $currency): int
    {
        foreach ($this->currencies as $currencies) {
            if ($currencies->contains($currency)) {
                return $currencies->subunitFor($currency);
            }
        }
        throw new UnknownCurrencyException('Cannot find currency ' . $currency->getCode());
    }
    /** {@inheritDoc} */
    public function getIterator(): Traversable
    {
        $iterator = new AppendIterator();
        foreach ($this->currencies as $currencies) {
            $iterator->append(new IteratorIterator($currencies->getIterator()));
        }
        return $iterator;
    }
}
