<?php

declare (strict_types=1);
namespace Store\Dependency\Money\Exchange;

use Store\Dependency\Money\Currency;
use Store\Dependency\Money\CurrencyPair;
use Store\Dependency\Money\Exception\UnresolvableCurrencyPairException;
use Store\Dependency\Money\Exchange;
use Store\Dependency\Money\Money;
/**
 * Tries the reverse of the currency pair if one is not available.
 *
 * Note: adding nested ReversedCurrenciesExchange could cause a huge performance hit.
 */
final class ReversedCurrenciesExchange implements Exchange
{
    public function __construct(private readonly Exchange $exchange)
    {
    }
    public function quote(Currency $baseCurrency, Currency $counterCurrency): CurrencyPair
    {
        try {
            return $this->exchange->quote($baseCurrency, $counterCurrency);
        } catch (UnresolvableCurrencyPairException $exception) {
            $calculator = Money::getCalculator();
            try {
                $currencyPair = $this->exchange->quote($counterCurrency, $baseCurrency);
                return new CurrencyPair($baseCurrency, $counterCurrency, $calculator::divide('1', $currencyPair->getConversionRatio()));
            } catch (UnresolvableCurrencyPairException) {
                throw $exception;
            }
        }
    }
}
