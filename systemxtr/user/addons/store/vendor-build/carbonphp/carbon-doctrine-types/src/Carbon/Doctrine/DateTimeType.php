<?php

declare (strict_types=1);
namespace Store\Dependency\Carbon\Doctrine;

use Store\Dependency\Carbon\Carbon;
use DateTime;
use Store\Dependency\Doctrine\DBAL\Platforms\AbstractPlatform;
use Store\Dependency\Doctrine\DBAL\Types\VarDateTimeType;
class DateTimeType extends VarDateTimeType implements CarbonDoctrineType
{
    /** @use CarbonTypeConverter<Carbon> */
    use CarbonTypeConverter;
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Carbon
    {
        return $this->doConvertToPHPValue($value);
    }
}
