<?php

declare (strict_types=1);
namespace Store\Dependency\Money\Exception;

use DomainException;
use Store\Dependency\Money\Exception;
/**
 * Thrown when trying to get ISO currency that does not exists.
 */
final class UnknownCurrencyException extends DomainException implements Exception
{
}
