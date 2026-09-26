<?php

declare (strict_types=1);
namespace Store\Dependency\Doctrine\Inflector\Rules\English;

use Store\Dependency\Doctrine\Inflector\GenericLanguageInflectorFactory;
use Store\Dependency\Doctrine\Inflector\Rules\Ruleset;
final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset(): Ruleset
    {
        return Rules::getSingularRuleset();
    }
    protected function getPluralRuleset(): Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
