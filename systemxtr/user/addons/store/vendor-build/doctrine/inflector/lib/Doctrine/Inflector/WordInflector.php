<?php

declare (strict_types=1);
namespace Store\Dependency\Doctrine\Inflector;

interface WordInflector
{
    public function inflect(string $word): string;
}
