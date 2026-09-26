<?php

declare (strict_types=1);
namespace Store\Dependency\Money\Exception;

use Store\Dependency\Money\Exception;
use RuntimeException;
/**
 * Thrown when a Money object cannot be formatted into a string.
 */
final class FormatterException extends RuntimeException implements Exception
{
}
