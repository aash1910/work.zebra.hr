<?php

declare (strict_types=1);
namespace Store\Dependency\Money\Parser;

use Store\Dependency\Money\Currencies\BitcoinCurrencies;
use Store\Dependency\Money\Currency;
use Store\Dependency\Money\Exception\ParserException;
use Store\Dependency\Money\Money;
use Store\Dependency\Money\MoneyParser;
use function ltrim;
use function rtrim;
use function str_pad;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
/**
 * Parses Bitcoin currency to Money.
 */
final class BitcoinMoneyParser implements MoneyParser
{
    public function __construct(private readonly int $fractionDigits)
    {
    }
    public function parse(string $money, Currency|null $fallbackCurrency = null): Money
    {
        if (strpos($money, BitcoinCurrencies::SYMBOL) === \false) {
            throw new ParserException('Value cannot be parsed as Bitcoin');
        }
        $currency = $fallbackCurrency ?? new Currency(BitcoinCurrencies::CODE);
        $decimal = str_replace(BitcoinCurrencies::SYMBOL, '', $money);
        $decimalSeparator = strpos($decimal, '.');
        if ($decimalSeparator !== \false) {
            $decimal = rtrim($decimal, '0');
            $lengthDecimal = strlen($decimal);
            $decimal = str_replace('.', '', $decimal);
            $decimal .= str_pad('', ($lengthDecimal - $decimalSeparator - $this->fractionDigits - 1) * -1, '0');
        } else {
            $decimal .= str_pad('', $this->fractionDigits, '0');
        }
        if (substr($decimal, 0, 1) === '-') {
            $decimal = '-' . ltrim(substr($decimal, 1), '0');
        } else {
            $decimal = ltrim($decimal, '0');
        }
        if ($decimal === '') {
            $decimal = '0';
        }
        return new Money($decimal, $currency);
    }
}
