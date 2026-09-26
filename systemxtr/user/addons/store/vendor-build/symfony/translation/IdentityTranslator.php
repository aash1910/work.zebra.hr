<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Store\Dependency\Symfony\Component\Translation;

use Store\Dependency\Symfony\Contracts\Translation\LocaleAwareInterface;
use Store\Dependency\Symfony\Contracts\Translation\TranslatorInterface;
use Store\Dependency\Symfony\Contracts\Translation\TranslatorTrait;
/**
 * IdentityTranslator does not translate anything.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IdentityTranslator implements TranslatorInterface, LocaleAwareInterface
{
    use TranslatorTrait;
}
